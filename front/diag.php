<?php
include('../../../inc/includes.php');
$plugin_web_dir = \Plugin::getWebDir('flow');
$root_doc = $CFG_GLPI['root_doc'];
$data = [
    'plugin_web_dir' => $plugin_web_dir,
    'root_doc' => $root_doc,
    'glpi_root' => GLPI_ROOT,
    'cwd' => getcwd(),
    'script_name' => $_SERVER['SCRIPT_NAME'],
];
file_put_contents(GLPI_ROOT . '/marketplace/flow/web_paths_diag.json', json_encode($data, JSON_PRETTY_PRINT));
echo "Diagnostic data written to web_paths_diag.json";
