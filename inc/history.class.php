<?php

if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access this file directly");
}

use Glpi\Plugin\Flow\Repository\FlowRepository;
use Glpi\Plugin\Flow\Repository\StepHistoryRepository;

class PluginFlowHistory extends CommonGLPI
{
    static $rightname = 'plugin_flow_history';

    public static function getIcon()
    {
        return 'ti ti-history';
    }

    public static function getRights($interface = 'central')
    {
        return [
            READ   => __('Read'),
        ];
    }

    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        $categoryId = (int) ($item->fields['itilcategories_id'] ?? 0);

        if (
            $item->getType() === 'Ticket'
            && Session::haveRight(self::$rightname, READ)
            && (new FlowRepository())->hasActiveFlowForCategory($categoryId)
        ) {
            return self::createTabEntry(
                __('Histórico do Flow', 'flow'),
                0,
                $item::getType(),
                self::getIcon()
            );
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
        $ticket_id = $ticket->getID();
        $timeline = (new StepHistoryRepository())->findTimeline($ticket_id);

        echo "<div class='container-fluid'>";
        echo "<div class='row mb-2'>";
        echo "<div class='col-12 d-flex justify-content-end'>";
        echo "<a href='/marketplace/flow/front/export.php?tickets_id=$ticket_id&type=csv' class='btn btn-outline-secondary btn-sm me-2'><i class='fas fa-file-csv'></i> CSV</a>";
        echo "<a href='/marketplace/flow/front/export.php?tickets_id=$ticket_id&type=xls' class='btn btn-outline-secondary btn-sm'><i class='fas fa-file-excel'></i> XLS</a>";
        echo "</div>";
        echo "</div>";

        echo "<table class='table table-hover'>";
        echo "<thead><tr>";
        echo "<th>" . __('Fluxo', 'flow') . "</th>";
        echo "<th>" . __('Etapa', 'flow') . "</th>";
        echo "<th>" . __('Data de entrada', 'flow') . "</th>";
        echo "</tr></thead>";
        echo "<tbody>";

        if (count($timeline) > 0) {
            foreach ($timeline as $data) {
                echo "<tr>";
                echo "<td>" . htmlescape($data['flow_name'] ?? '---') . "</td>";
                echo "<td>" . htmlescape($data['step_name'] ?? '---') . "</td>";
                echo "<td>" . Html::convDateTime($data['date_entered']) . "</td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='3' class='center'>" . __('Nenhum histórico encontrado', 'flow') . "</td></tr>";
        }

        echo "</tbody></table>";
        echo "</div>";
    }
}
