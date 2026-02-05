<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowTransition extends CommonDBTM {
   
   static $rightname = 'config';

   static function getTypeName($nb = 0) {
      return _n('Transition', 'Transitions', $nb, 'flow');
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

      // Logic to select Source and Target Steps would go here
      // For now, keeping it simple
      $step_id = $options['plugin_flow_steps_id_source'] ?? $this->fields['plugin_flow_steps_id_source'];
      echo "<input type='hidden' name='plugin_flow_steps_id_source' value='$step_id'>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Target Step') . "</td>";
      echo "<td>";
      // Dropdown of other steps in the same flow
      // This requires finding the flow_id from the step... 
      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Transition Type') . "</td>";
      echo "<td>";
      $types = [
         'default' => 'Default',
         'condition_positive' => 'Condition Positive',
         'condition_negative' => 'Condition Negative'
      ];
      Dropdown::showFromArray('transition_type', $types, ['value' => $this->fields['transition_type']]);
      echo "</td></tr>";



      $this->showFormButtons($options);
      return true;
   }
}
