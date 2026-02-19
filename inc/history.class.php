<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

class PluginFlowHistory extends CommonGLPI
{
    static $rightname = 'plugin_flow_history';

    public static function getRights($interface = 'central')
    {
        return [
            READ   => __('Read'),
        ];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item->getType() === 'Ticket' && Session::haveRight(self::$rightname, READ)) {
            return __('Flow Timeline', 'flow');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item->getType() === 'Ticket') {
            $history = new self();
            $history->showTimeline($item);
        }
        return true;
    }

    public function showTimeline(Ticket $ticket)
    {
        global $DB;

        $ticket_id = $ticket->getID();

        echo "<div class='container-fluid'>";
        echo "<div class='row mb-2'>";
        echo "<div class='col-12 d-flex justify-content-end'>";
        echo "<a href='/marketplace/flow/front/export.php?tickets_id=$ticket_id&type=csv' class='btn btn-outline-secondary btn-sm me-2'><i class='fas fa-file-csv'></i> CSV</a>";
        echo "<a href='/marketplace/flow/front/export.php?tickets_id=$ticket_id&type=xls' class='btn btn-outline-secondary btn-sm'><i class='fas fa-file-excel'></i> XLS</a>";
        echo "</div>";
        echo "</div>";

        $iterator = $DB->request([
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
            'WHERE'     => ['h.tickets_id' => $ticket_id],
            'ORDER'     => 'h.date_entered DESC'
        ]);

        echo "<table class='table table-hover'>";
        echo "<thead><tr>";
        echo "<th>" . __('Flow', 'flow') . "</th>";
        echo "<th>" . __('Step', 'flow') . "</th>";
        echo "<th>" . __('Date Entered', 'flow') . "</th>";
        echo "</tr></thead>";
        echo "<tbody>";

        if (count($iterator) > 0) {
            foreach ($iterator as $data) {
                echo "<tr>";
                echo "<td>" . htmlescape($data['flow_name'] ?? '---') . "</td>";
                echo "<td>" . htmlescape($data['step_name'] ?? '---') . "</td>";
                echo "<td>" . Html::convDateTime($data['date_entered']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='center'>" . __('No history found', 'flow') . "</td></tr>";
        }

        echo "</tbody></table>";
        echo "</div>";
    }
}
