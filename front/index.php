<?php
// Flow Manager React App Wrapper
// This file is accessed via GLPI's routing and loads the React app

define('GLPI_ROOT', realpath(__DIR__ . '/../../..'));
define('DO_NOT_CHECK_HTTP_REFERER', true);

try {
    include_once(GLPI_ROOT . '/inc/includes.php');
    \Session::checkLoginUser();
} catch (\Exception $e) {
    // If session fails, redirectto login
    header('Location: /glpi/front/login.php');
    exit;
}

// Return React app HTML with proper asset paths
$jsFile = 'index-CoLKsc-C.js'; // Match your build output
$cssFile = 'index-DpX3bnri.css'; // Match your build output
?>
<!DOCTYPE html>
<html lang="<?php echo $_SESSION['glpidefault_language'] ?? 'pt'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Flow Manager</title>
    <link rel="stylesheet" href="/glpi/public/flow/assets/<?php echo $cssFile; ?>">
</head>
<body>
    <div id="root"></div>
    <script type="module" src="/glpi/public/flow/assets/<?php echo $jsFile; ?>"></script>
</body>
</html>
