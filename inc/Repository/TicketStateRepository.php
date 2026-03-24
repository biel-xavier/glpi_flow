<?php

namespace Glpi\Plugin\Flow\Repository;

class TicketStateRepository
{
    public function create(int $ticketId, int $flowId, int $stepId): void
    {
        global $DB;

        $DB->insert('glpi_plugin_flow_ticket_states', [
            'tickets_id'            => $ticketId,
            'plugin_flow_flows_id'  => $flowId,
            'plugin_flow_steps_id'  => $stepId,
            'status'                => 'PENDING',
            'date_mod'              => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ]);
    }

    public function findByTicket(int $ticketId): ?array
    {
        global $DB;

        $iterator = $DB->request([
            'FROM'  => 'glpi_plugin_flow_ticket_states',
            'WHERE' => ['tickets_id' => $ticketId],
            'LIMIT' => 1
        ]);

        return $iterator->current() ?: null;
    }

    public function updateStep(int $stateId, int $stepId): void
    {
        global $DB;

        $DB->update('glpi_plugin_flow_ticket_states', [
            'plugin_flow_steps_id' => $stepId,
            'date_mod'             => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ], [
            'id' => $stateId
        ]);
    }

    public function markComplete(int $stateId): void
    {
        global $DB;

        $DB->update('glpi_plugin_flow_ticket_states', [
            'status'   => 'COMPLETE',
            'date_mod' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ], [
            'id' => $stateId
        ]);
    }
}
