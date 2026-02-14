<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowStep extends CommonDBTM
{

   static $rightname = 'config';

   static function getTypeName($nb = 0)
   {
      return _n('Step', 'Steps', $nb, 'flow');
   }

   static function canCreate(): bool
   {
      return Session::haveRight('config', UPDATE);
   }

   static function canStart(): bool
   {
      return true;
   }

   static function canView(): bool
   {
      return Session::haveRight('config', READ);
   }

   public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
   {
      if ($item->getType() == 'PluginFlowFlow') {
         return _n('Step', 'Steps', 2, 'flow');
      }
      return '';
   }

   public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
   {
      if ($item->getType() == 'PluginFlowFlow') {
         self::showForFlow($item);
      }
      return true;
   }

   /**
    * Display steps for a specific Flow
    */
   static function showForFlow($flow)
   {
      global $DB;

      $ID = $flow->fields['id'];

      if (!PluginFlowFlow::canUpdate()) {
         return;
      }

      echo "<div class='center'>";
      // Form to add a new step
      echo "<form method='post' action='" . PluginFlowStep::getFormURL() . "'>";
      echo "<table class='tab_cadre_fixwidth'>";
      echo "<tr class='tab_bg_1'><th colspan='3'>Add New Step</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>Name</td>";
      echo "<td><input type='text' name='name' value=''></td>";
      echo "<td>";
      echo "<input type='hidden' name='plugin_flow_flows_id' value='$ID'>";
      echo "<input type='submit' name='add' value='" . _sx('button', 'Add') . "' class='btn btn-primary'>";
      echo "</td>";
      echo "</tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";

      // List existing steps
      echo "<div class='spaced'>";
      echo "<table class='tab_cadre_fixwidth'>";
      echo "<tr><th>ID</th><th>Name</th><th>Type</th><th>Actions</th></tr>";

      $iterator = $DB->request([
         'FROM' => 'glpi_plugin_flow_steps',
         'WHERE' => ['plugin_flow_flows_id' => $ID]
      ]);

      foreach ($iterator as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . $data['id'] . "</td>";
         echo "<td><a href='" . PluginFlowStep::getFormURLWithID($data['id']) . "'>" . $data['name'] . "</a></td>";
         echo "<td>" . $data['step_type'] . "</td>";
         echo "<td>";
         // Delete button (simplified)
         echo "</td>";
         echo "</tr>";
      }
      echo "</table></div>";
   }

   function defineTabs($options = [])
   {
      $ong = [];
      $this->addDefaultFormTab($ong);
      // Tabs for Transitions/Validations/Actions can go here
      return $ong;
   }

   function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      $flow_id = $this->fields['plugin_flow_flows_id'];

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td rowspan='4' class='middle right'>" . __('Comments') . "</td>";
      echo "<td class='center middle' rowspan='4'><textarea cols='45' rows='5' name='comment' >" .
         $this->fields["comment"] . "</textarea></td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Step Type') . "</td>";
      echo "<td>";
      $types = [
         'Initial' => 'Initial',
         'Common' => 'Common', // Replaces ACTION
         'Condition' => 'Condition',
         'Request' => 'Request',
         'End' => 'End'
      ];
      Dropdown::showFromArray('step_type', $types, ['value' => $this->fields['step_type']]);
      echo "</td></tr>";

      echo "<input type='hidden' name='plugin_flow_flows_id' value='$flow_id'>";

      $this->showFormButtons($options);
      return true;
   }
}
