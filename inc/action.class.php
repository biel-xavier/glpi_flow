<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowAction extends CommonDBTM {
   
   static $rightname = 'plugin_flow';

   static function getTypeName($nb = 0) {
      return _n('Action', 'Actions', $nb, 'flow');
   }

   static function canCreate(): bool {
        return Session::haveRight(static::$rightname, UPDATE);
   }

   static function canView(): bool {
       return Session::haveRight(static::$rightname, READ);
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() === 'PluginFlowStep') {
         return _n('Action', 'Actions', 2, 'flow');
      }

      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() === 'PluginFlowStep') {
         self::showForStep($item);
      }

      return true;
   }

   private function getActionTypeOptions(): array
   {
      global $DB;

      $options = [];
      foreach ($DB->request([
         'SELECT' => ['name'],
         'FROM' => 'glpi_plugin_flow_action_types',
         'ORDER' => 'name ASC'
      ]) as $row) {
         $options[$row['name']] = $row['name'];
      }

      return $options;
   }

   public static function showForStep(PluginFlowStep $step): void
   {
      global $DB;

      if (!self::canView()) {
         return;
      }

      $stepId = (int) $step->getID();

      echo "<div class='center'>";
      echo "<form method='post' action='" . self::getFormURL() . "'>";
      echo "<table class='tab_cadre_fixwidth'>";
      echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Add New Action', 'flow') . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Action Type') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('action_type', (new self())->getActionTypeOptions());
      echo "</td>";
      echo "<td>" . __('Order') . "</td>";
      echo "<td>";
      Dropdown::showNumber('action_order', ['value' => 0]);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Configuration (JSON)') . "</td>";
      echo "<td colspan='3'><textarea name='action_config' cols='80' rows='6'>{}</textarea></td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type='hidden' name='plugin_flow_steps_id' value='" . $stepId . "'>";
      echo "<input type='submit' name='add' value='" . _sx('button', 'Add') . "' class='btn btn-primary'>";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";

      echo "<div class='spaced'><table class='tab_cadre_fixwidth'>";
      echo "<tr><th>ID</th><th>" . __('Action Type') . "</th><th>" . __('Order') . "</th></tr>";

      foreach ($DB->request([
         'FROM'  => 'glpi_plugin_flow_actions',
         'WHERE' => ['plugin_flow_steps_id' => $stepId],
         'ORDER' => 'action_order ASC'
      ]) as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . (int) $data['id'] . "</td>";
         echo "<td><a href='" . self::getFormURLWithID($data['id']) . "'>" . htmlescape($data['action_type']) . "</a></td>";
         echo "<td>" . (int) $data['action_order'] . "</td>";
         echo "</tr>";
      }

      echo "</table></div>";
   }
   
   function showForm($ID, $options = []) {
     $this->initForm($ID, $options);
     $this->showFormHeader($options);

     $stepId = $options['plugin_flow_steps_id'] ?? $this->fields['plugin_flow_steps_id'] ?? 0;

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Action Type') . "</td>";
     echo "<td>";
     Dropdown::showFromArray('action_type', $this->getActionTypeOptions(), ['value' => $this->fields['action_type']]);
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

     echo "<input type='hidden' name='plugin_flow_steps_id' value='" . (int) $stepId . "'>";

     $this->showFormButtons($options);
     return true;
   }
}
