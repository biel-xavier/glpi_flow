<?php
// Debug endpoint to return metadata (entities and categories) to a logged-in user
define('GLPI_ROOT', realpath(__DIR__ . '/../../..'));
include_once(GLPI_ROOT . '/inc/includes.php');

// Require logged user
\Session::checkLoginUser();

header('Content-Type: application/json');

global $DB;

try {
    $entities = [];
    $iter = $DB->request([
        'SELECT' => ['id','name','completename'],
        'FROM'   => getTableForItemType('Entity'),
        'ORDER'  => 'completename ASC'
    ]);
    foreach ($iter as $e) { $entities[] = $e; }

    $categories = [];
    $iter = $DB->request([
        'SELECT' => ['id','name','completename'],
        'FROM'   => getTableForItemType('ITILCategory'),
        'ORDER'  => 'completename ASC'
    ]);
    foreach ($iter as $c) { $categories[] = $c; }

    echo json_encode(['ok' => true, 'entities' => $entities, 'categories' => $categories]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'error' => $e->getMessage()]);
}
