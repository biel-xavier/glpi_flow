<?php
include_once('../../../inc/includes.php');

// Check if user is logged in
\Session::checkLoginUser();

// Check if user has permission to access Flow
\Session::checkRight('plugin_flow', READ);

// Redirect to the standalone React app in the public folder
$root_doc = isset($CFG_GLPI) && isset($CFG_GLPI['root_doc']) ? $CFG_GLPI['root_doc'] : '/glpi';
header("Location: " . "/flow/index.html");
exit();
