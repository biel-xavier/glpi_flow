<?php

if (!defined('GLPI_ROOT')) {
   die("Sorry. You can't access this file directly");
}

class PluginFlowValidation extends CommonDBTM {
   
   static $rightname = 'config';

   static function getTypeName($nb = 0) {
      return _n('Validation', 'Validations', $nb, 'flow');
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
     echo "<td>" . __('Validation Type') . "</td>";
     echo "<td>";
     Dropdown::show('PluginFlowValidationType', ['value' => $this->fields['validation_type'], 'name' => 'validation_type']);
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
     
     $this->showFormButtons($options);
   }
}