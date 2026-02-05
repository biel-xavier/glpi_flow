<?php

use Glpi\Plugin\Flow\Api;

define('GLPI_ROOT', realpath(__DIR__ . '/../../..'));
include_once(GLPI_ROOT . '/inc/includes.php');

// Allow localhost requests for server-side debugging, otherwise require login
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'])) {
    \Session::checkLoginUser();
}

header('Content-Type: application/json');

try {
    global $DB;

    $actionTypes = array_values(iterator_to_array($DB->request(['FROM' => 'glpi_plugin_flow_action_types'])));
    $validationTypes = array_values(iterator_to_array($DB->request(['FROM' => 'glpi_plugin_flow_validation_types'])));

    foreach ($actionTypes as &$at) {
        $at['action_type'] = $at['name'];
        $at['config_schema'] = json_decode($at['config_schema'], true);
    }
    foreach ($validationTypes as &$vt) {
        $vt['validation_type'] = $vt['name'];
        $vt['config_schema'] = json_decode($vt['config_schema'], true);
    }

    // Entities
    $entities = [];
    $table = getTableForItemType('Entity');
    $iter = $DB->request([
        'SELECT' => ['id', 'name', 'completename'],
        'FROM' => $table,
        'ORDER' => 'completename ASC'
    ]);
    foreach ($iter as $r) { $entities[] = $r; }

    // ITIL categories
    $categories = [];
    $table = getTableForItemType('ITILCategory');
    $iter = $DB->request([
        'SELECT' => ['id', 'name', 'completename'],
        'FROM' => $table,
        'ORDER' => 'completename ASC'
    ]);
    foreach ($iter as $r) { $categories[] = $r; }

    // Users
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

    // Groups
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

    // Task templates
    $templates = [];
    $iterator = $DB->request([
        'SELECT' => ['id', 'name'],
        'FROM' => 'glpi_tasktemplates',
        'ORDER' => 'name ASC'
    ]);
    foreach ($iterator as $data) {
        $templates[] = [ 'id' => $data['id'], 'name' => $data['name'] ];
    }

    echo json_encode([
        'csrf_token' => \Session::getNewCSRFToken(),
        'action_types' => $actionTypes,
        'validation_types' => $validationTypes,
        'entities' => $entities,
        'categories' => $categories,
        'users' => $users,
        'groups' => $groups,
        'task_templates' => $templates
    ]);
} catch (\Throwable $t) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => $t->getMessage(),
        'trace' => $t->getTraceAsString()
    ]);
}

exit;
