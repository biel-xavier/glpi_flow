<?php

/**
 * Migration Script for GLPI Flow Plugin (Table to Table)
 * This script migrates flows from glpi_plugin_flow_flows_old to the new schema.
 */

$glpiRoot = realpath(__DIR__ . '/../../..');
$configFile = $glpiRoot . '/marketplace/flow/scripts/configs.json';

// 1. Extract DB Credentials from config_db.php (avoiding class load error)
if (!file_exists($configFile)) {
    die("Config file not found: $configFile\n");
}

$configContent = file_get_contents($configFile);
$dbConfig = json_decode($configContent, true);

$host   = $dbConfig['host'];
$dbname = $dbConfig['dbname'];
$user   = $dbConfig['user'];
$pass   = $dbConfig['pass'];


try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    echo "Connected to database: $dbname\n";
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage() . "\n");
}

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

// 2. Fetch legacy flows
try {
    $stmt = $pdo->query("SELECT * FROM `glpi_plugin_flow_flows_old`");
    $legacyFlows = $stmt->fetchAll();
} catch (PDOException $e) {
    die("Error fetching from glpi_plugin_flow_flows_old: " . $e->getMessage() . "\n" .
        "Make sure the table exists and was renamed correctly.\n");
}

if (empty($legacyFlows)) {
    die("No flows found in glpi_plugin_flow_flows_old.\n");
}

echo "Found " . count($legacyFlows) . " flows to migrate.\n";

foreach ($legacyFlows as $oldFlow) {
    $flowName = $oldFlow['name'];
    $jsonContent = $oldFlow['json_data'];
    $data = json_decode($jsonContent, true);

    if (!$data) {
        echo "[SKIP] Invalid JSON for flow: $flowName (ID: {$oldFlow['id']})\n";
        continue;
    }

    echo "Migrating Flow: $flowName\n";

    // 3. Create Flow
    $flowId = $insert('glpi_plugin_flow_flows', [
        'name' => $flowName,
        'entities_id' => $oldFlow['entities_id'] ?? 0,
        'itilcategories_id' => $oldFlow['itilcategories_id'] ?? 0,
        'is_active' => 1
    ]);

    $stepIds = []; // name => database id

    // 4. Create Steps
    foreach ($data as $stepData) {
        $typeMap = [
            'initial'   => 'Initial',
            'common'    => 'Common',
            'condition' => 'Condition',
            'request'   => 'Request',
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
    echo "Migration of '$flowName' successfully completed (New Flow ID: $flowId).\n";
}

echo "\nTotal migration completed.\n";
