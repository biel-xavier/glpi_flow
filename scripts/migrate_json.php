<?php

/**
 * Migration Script for GLPI Flow Plugin (Independent Connection)
 * Usage: php migrate_json.php <json_file> [entity_id] [category_id]
 */

$glpiRoot = realpath(__DIR__ . '/../../..');

if (!isset($argv[1])) {
    die("Usage: php migrate_json.php <json_file> [entity_id] [category_id]\n");
}

$jsonFile = $argv[1];
$entityId = (int)($argv[2] ?? 0);
$catId    = (int)($argv[3] ?? 0);

if (!file_exists($jsonFile)) {
    die("File not found: $jsonFile\n");
}

$configContent = file_get_contents($configFile);
$dbConfigContent = json_decode($configContent, true);

$host = $dbConfigContent['host'];
$user = $dbConfigContent['user'];
$pass = $dbConfigContent['pass'];
$dbname = $dbConfigContent['dbname'];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

// 2. Load and Parse JSON
$content = file_get_contents($jsonFile);
$data = json_decode($content, true);

if (!$data) {
    die("Invalid JSON in $jsonFile\n");
}

$flowName = basename($jsonFile, '.json');
echo "Starting independent migration for Flow: $flowName\n";

// Helper for insertions
$insert = function ($table, $data) use ($pdo) {
    $keys = array_keys($data);
    $fields = implode('`, `', $keys);
    $placeholders = implode(', ', array_fill(0, count($keys), '?'));
    $sql = "INSERT INTO `$table` (`$fields`) VALUES ($placeholders)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(array_values($data));
    return $pdo->lastInsertId();
};

// 3. Create Flow
$flowId = $insert('glpi_plugin_flow_flows', [
    'name' => $flowName,
    'entities_id' => $entityId,
    'itilcategories_id' => $catId,
    'is_active' => 1
]);

$stepIds = []; // name => database id

// 4. Create Steps
foreach ($data as $stepData) {
    $typeMap = [
        'initial'   => 'Initial',
        'common'    => 'Common',
        'condition' => 'Condition',
        'end'       => 'End'
    ];
    $stepType = $typeMap[strtolower($stepData['stepType'] ?? 'common')] ?? 'Common';

    $stepId = $insert('glpi_plugin_flow_steps', [
        'plugin_flow_flows_id' => $flowId,
        'name' => $stepData['stepName'],
        'step_type' => $stepType
    ]);
    $stepIds[$stepData['stepName']] = $stepId;

    // --- Validations ---
    if (isset($stepData['validations']) && is_array($stepData['validations'])) {
        foreach ($stepData['validations'] as $v) {
            $insert('glpi_plugin_flow_validations', [
                'plugin_flow_steps_id' => $stepId,
                'validation_type' => 'QUERY_CHECK',
                'validation_config' => json_encode($v),
                'severity' => 'BLOCKER'
            ]);
        }
    }

    // --- Actions ---
    if (isset($stepData['actions']) && is_array($stepData['actions'])) {
        foreach ($stepData['actions'] as $legacyType => $config) {
            $actionType = '';
            $actionConfig = [];

            switch ($legacyType) {
                case 'insert_tag':
                    $actionType = 'ADD_TAG';
                    $actionConfig = ['tag_id' => $config];
                    break;
                case 'transfer_to_user':
                    $actionType = 'ADD_ACTOR';
                    $actionConfig = ['actor_type' => 'assign', 'user_id' => $config, 'mode' => 'replace'];
                    break;
                case 'transfer_to_group':
                    $actionType = 'ADD_ACTOR';
                    $actionConfig = ['actor_type' => 'assign', 'group_id' => $config, 'mode' => 'replace'];
                    break;
                case 'insert_task_template':
                    $actionType = 'ADD_TASK_TEMPLATE';
                    $actionConfig = ['tasktemplates_id' => $config];
                    break;
                case 'insert_status':
                    $actionType = 'CHANGE_STATUS';
                    $actionConfig = ['status' => $config];
                    break;
                case 'request_approval_to_user':
                    $actionType = 'REQUEST_VALIDATION';
                    $actionConfig = ['user_id' => $config];
                    break;
                case 'transfer_to_user_based_field_users':
                    $actionType = 'TRANSFER_FROM_QUERY';
                    $actionConfig = $config;
                    break;
                case 'request_approval_to_user_based_field_users':
                    $actionType = 'REQUEST_VALIDATION_FROM_QUERY';
                    $actionConfig = $config;
                    break;
                case 'insert_sla_tto':
                    $actionType = 'ADD_SLA_TTO';
                    $actionConfig = ['slas_id' => $config];
                    break;
            }

            if ($actionType) {
                $insert('glpi_plugin_flow_actions', [
                    'plugin_flow_steps_id' => $stepId,
                    'action_type' => $actionType,
                    'action_config' => json_encode($actionConfig)
                ]);
            }
        }
    }
}

// 5. Create Transitions
echo "Creating transitions...\n";
foreach ($data as $stepData) {
    if (!isset($stepIds[$stepData['stepName']])) continue;
    $sourceId = $stepIds[$stepData['stepName']];

    // Default Next Step
    if (isset($stepData['nextStep']) && isset($stepIds[$stepData['nextStep']])) {
        $insert('glpi_plugin_flow_transitions', [
            'plugin_flow_steps_id_source' => $sourceId,
            'plugin_flow_steps_id_target' => $stepIds[$stepData['nextStep']],
            'transition_type' => 'default'
        ]);
    }

    // Condition Positive
    if (isset($stepData['nextStepCasePositive']) && isset($stepIds[$stepData['nextStepCasePositive']])) {
        $insert('glpi_plugin_flow_transitions', [
            'plugin_flow_steps_id_source' => $sourceId,
            'plugin_flow_steps_id_target' => $stepIds[$stepData['nextStepCasePositive']],
            'transition_type' => 'condition_positive'
        ]);
    }

    // Condition Negative
    if (isset($stepData['nextStepCaseNegative']) && isset($stepIds[$stepData['nextStepCaseNegative']])) {
        $insert('glpi_plugin_flow_transitions', [
            'plugin_flow_steps_id_source' => $sourceId,
            'plugin_flow_steps_id_target' => $stepIds[$stepData['nextStepCaseNegative']],
            'transition_type' => 'condition_negative'
        ]);
    }
}

echo "Migration of '$flowName' successfully completed (Flow ID: $flowId).\n";
