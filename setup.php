<?php
if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

use Glpi\Plugin\Hooks;

define('PLUGIN_FLOW_VERSION', '1.0.0');

function plugin_init_flow()
{
   global $PLUGIN_HOOKS;

   $PLUGIN_HOOKS['csrf_compliant']['flow'] = true;

   // Register classes (GLPI will load inc files)
   $PLUGIN_HOOKS['add_javascript']['flow'] = [];
   $PLUGIN_HOOKS['menu_toadd']['flow'] = ['tools' => 'PluginFlowFlow'];
   $PLUGIN_HOOKS['menu_toadd']['flow'] = ['plugins' => 'PluginFlowFlow'];
   // // Register events for GLPI 11 dispatcher
   // $PLUGIN_HOOKS['events']['flow'] = [
   //    'Ticket' => [
   //       'add'    => ['\Glpi\Plugin\Flow\Listener', 'onTicketAdd'],
   //       'update' => ['\Glpi\Plugin\Flow\Listener', 'onTicketUpdate'],
   //    ]
   // ];

   // Simple PSR-4 Autoloader for Glpi\Plugin\Flow namespace
   spl_autoload_register(function ($class) {
      $prefix = 'Glpi\\Plugin\\Flow\\';
      $base_dir = GLPI_ROOT . '/marketplace/flow/inc/';

      $len = strlen($prefix);
      if (strncmp($prefix, $class, $len) !== 0) {
         return;
      }

      $relative_class = substr($class, $len);
      $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';

      if (file_exists($file)) {
         require_once $file;
      }
   });

   // Ensure the listener class is available
   if (file_exists(GLPI_ROOT . '/marketplace/flow/inc/listener.class.php')) {
      require_once GLPI_ROOT . '/marketplace/flow/inc/listener.class.php';
   }
   $PLUGIN_HOOKS[Hooks::ITEM_ADD]['flow'] = [
      Ticket::class => [\Glpi\Plugin\Flow\Listener::class, 'onTicketAdd']
   ];

   $PLUGIN_HOOKS[Hooks::PRE_ITEM_UPDATE]['flow'] = [
      Ticket::class => [\Glpi\Plugin\Flow\Listener::class, 'onTicketPreUpdate']
   ];
}

function plugin_version_flow()
{
   return [
      'name'           => 'Flow',
      'version'        => PLUGIN_FLOW_VERSION,
      'author'         => 'Gabriel Augusto Xavier',
      'license'        => 'GPLv2+',
      'homepage'       => 'https://github.com/biel-xavier/glpi-flow',
      'minGlpiVersion' => '11.0.0',
   ];
}

function plugin_flow_check_prerequisites()
{
   if (version_compare(GLPI_VERSION, '11.0.0', 'lt')) {
      echo "This plugin requires GLPI >= 11.0.0";
      return false;
   }
   return true;
}

function plugin_flow_check_config($verbose = false)
{
   return true;
}
