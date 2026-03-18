<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowTransition extends CommonDBTM {
   
   static $rightname = 'plugin_flow';

   static function getTypeName($nb = 0) {
      return _n('Transition', 'Transitions', $nb, 'flow');
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
         return _n('Transition', 'Transitions', 2, 'flow');
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

   private function getTargetStepOptions(int $sourceStepId): array
   {
      global $DB;

      if ($sourceStepId <= 0) {
         return [];
      }

      $sourceStep = $DB->request([
         'SELECT' => ['plugin_flow_flows_id'],
         'FROM'   => 'glpi_plugin_flow_steps',
         'WHERE'  => ['id' => $sourceStepId],
         'LIMIT'  => 1
      ])->current();

      if (!$sourceStep) {
         return [];
      }

      $options = [];
      foreach ($DB->request([
         'SELECT' => ['id', 'name'],
         'FROM'   => 'glpi_plugin_flow_steps',
         'WHERE'  => [
            'plugin_flow_flows_id' => $sourceStep['plugin_flow_flows_id'],
            'id'                   => ['!=', $sourceStepId]
         ],
         'ORDER'  => 'name ASC'
      ]) as $row) {
         $options[$row['id']] = $row['name'];
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
      echo "<tr class='tab_bg_1'><th colspan='3'>" . __('Add New Transition', 'flow') . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Target Step') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('plugin_flow_steps_id_target', (new self())->getTargetStepOptions($stepId));
      echo "</td>";
      echo "<td>";
      Dropdown::showFromArray('transition_type', [
         'default'            => 'Default',
         'condition_positive' => 'Condition Positive',
         'condition_negative' => 'Condition Negative'
      ], ['value' => 'default']);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'><td colspan='3' class='center'>";
      echo "<input type='hidden' name='plugin_flow_steps_id_source' value='" . $stepId . "'>";
      echo "<input type='submit' name='add' value='" . _sx('button', 'Add') . "' class='btn btn-primary'>";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";

      echo "<div class='spaced'><table class='tab_cadre_fixwidth'>";
      echo "<tr><th>ID</th><th>" . __('Target Step') . "</th><th>" . __('Transition Type') . "</th></tr>";

      foreach ($DB->request([
         'SELECT' => [
            'glpi_plugin_flow_transitions.id',
            'glpi_plugin_flow_transitions.transition_type',
            'glpi_plugin_flow_steps.name AS target_name'
         ],
         'FROM' => 'glpi_plugin_flow_transitions',
         'LEFT JOIN' => [
            'glpi_plugin_flow_steps' => [
               'ON' => [
                  'glpi_plugin_flow_transitions' => 'plugin_flow_steps_id_target',
                  'glpi_plugin_flow_steps'       => 'id'
               ]
            ]
         ],
         'WHERE' => ['plugin_flow_steps_id_source' => $stepId]
      ]) as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . (int) $data['id'] . "</td>";
         echo "<td><a href='" . self::getFormURLWithID($data['id']) . "'>" . htmlescape($data['target_name'] ?? '') . "</a></td>";
         echo "<td>" . htmlescape($data['transition_type']) . "</td>";
         echo "</tr>";
      }

      echo "</table></div>";
   }

   function showForm($ID, $options = []) {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);
      $stepId = (int) ($options['plugin_flow_steps_id_source'] ?? $this->fields['plugin_flow_steps_id_source'] ?? 0);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Source Step') . "</td>";
      echo "<td>" . (int) $stepId . "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Target Step') . "</td>";
      echo "<td>";
      Dropdown::showFromArray(
         'plugin_flow_steps_id_target',
         $this->getTargetStepOptions($stepId),
         ['value' => $this->fields['plugin_flow_steps_id_target'] ?? 0]
      );
      echo "</td>";
      echo "</tr>";

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
      echo "<input type='hidden' name='plugin_flow_steps_id_source' value='" . $stepId . "'>";

      $this->showFormButtons($options);
      return true;
   }
}
