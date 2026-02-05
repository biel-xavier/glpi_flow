<?php
define('GLPI_ROOT', '/var/www/glpi');
include(GLPI_ROOT . "/inc/includes.php");

global $DB;

$schema = json_encode([
    'type' => 'object',
    'properties' => [
        'url' => ['type' => 'string'],
        'method' => ['type' => 'string', 'enum' => ['GET', 'POST', 'PUT', 'DELETE'], 'default' => 'POST'],
        'is_internal' => ['type' => 'boolean', 'default' => false, 'description' => 'If true, sends current session cookies (for GLPI internal API)'],
        'headers' => ['type' => 'string', 'format' => 'json', 'description' => 'JSON Object of headers'],
        'body' => ['type' => 'string', 'format' => 'json', 'description' => 'JSON Body content']
    ],
    'required' => ['url']
]);

$DB->update(
    'glpi_plugin_flow_action_types',
    [
        'config_schema' => $schema
    ],
    ['name' => 'REQUEST_HTTP']
);

echo "Updated REQUEST_HTTP schema manually.\n";
