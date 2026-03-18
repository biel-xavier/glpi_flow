<?php

namespace Glpi\Plugin\Flow\Repository;

class FlowBuilderRepository
{
    public function findFlows(): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'FROM' => 'glpi_plugin_flow_flows'
        ])));
    }

    public function findStepsByFlow(int $flowId): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_flow_steps',
            'WHERE' => ['plugin_flow_flows_id' => $flowId]
        ])));
    }

    public function findActionsByStep(int $stepId): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_flow_actions',
            'WHERE' => ['plugin_flow_steps_id' => $stepId],
            'ORDER' => 'action_order ASC'
        ])));
    }

    public function findValidationsByStep(int $stepId): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_flow_validations',
            'WHERE' => ['plugin_flow_steps_id' => $stepId],
            'ORDER' => 'validation_order ASC'
        ])));
    }

    public function findTransitionsByStep(int $stepId): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'FROM'  => 'glpi_plugin_flow_transitions',
            'WHERE' => ['plugin_flow_steps_id_source' => $stepId]
        ])));
    }

    public function findMetadataTable(string $table): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'FROM' => $table
        ])));
    }

    public function findDropdownData(string $table, array $fields, string $order = 'name ASC'): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'SELECT' => $fields,
            'FROM'   => $table,
            'ORDER'  => $order
        ])));
    }

    public function findUsers(): array
    {
        global $DB;

        return array_values(iterator_to_array($DB->request([
            'SELECT' => ['id', 'name', 'realname', 'firstname'],
            'FROM'   => 'glpi_users',
            'WHERE'  => ['is_deleted' => 0, 'is_active' => 1],
            'ORDER'  => 'name ASC'
        ])));
    }

    public function findTaskTemplate(int $id): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'SELECT' => ['id', 'name', 'content', 'taskcategories_id', 'is_private', 'users_id_tech', 'groups_id_tech'],
            'FROM'   => 'glpi_tasktemplates',
            'WHERE'  => ['id' => $id]
        ]);

        return $iterator->current() ?: null;
    }

    public function findTags(): array
    {
        global $DB;

        if (!$DB->tableExists('glpi_plugin_tag_tags')) {
            return [];
        }

        return array_values(iterator_to_array($DB->request([
            'SELECT' => ['id', 'name'],
            'FROM'   => 'glpi_plugin_tag_tags',
            'ORDER'  => 'name ASC'
        ])));
    }

    public function findGlpiTables(): array
    {
        global $DB;

        $result = [];
        $iterator = $DB->request([
            'SELECT' => 'TABLE_NAME',
            'FROM'   => 'information_schema.TABLES',
            'WHERE'  => [
                'TABLE_SCHEMA' => $DB->dbdefault,
                'TABLE_NAME'   => ['LIKE', 'glpi_%']
            ],
            'ORDER'  => 'TABLE_NAME ASC'
        ]);

        foreach ($iterator as $data) {
            $result[] = $data['TABLE_NAME'];
        }

        return $result;
    }

    public function findTableFields(string $table): array
    {
        global $DB;

        $result = [];
        $iterator = $DB->request([
            'SELECT' => 'COLUMN_NAME',
            'FROM'   => 'information_schema.COLUMNS',
            'WHERE'  => [
                'TABLE_SCHEMA' => $DB->dbdefault,
                'TABLE_NAME'   => $table
            ],
            'ORDER'  => 'ORDINAL_POSITION ASC'
        ]);

        foreach ($iterator as $data) {
            $result[] = $data['COLUMN_NAME'];
        }

        return $result;
    }
}
