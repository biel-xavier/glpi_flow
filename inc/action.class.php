<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowAction extends CommonDBTM {
   
   static $rightname = 'config';

   static function getTypeName($nb = 0) {
      return _n('Action', 'Actions', $nb, 'flow');
   }

   static function canCreate(): bool {
        return Session::haveRight('config', UPDATE);
   }

   static function canView(): bool {
       return Session::haveRight('config', READ);
   }
   
   function showForm($ID, $options = []) {
     $this->initForm($ID, $options);
     $this->showFormHeader($options);

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Action Type') . "</td>";
     echo "<td>";
     // Dropdown for Action Type
     Dropdown::show('PluginFlowActionType', ['value' => $this->fields['action_type'], 'name' => 'action_type']);
     echo "</td></tr>";

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Order') . "</td>";
     echo "<td>";
     Dropdown::showNumber('action_order', ['value' => $this->fields['action_order']]);
     echo "</td></tr>";

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Configuration (JSON)') . "</td>";
     echo "<td><textarea name='action_config' cols='80' rows='10'>" . $this->fields['action_config'] . "</textarea></td>";
     echo "</tr>";

     $this->showFormButtons($options);
     return true;
   }
   
   public function execute($ticket) {
       // Logic to execute action based on action_type and params
   }
}
