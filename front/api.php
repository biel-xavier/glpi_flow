<?php

use Glpi\Plugin\Flow\Api;

define('GLPI_ROOT', realpath(__DIR__ . '/../../..'));
include_once(GLPI_ROOT . '/inc/includes.php');

// Check if user is logged in
\Session::checkLoginUser();

$api = new Api();
$api->handleRequest();
