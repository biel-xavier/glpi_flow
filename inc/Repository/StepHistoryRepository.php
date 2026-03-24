<?php

namespace Glpi\Plugin\Flow\Repository;

class StepHistoryRepository
{
    public function logStepEntry(int $ticketId, int $flowId, int $stepId): void
    {
        global $DB;

        $DB->insert('glpi_plugin_flow_step_history', [
            'tickets_id'           => $ticketId,
            'plugin_flow_flows_id' => $flowId,
            'plugin_flow_steps_id' => $stepId,
            'date_entered'         => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ]);
    }

    public function findTimeline(int $ticketId): array
    {
        global $DB;

        return iterator_to_array($DB->request([
            'SELECT'    => [
                'h.date_entered',
                's.name AS step_name',
                'f.name AS flow_name'
            ],
            'FROM'      => 'glpi_plugin_flow_step_history AS h',
            'LEFT JOIN' => [
                'glpi_plugin_flow_steps AS s' => [
                    'ON' => [
                        'h' => 'plugin_flow_steps_id',
                        's' => 'id'
                    ]
                ],
                'glpi_plugin_flow_flows AS f' => [
                    'ON' => [
                        'h' => 'plugin_flow_flows_id',
                        'f' => 'id'
                    ]
                ]
            ],
            'WHERE'     => ['h.tickets_id' => $ticketId],
            'ORDER'     => 'h.date_entered DESC'
        ]));
    }
}
