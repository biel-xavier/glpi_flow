<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowValidation extends CommonDBTM {
   
   static $rightname = 'plugin_flow';

   static function getTypeName($nb = 0) {
      return _n('Validation', 'Validations', $nb, 'flow');
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
         return _n('Validation', 'Validations', 2, 'flow');
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

   private function getValidationTypeOptions(): array
   {
      global $DB;

      $options = [];
      foreach ($DB->request([
         'SELECT' => ['name'],
         'FROM' => 'glpi_plugin_flow_validation_types',
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
      echo "<tr class='tab_bg_1'><th colspan='4'>" . __('Add New Validation', 'flow') . "</th></tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Validation Type') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('validation_type', (new self())->getValidationTypeOptions());
      echo "</td>";
      echo "<td>" . __('Severity') . "</td>";
      echo "<td>";
      Dropdown::showFromArray('severity', [
         'INFO'    => 'Info',
         'WARNING' => 'Warning',
         'BLOCKER' => 'Blocker'
      ], ['value' => 'BLOCKER']);
      echo "</td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'>";
      echo "<td>" . __('Order') . "</td>";
      echo "<td>";
      Dropdown::showNumber('validation_order', ['value' => 0]);
      echo "</td>";
      echo "<td>" . __('Configuration (JSON)') . "</td>";
      echo "<td><textarea name='validation_config' cols='60' rows='6'>{}</textarea></td>";
      echo "</tr>";
      echo "<tr class='tab_bg_2'><td colspan='4' class='center'>";
      echo "<input type='hidden' name='plugin_flow_steps_id' value='" . $stepId . "'>";
      echo "<input type='submit' name='add' value='" . _sx('button', 'Add') . "' class='btn btn-primary'>";
      echo "</td></tr>";
      echo "</table>";
      Html::closeForm();
      echo "</div>";

      echo "<div class='spaced'><table class='tab_cadre_fixwidth'>";
      echo "<tr><th>ID</th><th>" . __('Validation Type') . "</th><th>" . __('Severity') . "</th><th>" . __('Order') . "</th></tr>";

      foreach ($DB->request([
         'FROM'  => 'glpi_plugin_flow_validations',
         'WHERE' => ['plugin_flow_steps_id' => $stepId],
         'ORDER' => 'validation_order ASC'
      ]) as $data) {
         echo "<tr class='tab_bg_1'>";
         echo "<td>" . (int) $data['id'] . "</td>";
         echo "<td><a href='" . self::getFormURLWithID($data['id']) . "'>" . htmlescape($data['validation_type']) . "</a></td>";
         echo "<td>" . htmlescape($data['severity']) . "</td>";
         echo "<td>" . (int) $data['validation_order'] . "</td>";
         echo "</tr>";
      }

      echo "</table></div>";
   }
   
   function showForm($ID, $options = []) {
     $this->initForm($ID, $options);
     $this->showFormHeader($options);

     $stepId = $options['plugin_flow_steps_id'] ?? $this->fields['plugin_flow_steps_id'] ?? 0;

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Validation Type') . "</td>";
     echo "<td>";
     Dropdown::showFromArray('validation_type', $this->getValidationTypeOptions(), ['value' => $this->fields['validation_type']]);
     echo "</td></tr>";

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Severity') . "</td>";
     echo "<td>";
     $severities = [
         'INFO' => 'Info',
         'WARNING' => 'Warning',
         'BLOCKER' => 'Blocker'
     ];
     Dropdown::showFromArray('severity', $severities, ['value' => $this->fields['severity']]);
     echo "</td></tr>";

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Order') . "</td>";
     echo "<td>";
     Dropdown::showNumber('validation_order', ['value' => $this->fields['validation_order']]);
     echo "</td></tr>";

     echo "<tr class='tab_bg_1'>";
     echo "<td>" . __('Configuration (JSON)') . "</td>";
     echo "<td><textarea name='validation_config' cols='80' rows='10'>" . $this->fields['validation_config'] . "</textarea></td>";
     echo "</tr>";

     echo "<input type='hidden' name='plugin_flow_steps_id' value='" . (int) $stepId . "'>";
     
     $this->showFormButtons($options);
     return true;
   }
}
