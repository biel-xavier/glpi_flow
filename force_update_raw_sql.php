<?php
$host = 'localhost';
$user = 'glpi_user';
$pass = urldecode('Advanta%2326');
$db   = 'glpi_dev';

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

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

// Escape the schema string
$safeSchema = $conn->real_escape_string($schema);
$name = 'REQUEST_HTTP';

$sql = "UPDATE glpi_plugin_flow_action_types SET config_schema = '$safeSchema' WHERE name = '$name'";

if ($conn->query($sql) === TRUE) {
    echo "Record updated successfully\n";
} else {
    echo "Error updating record: " . $conn->error;
}

$conn->close();
