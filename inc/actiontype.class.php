<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowActionType extends CommonDBTM {
   
   static $rightname = 'config';

   static function getTypeName($nb = 0) {
      return _n('Action Type', 'Action Types', $nb, 'flow');
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
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Description') . "</td>";
      echo "<td><textarea name='description' cols='50' rows='3'>" . $this->fields['description'] . "</textarea></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Config Schema (JSON)') . "</td>";
      echo "<td><textarea name='config_schema' cols='80' rows='10'>" . $this->fields['config_schema'] . "</textarea></td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }
}
