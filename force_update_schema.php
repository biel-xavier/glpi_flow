<?php
define('GLPI_ROOT', '/var/www/glpi');
include(GLPI_ROOT . "/inc/includes.php");

// Re-run the install logic to trigger the update
include(GLPI_ROOT . "/marketplace/flow/hook.php");
plugin_flow_install();

echo "Plugin Flow install/update logic executed.\n";
