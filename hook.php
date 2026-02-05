<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

function plugin_flow_install()
{
    global $DB;
    $migration = new Migration(100);

    // 1. Action Types (Registry)
    if (!$DB->tableExists('glpi_plugin_flow_action_types')) {
        $query = "CREATE TABLE `glpi_plugin_flow_action_types` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `description` text COLLATE utf8mb4_unicode_ci,
          `config_schema` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON Schema',
          `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    }

    // 2. Validation Types (Registry)
    if (!$DB->tableExists('glpi_plugin_flow_validation_types')) {
        $query = "CREATE TABLE `glpi_plugin_flow_validation_types` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `name` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `description` text COLLATE utf8mb4_unicode_ci,
          `config_schema` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON Schema',
          `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `name` (`name`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    }

    // 3. Flows (Main)
    if (!$DB->tableExists('glpi_plugin_flow_flows')) {
        $query = "CREATE TABLE `glpi_plugin_flow_flows` (
        `id` int(11) NOT NULL AUTO_INCREMENT,
        `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '',
        `description` text COLLATE utf8mb4_unicode_ci,
        `entities_id` int(11) NOT NULL DEFAULT '0',
        `itilcategories_id` int(11) NOT NULL DEFAULT '0',
        `is_active` tinyint(1) NOT NULL DEFAULT '1',
        `comment` text COLLATE utf8mb4_unicode_ci,
        `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (`id`),
        KEY `entities_id` (`entities_id`),
        KEY `itilcategories_id` (`itilcategories_id`),
        KEY `is_active` (`is_active`)
      ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    } else {
        // Migration for existing table
        $migration->addField('glpi_plugin_flow_flows', 'description', 'text');
        $migration->addField('glpi_plugin_flow_flows', 'is_active', 'bool', ['value' => 1]);
    }

    // 4. Steps
    if (!$DB->tableExists('glpi_plugin_flow_steps')) {
        $query = "CREATE TABLE `glpi_plugin_flow_steps` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `plugin_flow_flows_id` int(11) NOT NULL DEFAULT '0', 
          `name` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
          `description` text COLLATE utf8mb4_unicode_ci,
          `step_type` enum('Initial', 'Common', 'Condition', 'End') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Common',
          `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
           PRIMARY KEY (`id`),
           KEY `plugin_flow_flows_id` (`plugin_flow_flows_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    } else {
        // Migration: Ensure plugin_flow_flows_id exists
        if (!$DB->fieldExists('glpi_plugin_flow_steps', 'plugin_flow_flows_id') && $DB->fieldExists('glpi_plugin_flow_steps', 'flows_id')) {
            $migration->changeField('glpi_plugin_flow_steps', 'flows_id', 'plugin_flow_flows_id', 'integer');
        } elseif (!$DB->fieldExists('glpi_plugin_flow_steps', 'plugin_flow_flows_id')) {
            $migration->addField('glpi_plugin_flow_steps', 'plugin_flow_flows_id', 'integer');
        }

        $migration->addField('glpi_plugin_flow_steps', 'description', 'text');
        $migration->addField('glpi_plugin_flow_steps', 'step_type', "enum('Initial', 'Common', 'Condition', 'End')", ['value' => 'Common']);
    }

    // 5. Actions
    if (!$DB->tableExists('glpi_plugin_flow_actions')) {
        $query = "CREATE TABLE `glpi_plugin_flow_actions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `plugin_flow_steps_id` int(11) NOT NULL DEFAULT '0',
          `action_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `action_config` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON',
          `action_order` int(11) NOT NULL DEFAULT '0',
          `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `plugin_flow_steps_id` (`plugin_flow_steps_id`),
          KEY `action_type` (`action_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    } else {
        $migration->addField('glpi_plugin_flow_actions', 'action_type', 'string');
        $migration->addField('glpi_plugin_flow_actions', 'action_config', 'text');
    }

    // 6. Validations
    if (!$DB->tableExists('glpi_plugin_flow_validations')) {
        $query = "CREATE TABLE `glpi_plugin_flow_validations` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `plugin_flow_steps_id` int(11) NOT NULL DEFAULT '0',
          `validation_type` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
          `validation_config` longtext COLLATE utf8mb4_unicode_ci COMMENT 'JSON',
          `validation_order` int(11) NOT NULL DEFAULT '0',
          `severity` enum('INFO', 'WARNING', 'BLOCKER') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'BLOCKER',
          `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `plugin_flow_steps_id` (`plugin_flow_steps_id`),
          KEY `validation_type` (`validation_type`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    } else {
        $migration->addField('glpi_plugin_flow_validations', 'validation_type', 'string');
        $migration->addField('glpi_plugin_flow_validations', 'validation_config', 'text');
        $migration->addField('glpi_plugin_flow_validations', 'severity', "enum('INFO', 'WARNING', 'BLOCKER')", ['value' => 'BLOCKER']);
    }

    // 7. Transitions
    if (!$DB->tableExists('glpi_plugin_flow_transitions')) {
        $query = "CREATE TABLE `glpi_plugin_flow_transitions` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `plugin_flow_steps_id_source` int(11) NOT NULL DEFAULT '0',
          `plugin_flow_steps_id_target` int(11) NOT NULL DEFAULT '0',
          `transition_type` enum('default', 'condition_positive', 'condition_negative') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'default',
          `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `plugin_flow_steps_id_source` (`plugin_flow_steps_id_source`),
          KEY `plugin_flow_steps_id_target` (`plugin_flow_steps_id_target`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    } else {
        $migration->addField('glpi_plugin_flow_transitions', 'transition_type', "enum('default', 'condition_positive', 'condition_negative')", ['value' => 'default']);
    }

    // 8. Ticket State
    if (!$DB->tableExists('glpi_plugin_flow_ticket_states')) {
        $query = "CREATE TABLE `glpi_plugin_flow_ticket_states` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `tickets_id` int(11) NOT NULL DEFAULT '0',
          `plugin_flow_flows_id` int(11) NOT NULL DEFAULT '0',
          `plugin_flow_steps_id` int(11) NOT NULL DEFAULT '0',
          `status` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'PENDING',
          `date_mod` timestamp DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          `date_creation` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `tickets_id` (`tickets_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    }

    // 9. Step History (Transition Log)
    if (!$DB->tableExists('glpi_plugin_flow_step_history')) {
        $query = "CREATE TABLE `glpi_plugin_flow_step_history` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `tickets_id` int(11) NOT NULL,
          `plugin_flow_flows_id` int(11) NOT NULL,
          `plugin_flow_steps_id` int(11) NOT NULL,
          `date_entered` timestamp DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `tickets_id` (`tickets_id`),
          KEY `plugin_flow_flows_id` (`plugin_flow_flows_id`),
          KEY `plugin_flow_steps_id` (`plugin_flow_steps_id`),
          KEY `date_entered` (`date_entered`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
        $migration->addPostQuery($query);
    }

    $migration->executeMigration();

    // Seed Action Types
    $defaultActions = [
        [
            'name' => 'ADD_ACTOR',
            'description' => 'Add an actor (Watcher, Tech, Requester) to the ticket.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'actor_type' => ['type' => 'string', 'enum' => ['observer', 'tech', 'requester']],
                    'user_id' => ['type' => 'integer'],
                    'mode' => ['type' => 'string', 'enum' => ['append', 'replace'], 'default' => 'append']
                ]
            ])
        ],
        [
            'name' => 'CHANGE_STATUS',
            'description' => 'Change the status of the ticket.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'status' => ['type' => 'integer'] // GLPI Status ID
                ]
            ])
        ],
        [
            'name' => 'ADD_SLA_TTO',
            'description' => 'Add SLA TTO to the ticket.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'slas_id' => ['type' => 'integer']
                ]
            ])
        ],
        [
            'name' => 'ADD_TASK_TEMPLATE',
            'description' => 'Insert a task from a template.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'tasktemplates_id' => ['type' => 'integer']
                ]
            ])
        ],
        [
            'name' => 'REQUEST_VALIDATION',
            'description' => 'Request approval/validation.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer'],
                    'group_id' => ['type' => 'integer'],
                    'comment' => ['type' => 'string']
                ]
            ])
        ],
        [
            'name' => 'ADD_TAG',
            'description' => 'Add a tag to the ticket.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'tag_id' => ['type' => 'integer']
                ]
            ])
        ],
        [
            'name' => 'TRANSFER_FROM_QUERY',
            'description' => 'Transfer to a user/group based on a database query and mapping.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'table' => ['type' => 'string'],
                    'field' => ['type' => 'string'],
                    'fieldIndex' => ['type' => 'string'],
                    'data_comparative' => ['type' => 'array']
                ]
            ])
        ],
        [
            'name' => 'REQUEST_VALIDATION_FROM_QUERY',
            'description' => 'Request approval/validation based on a database query and mapping.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'table' => ['type' => 'string'],
                    'field' => ['type' => 'string'],
                    'fieldIndex' => ['type' => 'string'],
                    'comment' => ['type' => 'string'],
                    'data_comparative' => ['type' => 'array']
                ]
            ])
        ],
        [
            'name' => 'REQUEST_HTTP',
            'description' => 'Send an HTTP request (internal or external). Placeholders: {{ticket_id}}, {{ticket_input}}',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'url' => ['type' => 'string'],
                    'method' => ['type' => 'string', 'enum' => ['GET', 'POST', 'PUT', 'DELETE'], 'default' => 'POST'],
                    'is_internal' => ['type' => 'boolean', 'default' => false, 'description' => 'If true, sends current session cookies (for GLPI internal API)'],
                    'headers' => ['type' => 'string', 'format' => 'json', 'description' => 'JSON Object of headers'],
                    'body' => ['type' => 'string', 'format' => 'json', 'description' => 'JSON Body content']
                ],
                'required' => ['url']
            ])
        ]
    ];

    foreach ($defaultActions as $action) {
        $count = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_flow_action_types',
            'WHERE' => ['name' => $action['name']]
        ])->current();

        if ($count['cpt'] == 0) {
            $DB->insert('glpi_plugin_flow_action_types', $action);
        } else {
            $DB->update(
                'glpi_plugin_flow_action_types',
                [
                    'config_schema' => $action['config_schema'],
                    'description' => $action['description']
                ],
                ['name' => $action['name']]
            );
        }
    }

    // Seed Validation Types
    $defaultValidations = [
        [
            'name' => 'FIELD_NOT_EMPTY',
            'description' => 'Check if a specific field is not empty.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'field_name' => ['type' => 'string']
                ]
            ])
        ],
        [
            'name' => 'USER_APPROVAL',
            'description' => 'Wait for approval from a specific user.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'user_id' => ['type' => 'integer']
                ]
            ])
        ],
        [
            'name' => 'QUERY_CHECK',
            'description' => 'Advanced check for values in database or form with comparisons.',
            'config_schema' => json_encode([
                'type' => 'object',
                'properties' => [
                    'base_for_consultation' => ['type' => 'string', 'enum' => ['form', 'database']],
                    'table' => ['type' => 'string'],
                    'field' => ['type' => 'string'],
                    'fieldIndex' => ['type' => 'string'],
                    'function' => ['type' => 'string', 'enum' => ['verify_value', 'verify_length']],
                    'validator' => ['type' => 'string', 'enum' => ['EQUAL', 'DIFFERENT', 'MAJOR', 'MINOR']],
                    'value' => ['type' => 'mixed']
                ]
            ])
        ]
    ];

    foreach ($defaultValidations as $validation) {
        $count = $DB->request([
            'COUNT' => 'cpt',
            'FROM' => 'glpi_plugin_flow_validation_types',
            'WHERE' => ['name' => $validation['name']]
        ])->current();

        if ($count['cpt'] == 0) {
            $DB->insert('glpi_plugin_flow_validation_types', $validation);
        }
    }

    // 10. Install Assets (Frontend)
    plugin_flow_install_assets();

    return true;
}

/**
 * Copy frontend assets from web/dist to GLPI_ROOT/public/flow
 */
function plugin_flow_install_assets()
{
    $source = __DIR__ . '/web/dist';
    $dest = GLPI_ROOT . '/public/flow';

    if (!is_dir($source)) {
        // If dist doesn't exist, we can't deploy. 
        // In a dev env, this might mean build hasn't run.
        // In prod, it means the package is malformed.
        \Toolbox::logInFile('php-errors', "Flow Plugin: web/dist directory not found. Assets not deployed.");
        return;
    }

    if (!is_dir($dest)) {
        if (!mkdir($dest, 0755, true)) {
            \Toolbox::logInFile('php-errors', "Flow Plugin: Failed to create public/flow directory.");
            return;
        }
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($source, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::SELF_FIRST
    );

    foreach ($iterator as $item) {
        // Calculate relative path safely
        // $item is SplFileInfo
        $fullPath = $item->getPathname();
        // Remove source prefix to get relative path
        $relativePath = substr($fullPath, strlen($source) + 1);
        $destPath = $dest . DIRECTORY_SEPARATOR . $relativePath;

        if ($item->isDir()) {
            if (!is_dir($destPath)) {
                mkdir($destPath);
            }
        } else {
            copy($item, $destPath);
        }
    }
}

function plugin_flow_uninstall()
{
    global $DB;
    $tables = [
        'glpi_plugin_flow_step_history',
        'glpi_plugin_flow_ticket_states',
        'glpi_plugin_flow_actions',
        'glpi_plugin_flow_validations',
        'glpi_plugin_flow_transitions',
        'glpi_plugin_flow_steps',
        'glpi_plugin_flow_flows',
        'glpi_plugin_flow_action_types',
        'glpi_plugin_flow_validation_types'
    ];
    foreach ($tables as $table) {
        if ($DB->tableExists($table)) {
            $DB->dropTable($table);
        }
    }
    return true;
}

function plugin_flow_getMenuContent()
{
    $menu = [];
    $menu['title'] = __('Flow', 'flow');
    $menu['page']  = '/plugins/flow/front/flow.php';
    $menu['icon']  = 'fa fa-project-diagram';
    return $menu;
}
