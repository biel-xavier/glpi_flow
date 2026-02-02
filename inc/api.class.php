<?php

namespace Glpi\Plugin\Flow;

use PluginFlowFlow;
use PluginFlowStep;
use PluginFlowAction;
use PluginFlowValidation;
use PluginFlowTransition;
use PluginFlowActionType;
use PluginFlowValidationType;
use Entity;
use ITILCategory;
use CommonITILObject;
use Toolbox;

class Api
{
    public function handleRequest()
    {
        header('Content-Type: application/json');
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'get_flows':
                    $this->sendResponse($this->getFlows());
                    break;
                case 'get_flow':
                    $id = (int)($_GET['id'] ?? 0);
                    $this->sendResponse($this->getFlow($id));
                    break;
                case 'save_flow':
                    if ($method !== 'POST') throw new \Exception('Method not allowed');
                    $data = json_decode(file_get_contents('php://input'), true);
                    $this->sendResponse($this->saveFlow($data));
                    break;
                case 'delete_flow':
                    $id = (int)($_GET['id'] ?? 0);
                    $this->sendResponse($this->deleteFlow($id));
                    break;
                case 'toggle_active':
                    if ($method !== 'POST') throw new \Exception('Method not allowed');
                    $data = json_decode(file_get_contents('php://input'), true);
                    $this->sendResponse($this->toggleActive($data));
                    break;
                case 'get_metadata':
                    $this->sendResponse($this->getMetadata());
                    break;
                case 'log_js_error':
                    $data = json_decode(file_get_contents('php://input'), true);
                    \Toolbox::logInFile('php-errors', "JS Error: " . json_encode($data));
                    $this->sendResponse(['status' => 'logged']);
                    break;
                case 'get_tables':
                    $this->sendResponse($this->getTables());
                    break;
                case 'get_fields':
                    $this->sendResponse($this->getFields($_GET['table'] ?? ''));
                    break;
                case 'get_task_template_preview':
                    $this->sendResponse($this->getTaskTemplatePreview((int)($_GET['id'] ?? 0)));
                    break;
                case 'get_tags':
                    $this->sendResponse($this->getTags());
                    break;
                case 'import_flow':
                    if ($method !== 'POST') throw new \Exception('Method not allowed');
                    $data = json_decode(file_get_contents('php://input'), true);
                    $this->sendResponse($this->importFlow($data));
                    break;
                default:
                    throw new \Exception('Action not found');
            }
        } catch (\Exception $e) {
            http_response_code(400);
            $this->sendResponse(['error' => $e->getMessage()]);
        }
    }

    private function sendResponse($data)
    {
        echo json_encode($data);
        exit;
    }

    private function getFlows()
    {
        global $DB;
        $iter = $DB->request([
            'FROM' => 'glpi_plugin_flow_flows'
        ]);
        return array_values(iterator_to_array($iter));
    }

    private function getFlow($id)
    {
        global $DB;
        $flow = $DB->request([
            'FROM' => 'glpi_plugin_flow_flows',
            'WHERE' => ['id' => $id]
        ])->current();

        if (!$flow) throw new \Exception('Flow not found');

        // Get Steps
        $steps = array_values(iterator_to_array($DB->request([
            'FROM' => 'glpi_plugin_flow_steps',
            'WHERE' => ['plugin_flow_flows_id' => $id]
        ])));

        foreach ($steps as &$step) {
            $actions = array_values(iterator_to_array($DB->request([
                'FROM' => 'glpi_plugin_flow_actions',
                'WHERE' => ['plugin_flow_steps_id' => $step['id']],
                'ORDER' => 'action_order ASC'
            ])));
            foreach ($actions as &$action) {
                $action['config'] = json_decode($action['action_config'] ?? '{}', true);
            }
            $step['actions'] = $actions;

            $validations = array_values(iterator_to_array($DB->request([
                'FROM' => 'glpi_plugin_flow_validations',
                'WHERE' => ['plugin_flow_steps_id' => $step['id']],
                'ORDER' => 'validation_order ASC'
            ])));
            foreach ($validations as &$validation) {
                $validation['config'] = json_decode($validation['validation_config'] ?? '{}', true);
            }
            $step['validations'] = $validations;

            $step['transitions'] = array_values(iterator_to_array($DB->request([
                'FROM' => 'glpi_plugin_flow_transitions',
                'WHERE' => ['plugin_flow_steps_id_source' => $step['id']]
            ])));
        }

        $flow['steps'] = $steps;
        return $flow;
    }

    private function saveFlow($data)
    {
        global $DB;
        $flow = new \PluginFlowFlow();

        $flowData = [
            'name' => $data['name'],
            'description' => $data['description'],
            'entities_id' => $data['entities_id'],
            'itilcategories_id' => $data['itilcategories_id'],
            'is_active' => $data['is_active']
        ];

        if (isset($data['id']) && !empty($data['id'])) {
            $flowData['id'] = $data['id'];
            $flow->update($flowData);
            $flowId = $data['id'];
        } else {
            $flowId = $flow->add($flowData);
            if (!$flowId) throw new \Exception('Failed to create flow');
        }

        // --- Synchronize Steps ---
        $existingSteps = iterator_to_array($DB->request([
            'FROM' => 'glpi_plugin_flow_steps',
            'WHERE' => ['plugin_flow_flows_id' => $flowId]
        ]));
        $existingStepIds = array_column($existingSteps, 'id');
        $incomingStepIds = array_filter(array_column($data['steps'] ?? [], 'id'));

        // Delete removed steps
        foreach ($existingStepIds as $id) {
            if (!in_array($id, $incomingStepIds)) {
                $stepObj = new \PluginFlowStep();
                $stepObj->delete(['id' => $id], true);
            }
        }

        foreach ($data['steps'] ?? [] as $stepData) {
            $stepObj = new \PluginFlowStep();
            $stepItem = [
                'plugin_flow_flows_id' => $flowId,
                'name' => $stepData['name'],
                'step_type' => $stepData['step_type']
            ];

            if (isset($stepData['id']) && !empty($stepData['id'])) {
                $stepItem['id'] = $stepData['id'];
                $stepObj->update($stepItem);
                $stepId = $stepData['id'];
            } else {
                $stepId = $stepObj->add($stepItem);
            }

            // --- Synchronize Actions ---
            $this->syncNested($stepId, 'glpi_plugin_flow_actions', 'plugin_flow_steps_id', $stepData['actions'] ?? [], \PluginFlowAction::class);

            // --- Synchronize Validations ---
            $this->syncNested($stepId, 'glpi_plugin_flow_validations', 'plugin_flow_steps_id', $stepData['validations'] ?? [], \PluginFlowValidation::class);
        }

        return $this->getFlow($flowId);
    }

    private function syncNested($parentId, $table, $parentField, $incomingItems, $className)
    {
        global $DB;
        $existing = iterator_to_array($DB->request([
            'FROM' => $table,
            'WHERE' => [$parentField => $parentId]
        ]));
        $existingIds = array_column($existing, 'id');
        $incomingIds = array_filter(array_column($incomingItems, 'id'));

        // Delete removed
        foreach ($existingIds as $id) {
            if (!in_array($id, $incomingIds)) {
                $obj = new $className();
                $obj->delete(['id' => $id], true);
            }
        }

        // Add / Update
        foreach ($incomingItems as $itemData) {
            $obj = new $className();
            $itemData[$parentField] = $parentId;

            // Handle JSON config if present
            if (isset($itemData['config'])) {
                $configJson = is_array($itemData['config']) ? json_encode($itemData['config']) : $itemData['config'];
                if ($table === 'glpi_plugin_flow_actions') {
                    $itemData['action_config'] = $configJson;
                } else {
                    $itemData['validation_config'] = $configJson;
                }
                unset($itemData['config']);
            }

            if (isset($itemData['id']) && !empty($itemData['id'])) {
                $obj->update($itemData);
            } else {
                $obj->add($itemData);
            }
        }
    }

    private function deleteFlow($id)
    {
        $flow = new \PluginFlowFlow();
        $flow->delete(['id' => $id], true);
        return ['status' => 'success'];
    }

    private function toggleActive($data)
    {
        $id = (int)($data['id'] ?? 0);
        $isActive = (int)($data['is_active'] ?? 0);

        \Toolbox::logInFile('flow-debug', "API toggleActive called for ID: $id. New Status: $isActive");

        if ($id <= 0) throw new \Exception('Invalid Flow ID');

        $flow = new \PluginFlowFlow();

        // Check rights manually to debug
        if (!$flow->can($id, UPDATE)) {
            \Toolbox::logInFile('flow-debug', "API toggleActive: can($id, UPDATE) returned FALSE");
            // Proceed anyway to let GLPI handle it or die trying
        } else {
            \Toolbox::logInFile('flow-debug', "API toggleActive: can($id, UPDATE) returned TRUE");
        }

        $flow->update([
            'id' => $id,
            'is_active' => $isActive
        ]);

        return ['status' => 'success'];
    }

    private function getMetadata()
    {
        global $DB;

        $actionTypes = array_values(iterator_to_array($DB->request(['FROM' => 'glpi_plugin_flow_action_types'])));
        $validationTypes = array_values(iterator_to_array($DB->request(['FROM' => 'glpi_plugin_flow_validation_types'])));

        // Simplify for frontend
        foreach ($actionTypes as &$at) {
            $at['action_type'] = $at['name']; // Standardize key
            $at['config_schema'] = json_decode($at['config_schema'], true);
        }
        foreach ($validationTypes as &$vt) {
            $vt['validation_type'] = $vt['name']; // Standardize key
            $vt['config_schema'] = json_decode($vt['config_schema'], true);
        }

        return [
            'csrf_token' => \Session::getNewCSRFToken(),
            'action_types' => $actionTypes,
            'validation_types' => $validationTypes,
            'entities' => $this->getDropdownData('Entity'),
            'categories' => $this->getDropdownData('ITILCategory'),
            'users' => $this->getUsers(),
            'groups' => $this->getGroups(),
            'task_templates' => $this->getTaskTemplates()
        ];
    }

    private function getDropdownData($itemtype)
    {
        global $DB;

        // Handle common types explicitly to be safe
        if ($itemtype === 'Entity') {
            $table = 'glpi_entities';
        } elseif ($itemtype === 'ITILCategory') {
            $table = 'glpi_itilcategories';
        } else {
            $table = getTableForItemType($itemtype);
        }
        $iter = $DB->request([
            'SELECT' => ['id', 'name', 'completename'],
            'FROM' => $table,
            'ORDER' => 'completename ASC'
        ]);
        return array_values(iterator_to_array($iter));
    }

    private function getTables()
    {
        global $DB;
        $tables = [];
        $iterator = $DB->request([
            'SELECT' => 'TABLE_NAME',
            'FROM' => 'information_schema.TABLES',
            'WHERE' => [
                'TABLE_SCHEMA' => $DB->dbdefault,
                'TABLE_NAME' => ['LIKE', 'glpi_%']
            ],
            'ORDER' => 'TABLE_NAME ASC'
        ]);
        foreach ($iterator as $data) {
            $tables[] = $data['TABLE_NAME'];
        }
        return $tables;
    }

    private function getFields($table)
    {
        global $DB;
        if (empty($table) || !preg_match('/^glpi_[a-z0-9_]+$/i', $table)) {
            return [];
        }

        $fields = [];
        $iterator = $DB->request([
            'SELECT' => 'COLUMN_NAME',
            'FROM' => 'information_schema.COLUMNS',
            'WHERE' => [
                'TABLE_SCHEMA' => $DB->dbdefault,
                'TABLE_NAME' => $table
            ],
            'ORDER' => 'ORDINAL_POSITION ASC'
        ]);
        foreach ($iterator as $data) {
            $fields[] = $data['COLUMN_NAME'];
        }
        return $fields;
    }

    private function getUsers()
    {
        global $DB;
        $users = [];
        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'realname', 'firstname'],
            'FROM' => 'glpi_users',
            'WHERE' => ['is_deleted' => 0, 'is_active' => 1],
            'ORDER' => 'name ASC'
        ]);
        foreach ($iterator as $data) {
            $fullname = trim(($data['realname'] ?? '') . ' ' . ($data['firstname'] ?? ''));
            $users[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'completename' => !empty($fullname) ? $fullname . ' (' . $data['name'] . ')' : $data['name']
            ];
        }
        return $users;
    }

    private function getGroups()
    {
        global $DB;
        $groups = [];
        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'completename'],
            'FROM' => 'glpi_groups',
            'ORDER' => 'completename ASC'
        ]);
        foreach ($iterator as $data) {
            $groups[] = [
                'id' => $data['id'],
                'name' => $data['name'],
                'completename' => $data['completename'] ?? $data['name']
            ];
        }
        return $groups;
    }

    private function getTaskTemplates()
    {
        global $DB;
        $templates = [];
        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM' => 'glpi_tasktemplates',
            'ORDER' => 'name ASC'
        ]);
        foreach ($iterator as $data) {
            $templates[] = [
                'id' => $data['id'],
                'name' => $data['name']
            ];
        }
        return $templates;
    }

    private function getTaskTemplatePreview($id)
    {
        global $DB;
        if ($id <= 0) {
            return ['error' => 'Invalid template ID'];
        }

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'content', 'taskcategories_id', 'is_private', 'users_id_tech', 'groups_id_tech'],
            'FROM' => 'glpi_tasktemplates',
            'WHERE' => ['id' => $id]
        ]);

        if ($iterator->count() === 0) {
            return ['error' => 'Template not found'];
        }

        return $iterator->current();
    }

    private function getTags()
    {
        global $DB;
        $tags = [];

        // Check if table exists (plugin might not be installed)
        $tableExists = $DB->tableExists('glpi_plugin_tag_tags');
        if (!$tableExists) {
            return [];
        }

        $iterator = $DB->request([
            'SELECT' => ['id', 'name'],
            'FROM' => 'glpi_plugin_tag_tags',
            'ORDER' => 'name ASC'
        ]);

        foreach ($iterator as $data) {
            $tags[] = [
                'id' => $data['id'],
                'name' => $data['name']
            ];
        }
        return $tags;
    }
    private function importFlow($data)
    {
        global $DB;

        $jsonContent = $data['json_content'] ?? '';
        $entityId = (int)($data['entities_id'] ?? 0);
        $catId = (int)($data['itilcategories_id'] ?? 0);
        $flowName = $data['name'] ?? 'Imported Flow';

        if (empty($jsonContent)) {
            throw new \Exception('Empty JSON content');
        }

        $flowData = json_decode($jsonContent, true);
        if (!$flowData) {
            throw new \Exception('Invalid JSON format');
        }

        $DB->beginTransaction();

        try {
            // 1. Create Flow
            $flow = new \PluginFlowFlow();
            $flowId = $flow->add([
                'name' => $flowName,
                'entities_id' => $entityId,
                'itilcategories_id' => $catId,
                'is_active' => 0 // Import as inactive by default
            ]);

            if (!$flowId) {
                throw new \Exception('Failed to create Flow');
            }

            // 2. Create Steps & Map Names to IDs
            $stepNameMap = []; // Name -> DB ID

            foreach ($flowData as $step) {
                $stepName = $step['stepName'];

                // Map Legacy Types
                $typeMap = [
                    'initial'   => 'Initial',
                    'common'    => 'Common',
                    'condition' => 'Condition',
                    'end'       => 'End'
                ];
                $stepType = $typeMap[strtolower($step['stepType'] ?? 'common')] ?? 'Common';

                $stepObj = new \PluginFlowStep();
                $stepId = $stepObj->add([
                    'plugin_flow_flows_id' => $flowId,
                    'name' => $stepName,
                    'step_type' => $stepType
                ]);

                if (!$stepId) {
                    throw new \Exception("Failed to create step: $stepName");
                }

                $stepNameMap[$stepName] = $stepId;

                // 3. Add Validations
                if (isset($step['validations']) && is_array($step['validations'])) {
                    foreach ($step['validations'] as $v) {
                        $valObj = new \PluginFlowValidation();
                        $valObj->add([
                            'plugin_flow_steps_id' => $stepId,
                            'validation_type' => 'QUERY_CHECK',
                            'validation_config' => json_encode($v),
                            'severity' => 'BLOCKER'
                        ]);
                    }
                }

                // 4. Add Actions (Legacy Mapping)
                if (isset($step['actions']) && is_array($step['actions'])) {
                    foreach ($step['actions'] as $legacyType => $config) {
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
                                $actionConfig = $config; // Assuming compatible
                                break;
                            case 'request_approval_to_user_based_field_users':
                                $actionType = 'REQUEST_VALIDATION_FROM_QUERY';
                                $actionConfig = $config; // Assuming compatible
                                break;
                            case 'insert_sla_tto':
                                $actionType = 'ADD_SLA_TTO';
                                $actionConfig = ['slas_id' => $config];
                                break;
                        }

                        if ($actionType) {
                            $actObj = new \PluginFlowAction();
                            $actObj->add([
                                'plugin_flow_steps_id' => $stepId,
                                'action_type' => $actionType,
                                'action_config' => json_encode($actionConfig)
                            ]);
                        }
                    }
                }
            }

            // 5. Create Transitions
            foreach ($flowData as $step) {
                if (!isset($stepNameMap[$step['stepName']])) continue;
                $sourceId = $stepNameMap[$step['stepName']];

                // Helper to add transition
                $addTrans = function ($targetName, $type) use ($sourceId, $stepNameMap) {
                    if (isset($stepNameMap[$targetName])) {
                        $transObj = new \PluginFlowTransition();
                        $transObj->add([
                            'plugin_flow_steps_id_source' => $sourceId,
                            'plugin_flow_steps_id_target' => $stepNameMap[$targetName],
                            'transition_type' => $type
                        ]);
                    }
                };

                if (isset($step['nextStep'])) $addTrans($step['nextStep'], 'default');
                if (isset($step['nextStepCasePositive'])) $addTrans($step['nextStepCasePositive'], 'condition_positive');
                if (isset($step['nextStepCaseNegative'])) $addTrans($step['nextStepCaseNegative'], 'condition_negative');
            }

            $DB->commit();
            return ['status' => 'success', 'flow_id' => $flowId];
        } catch (\Exception $e) {
            $DB->rollback();
            throw new \Exception("Import Failed: " . $e->getMessage());
        }
    }
}
