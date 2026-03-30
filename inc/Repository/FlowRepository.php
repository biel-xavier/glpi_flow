<?php

namespace Glpi\Plugin\Flow\Repository;

class FlowRepository
{
    /** @var array<string, bool> */
    private static array $categoryCache = [];

    public function hasActiveFlowForCategory(int $categoryId): bool
    {
        global $DB;

        if ($categoryId <= 0) {
            return false;
        }

        $cacheKey = (string) $categoryId;
        if (array_key_exists($cacheKey, self::$categoryCache)) {
            return self::$categoryCache[$cacheKey];
        }

        $result = $DB->request([
            'COUNT' => 'cpt',
            'FROM'  => 'glpi_plugin_flow_flows',
            'WHERE' => [
                'itilcategories_id' => $categoryId,
                'is_active'         => 1,
            ],
        ])->current();

        self::$categoryCache[$cacheKey] = (int) ($result['cpt'] ?? 0) > 0;

        return self::$categoryCache[$cacheKey];
    }

    public function findMatchingFlow(int $entityId, int $categoryId): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_flow_flows',
            'WHERE' => [
                'entities_id'       => $entityId,
                'itilcategories_id' => $categoryId,
                'is_active'         => 1,
            ],
            'LIMIT' => 1
        ]);

        return $iterator->current() ?: null;
    }

    public function findInitialStep(int $flowId): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_flow_steps',
            'WHERE' => [
                'plugin_flow_flows_id' => $flowId,
                'step_type'            => 'Initial'
            ],
            'LIMIT' => 1
        ]);

        return $iterator->current() ?: null;
    }

    public function findStep(int $stepId): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_flow_steps',
            'WHERE' => ['id' => $stepId],
            'LIMIT' => 1
        ]);

        return $iterator->current() ?: null;
    }

    public function findTransitions(int $stepId): array
    {
        global $DB;

        return iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_flow_transitions',
            'WHERE' => ['plugin_flow_steps_id_source' => $stepId]
        ]));
    }

    public function findTransitionByType(int $sourceStepId, string $type): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_flow_transitions',
            'WHERE' => [
                'plugin_flow_steps_id_source' => $sourceStepId,
                'transition_type'             => $type
            ],
            'LIMIT' => 1
        ]);

        return $iterator->current() ?: null;
    }

    public function findStepValidations(int $stepId, bool $onlyBlockers = true): array
    {
        global $DB;

        $where = ['val.plugin_flow_steps_id' => $stepId];
        if ($onlyBlockers) {
            $where['val.severity'] = 'BLOCKER';
        }

        return iterator_to_array($DB->request([
            'SELECT' => [
                'val.*',
                'type.name AS type_name'
            ],
            'FROM' => 'glpi_plugin_flow_validations AS val',
            'LEFT JOIN' => [
                'glpi_plugin_flow_validation_types AS type' => [
                    'ON' => [
                        'val'  => 'validation_type',
                        'type' => 'name'
                    ]
                ]
            ],
            'WHERE' => $where,
            'ORDER' => 'val.validation_order ASC'
        ]));
    }

    public function findStepActions(int $stepId): array
    {
        global $DB;

        return iterator_to_array($DB->request([
            'SELECT' => [
                'act.*',
                'type.name AS type_name'
            ],
            'FROM' => 'glpi_plugin_flow_actions AS act',
            'LEFT JOIN' => [
                'glpi_plugin_flow_action_types AS type' => [
                    'ON' => [
                        'act'  => 'action_type',
                        'type' => 'name'
                    ]
                ]
            ],
            'WHERE' => ['act.plugin_flow_steps_id' => $stepId],
            'ORDER' => 'act.action_order ASC'
        ]));
    }
}
