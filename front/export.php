<?php

include_once('../../../inc/includes.php');

// 1. Authenticate and check rights
\Session::checkLoginUser();
if (!\Session::haveRight('plugin_flow_history', READ)) {
    Html::displayRightError();
}

$tickets_id = (int)($_GET['tickets_id'] ?? 0);
$type       = $_GET['type'] ?? 'csv'; // csv or xls

if ($tickets_id <= 0) {
    die("Invalid Ticket ID");
}

global $DB;

// 2. Fetch data
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
    'WHERE'     => ['h.tickets_id' => $tickets_id],
    'ORDER'     => 'h.date_entered DESC'
]);

$filename = "flow_history_ticket_$tickets_id" . ($type === 'xls' ? '.xls' : '.csv');

// 3. Output Headers
if ($type === 'xls') {
    header("Content-Type: application/vnd.ms-excel");
    header("Content-Disposition: attachment; filename=\"$filename\"");
    header("Pragma: no-cache");
    header("Expires: 0");

    echo "<table border='1'>";
    echo "<tr>";
    echo "<th>" . __('Flow', 'flow') . "</th>";
    echo "<th>" . __('Step', 'flow') . "</th>";
    echo "<th>" . __('Date Entered', 'flow') . "</th>";
    echo "</tr>";

    foreach ($iterator as $data) {
        echo "<tr>";
        echo "<td>" . $data['flow_name'] . "</td>";
        echo "<td>" . $data['step_name'] . "</td>";
        echo "<td>" . $data['date_entered'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    header("Content-Type: text/csv; charset=UTF-8");
    header("Content-Disposition: attachment; filename=\"$filename\"");

    $output = fopen('php://output', 'w');
    // Add BOM for Excel UTF-8 support
    fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));

    fputcsv($output, [
        __('Flow', 'flow'),
        __('Step', 'flow'),
        __('Date Entered', 'flow')
    ]);

    foreach ($iterator as $data) {
        fputcsv($output, [
            $data['flow_name'],
            $data['step_name'],
            $data['date_entered']
        ]);
    }
    fclose($output);
}

exit();
