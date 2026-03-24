<?php

namespace Glpi\Plugin\Flow\Service;

use Glpi\Plugin\Flow\Repository\FlowBuilderRepository;

class FlowBuilderService
{
    private FlowBuilderRepository $repository;

    public function __construct(?FlowBuilderRepository $repository = null)
    {
        $this->repository = $repository ?? new FlowBuilderRepository();
    }

    public function getFlows(): array
    {
        return $this->repository->findFlows();
    }

    public function getFlow(int $id): array
    {
        global $DB;

        $flow = $DB->request([
            'FROM'  => 'glpi_plugin_flow_flows',
            'WHERE' => ['id' => $id]
        ])->current();

        if (!$flow) {
            throw new \Exception('Flow not found');
        }

        $steps = $this->repository->findStepsByFlow($id);
        foreach ($steps as &$step) {
            $actions = $this->repository->findActionsByStep((int) $step['id']);
            foreach ($actions as &$action) {
                $action['config'] = json_decode($action['action_config'] ?? '{}', true);
            }
            $step['actions'] = $actions;

            $validations = $this->repository->findValidationsByStep((int) $step['id']);
            foreach ($validations as &$validation) {
                $validation['config'] = json_decode($validation['validation_config'] ?? '{}', true);
            }
            $step['validations'] = $validations;
            $step['transitions'] = $this->repository->findTransitionsByStep((int) $step['id']);
        }

        $flow['steps'] = $steps;
        return $flow;
    }

    public function saveFlow(array $data): array
    {
        global $DB;

        $flow = new \PluginFlowFlow();
        $flowData = [
            'name'              => $data['name'],
            'description'       => $data['description'],
            'entities_id'       => $data['entities_id'],
            'itilcategories_id' => $data['itilcategories_id'],
            'is_active'         => $data['is_active']
        ];

        if (!empty($data['id'])) {
            $flowData['id'] = $data['id'];
            $flow->update($flowData);
            $flowId = (int) $data['id'];
        } else {
            $flowId = (int) $flow->add($flowData);
            if ($flowId <= 0) {
                throw new \Exception('Failed to create flow');
            }
        }

        $existingSteps = $this->repository->findStepsByFlow($flowId);
        $existingStepIds = array_column($existingSteps, 'id');
        $incomingStepIds = [];

        foreach (($data['steps'] ?? []) as $stepData) {
            if (isset($stepData['id']) && is_numeric($stepData['id']) && $stepData['id'] > 0) {
                $incomingStepIds[] = (int) $stepData['id'];
            }
        }

        foreach ($existingStepIds as $id) {
            if (!in_array($id, $incomingStepIds, true)) {
                (new \PluginFlowStep())->delete(['id' => $id], true);
            }
        }

        $idMap = [];
        foreach (($data['steps'] ?? []) as $stepData) {
            $stepObj = new \PluginFlowStep();
            $stepItem = [
                'plugin_flow_flows_id' => $flowId,
                'name'                 => $stepData['name'],
                'step_type'            => $stepData['step_type']
            ];

            if (isset($stepData['id']) && is_numeric($stepData['id']) && $stepData['id'] > 0) {
                $stepItem['id'] = $stepData['id'];
                $stepObj->update($stepItem);
                $realId = (int) $stepData['id'];
                $idMap[$realId] = $realId;
            } else {
                $realId = (int) $stepObj->add($stepItem);
            }

            if (isset($stepData['_tmp_id'])) {
                $idMap[$stepData['_tmp_id']] = $realId;
            }
            if (isset($stepData['id'])) {
                $idMap[$stepData['id']] = $realId;
            }

            $this->syncNested(
                $realId,
                'glpi_plugin_flow_actions',
                'plugin_flow_steps_id',
                $stepData['actions'] ?? [],
                \PluginFlowAction::class
            );
            $this->syncNested(
                $realId,
                'glpi_plugin_flow_validations',
                'plugin_flow_steps_id',
                $stepData['validations'] ?? [],
                \PluginFlowValidation::class
            );
        }

        $transObj = new \PluginFlowTransition();
        $transObj->deleteByCriteria(['plugin_flow_steps_id_source' => array_values($idMap)]);

        foreach (($data['steps'] ?? []) as $stepData) {
            $sourceId = 0;
            if (isset($stepData['id'], $idMap[$stepData['id']])) {
                $sourceId = $idMap[$stepData['id']];
            } elseif (isset($stepData['_tmp_id'], $idMap[$stepData['_tmp_id']])) {
                $sourceId = $idMap[$stepData['_tmp_id']];
            }

            if ($sourceId <= 0) {
                continue;
            }

            foreach (($stepData['transitions'] ?? []) as $transition) {
                $targetKey = $transition['target_step_id'] ?? $transition['target_tmp_id'] ?? $transition['plugin_flow_steps_id_target'] ?? null;
                if ($targetKey === null || !isset($idMap[$targetKey])) {
                    continue;
                }

                $transObj->add([
                    'plugin_flow_steps_id_source' => $sourceId,
                    'plugin_flow_steps_id_target' => $idMap[$targetKey],
                    'transition_type'             => $transition['transition_type']
                ]);
            }
        }

        return $this->getFlow($flowId);
    }

    public function deleteFlow(int $id): array
    {
        (new \PluginFlowFlow())->delete(['id' => $id], true);
        return ['status' => 'success'];
    }

    public function toggleActive(array $data): array
    {
        $id = (int) ($data['id'] ?? 0);
        $isActive = (int) ($data['is_active'] ?? 0);

        if ($id <= 0) {
            throw new \Exception('Invalid Flow ID');
        }

        (new \PluginFlowFlow())->update([
            'id'        => $id,
            'is_active' => $isActive
        ]);

        return ['status' => 'success'];
    }

    public function getMetadata(): array
    {
        $actionTypes = $this->repository->findMetadataTable('glpi_plugin_flow_action_types');
        $validationTypes = $this->repository->findMetadataTable('glpi_plugin_flow_validation_types');

        foreach ($actionTypes as &$actionType) {
            $actionType['action_type'] = $actionType['name'];
            $actionType['config_schema'] = json_decode($actionType['config_schema'] ?? '{}', true);
        }

        foreach ($validationTypes as &$validationType) {
            $validationType['validation_type'] = $validationType['name'];
            $validationType['config_schema'] = json_decode($validationType['config_schema'] ?? '{}', true);
        }

        return [
            'csrf_token'       => \Session::getNewCSRFToken(),
            'action_types'     => $actionTypes,
            'validation_types' => $validationTypes,
            'entities'         => $this->getDropdownData('Entity'),
            'categories'       => $this->getDropdownData('ITILCategory'),
            'users'            => $this->getUsers(),
            'groups'           => $this->getGroups(),
            'task_templates'   => $this->getTaskTemplates(),
        ];
    }

    public function getTables(): array
    {
        return $this->repository->findGlpiTables();
    }

    public function getFields(string $table): array
    {
        if (empty($table) || !preg_match('/^glpi_[a-z0-9_]+$/i', $table)) {
            return [];
        }

        return $this->repository->findTableFields($table);
    }

    public function getTaskTemplatePreview(int $id): array
    {
        if ($id <= 0) {
            return ['error' => 'Invalid template ID'];
        }

        $template = $this->repository->findTaskTemplate($id);
        if ($template === null) {
            return ['error' => 'Template not found'];
        }

        return $template;
    }

    public function getTags(): array
    {
        return $this->repository->findTags();
    }

    public function importFlow(array $data): array
    {
        global $DB;

        $jsonContent = $data['json_content'] ?? '';
        $entityId = (int) ($data['entities_id'] ?? 0);
        $catId = (int) ($data['itilcategories_id'] ?? 0);
        $flowName = $data['name'] ?? 'Imported Flow';

        if ($jsonContent === '') {
            throw new \Exception('Empty JSON content');
        }

        $flowData = json_decode($jsonContent, true);
        if (!$flowData) {
            throw new \Exception('Invalid JSON format');
        }

        $DB->beginTransaction();
        try {
            $flowId = (new \PluginFlowFlow())->add([
                'name'              => $flowName,
                'entities_id'       => $entityId,
                'itilcategories_id' => $catId,
                'is_active'         => 0
            ]);

            if (!$flowId) {
                throw new \Exception('Failed to create Flow');
            }

            $stepNameMap = [];
            foreach ($flowData as $step) {
                $stepName = $step['stepName'];
                $typeMap = [
                    'initial'   => 'Initial',
                    'common'    => 'Common',
                    'condition' => 'Condition',
                    'end'       => 'End'
                ];
                $stepType = $typeMap[strtolower($step['stepType'] ?? 'common')] ?? 'Common';

                $stepId = (new \PluginFlowStep())->add([
                    'plugin_flow_flows_id' => $flowId,
                    'name'                 => $stepName,
                    'step_type'            => $stepType
                ]);

                if (!$stepId) {
                    throw new \Exception('Failed to create step: ' . $stepName);
                }

                $stepNameMap[$stepName] = $stepId;

                foreach (($step['validations'] ?? []) as $validation) {
                    (new \PluginFlowValidation())->add([
                        'plugin_flow_steps_id' => $stepId,
                        'validation_type'      => 'QUERY_CHECK',
                        'validation_config'    => json_encode($validation),
                        'severity'             => 'BLOCKER'
                    ]);
                }

                foreach (($step['actions'] ?? []) as $legacyType => $config) {
                    [$actionType, $actionConfig] = $this->mapLegacyAction($legacyType, $config);
                    if ($actionType === '') {
                        continue;
                    }

                    (new \PluginFlowAction())->add([
                        'plugin_flow_steps_id' => $stepId,
                        'action_type'          => $actionType,
                        'action_config'        => json_encode($actionConfig)
                    ]);
                }
            }

            foreach ($flowData as $step) {
                if (!isset($stepNameMap[$step['stepName']])) {
                    continue;
                }

                $sourceId = $stepNameMap[$step['stepName']];
                $addTransition = function ($targetName, $type) use ($sourceId, $stepNameMap) {
                    if (!isset($stepNameMap[$targetName])) {
                        return;
                    }

                    (new \PluginFlowTransition())->add([
                        'plugin_flow_steps_id_source' => $sourceId,
                        'plugin_flow_steps_id_target' => $stepNameMap[$targetName],
                        'transition_type'             => $type
                    ]);
                };

                if (isset($step['nextStep'])) {
                    $addTransition($step['nextStep'], 'default');
                }
                if (isset($step['nextStepCasePositive'])) {
                    $addTransition($step['nextStepCasePositive'], 'condition_positive');
                }
                if (isset($step['nextStepCaseNegative'])) {
                    $addTransition($step['nextStepCaseNegative'], 'condition_negative');
                }
            }

            $DB->commit();
            return ['status' => 'success', 'flow_id' => $flowId];
        } catch (\Exception $e) {
            $DB->rollback();
            throw new \Exception('Import Failed: ' . $e->getMessage());
        }
    }

    private function syncNested(int $parentId, string $table, string $parentField, array $incomingItems, string $className): void
    {
        global $DB;

        $existing = iterator_to_array($DB->request([
            'FROM'  => $table,
            'WHERE' => [$parentField => $parentId]
        ]));
        $existingIds = array_column($existing, 'id');
        $incomingIds = array_filter(array_column($incomingItems, 'id'));

        foreach ($existingIds as $id) {
            if (!in_array($id, $incomingIds, true)) {
                (new $className())->delete(['id' => $id], true);
            }
        }

        foreach ($incomingItems as $itemData) {
            $itemData[$parentField] = $parentId;
            if (isset($itemData['config'])) {
                $configJson = is_array($itemData['config']) ? json_encode($itemData['config']) : $itemData['config'];
                if ($table === 'glpi_plugin_flow_actions') {
                    $itemData['action_config'] = $configJson;
                } else {
                    $itemData['validation_config'] = $configJson;
                }
                unset($itemData['config']);
            }

            $object = new $className();
            if (!empty($itemData['id'])) {
                $object->update($itemData);
            } else {
                $object->add($itemData);
            }
        }
    }

    private function getDropdownData(string $itemtype): array
    {
        if ($itemtype === 'Entity') {
            $table = 'glpi_entities';
        } elseif ($itemtype === 'ITILCategory') {
            $table = 'glpi_itilcategories';
        } else {
            $table = getTableForItemType($itemtype);
        }

        return $this->repository->findDropdownData($table, ['id', 'name', 'completename'], 'completename ASC');
    }

    private function getUsers(): array
    {
        $users = [];
        foreach ($this->repository->findUsers() as $data) {
            $fullname = trim(($data['realname'] ?? '') . ' ' . ($data['firstname'] ?? ''));
            $users[] = [
                'id'           => $data['id'],
                'name'         => $data['name'],
                'completename' => $fullname !== '' ? $fullname . ' (' . $data['name'] . ')' : $data['name']
            ];
        }

        return $users;
    }

    private function getGroups(): array
    {
        $groups = [];
        foreach ($this->repository->findDropdownData('glpi_groups', ['id', 'name', 'completename'], 'completename ASC') as $data) {
            $groups[] = [
                'id'           => $data['id'],
                'name'         => $data['name'],
                'completename' => $data['completename'] ?? $data['name']
            ];
        }

        return $groups;
    }

    private function getTaskTemplates(): array
    {
        $templates = [];
        foreach ($this->repository->findDropdownData('glpi_tasktemplates', ['id', 'name'], 'name ASC') as $data) {
            $templates[] = [
                'id'   => $data['id'],
                'name' => $data['name']
            ];
        }

        return $templates;
    }

    private function mapLegacyAction(string $legacyType, $config): array
    {
        switch ($legacyType) {
            case 'insert_tag':
                return ['ADD_TAG', ['tag_id' => $config]];
            case 'transfer_to_user':
                return ['ADD_ACTOR', ['actor_type' => 'assign', 'user_id' => $config, 'mode' => 'replace']];
            case 'transfer_to_group':
                return ['ADD_ACTOR', ['actor_type' => 'assign', 'group_id' => $config, 'mode' => 'replace']];
            case 'insert_task_template':
                return ['ADD_TASK_TEMPLATE', ['tasktemplates_id' => $config]];
            case 'insert_status':
                return ['CHANGE_STATUS', ['status' => $config]];
            case 'request_approval_to_user':
                return ['REQUEST_VALIDATION', ['user_id' => $config]];
            case 'transfer_to_user_based_field_users':
                return ['TRANSFER_FROM_QUERY', $config];
            case 'request_approval_to_user_based_field_users':
                return ['REQUEST_VALIDATION_FROM_QUERY', $config];
            case 'insert_sla_tto':
                return ['ADD_SLA_TTO', ['slas_id' => $config]];
            default:
                return ['', []];
        }
    }
}
