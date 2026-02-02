<?php

use Glpi\Plugin\Flow\Api;

// Checks if running via GLPI Controller
if (!defined('GLPI_ROOT')) {
    define('GLPI_ROOT', realpath(__DIR__ . '/../../..'));
    define('GLPI_AJAX', 1);
    include_once(GLPI_ROOT . '/inc/includes.php');
} else {
    // Controller case: Ensure AJAX constant if not set
    if (!defined('GLPI_AJAX')) define('GLPI_AJAX', 1);
}

// Check if user is logged in
\Toolbox::logInFile('flow-debug', "API accessed. $_SERVER[REQUEST_URI]");
try {
    \Session::checkLoginUser();
    \Toolbox::logInFile('flow-debug', "Session check passed.");
} catch (\Exception $e) {
    \Toolbox::logInFile('flow-debug', "Session check failed: " . $e->getMessage());
    throw $e;
}

$api = new Api();
$api->handleRequest();
