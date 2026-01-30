<?php
include('../../../inc/includes.php');

// Check if user is logged in
\Session::checkLoginUser();

// Redirect to the standalone React app in the public folder
header("Location: " . $CFG_GLPI['root_doc'] . "/flow/index.html");
exit();
