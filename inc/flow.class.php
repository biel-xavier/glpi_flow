<?php
if (!defined('GLPI_ROOT')) {
   die('Sorry. You can\'t access this file directly');
}

class PluginFlowFlow extends CommonDBTM
{
   static $rightname = 'config';

   public function defineTabs($options = [])
   {
        $ong = [];
        $this->addDefaultFormTab($ong);
        $this->addStandardTab('PluginFlowStep', $ong, $options);

        return $ong;
   }

   public static function getTypeName($nb = 0)
   {
      return _n('Flow', 'Flows', $nb, 'flow');
   }

   public static function getMenuName()
   {
      return __('Flows', 'flow');
   }

   public static function getIcon()
   {
      return 'fa-project-diagram';
   }

   public static function getFormURL($full = true)
   {
      global $CFG_GLPI;
      $base = '';
      if ($full && isset($CFG_GLPI['root_doc'])) {
         $base = $CFG_GLPI['root_doc'];
      }
      return $base . '/marketplace/flow/front/flow.form.php';
   }

   public static function showSearchStatusArea()
   {
      if (static::canCreate()) {
         echo "<div class='me-2'>";
         echo "<a class='btn btn-primary btn-sm' href='" . htmlescape(static::getFormURLWithID(-1, false)) . "'>";
         echo "<i class='ti ti-plus'></i>&nbsp;" . __('Add') . "</a>";
         echo "</div>";
      }
   }

   public static function canUpdate(): bool
   {
      return Session::haveRight('config', UPDATE);
   }

   public static function canCreate(): bool
   {
      return Session::haveRight('config', CREATE);
   }

   public static function canView(): bool
   {
      return Session::haveRight('config', READ);
   }

   public function rawSearchOptions()
   {
      $tab = parent::rawSearchOptions();

      $tab[] = [
         'id'       => 10,
         'table'    => static::getTable(),
         'field'    => 'id',
         'name'     => __('ID'),
         'datatype' => 'number',
      ];

      $tab[] = [
         'id'       => 11,
         'table'    => static::getTable(),
         'field'    => 'name',
         'name'     => __('Name'),
         'datatype' => 'string',
      ];

      $tab[] = [
         'id'       => 12,
         'table'    => Entity::getTable(),
         'field'    => 'completename',
         'linkfield' => 'entities_id',
         'name'     => Entity::getTypeName(1),
         'datatype' => 'dropdown',
      ];

      $tab[] = [
         'id'       => 13,
         'table'    => ITILCategory::getTable(),
         'field'    => 'completename',
         'linkfield' => 'itilcategories_id',
         'name'     => __('Ticket Category'),
         'datatype' => 'dropdown',
      ];

      $tab[] = [
         'id'       => 14,
         'table'    => static::getTable(),
         'field'    => 'is_active',
         'name'     => __('Active'),
         'datatype' => 'bool',
      ];

      return $tab;
   }

   function showForm($ID, $options = [])
   {
      $this->initForm($ID, $options);
      $this->showFormHeader($options);

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Name') . "</td>";
      echo "<td>";
      Html::autocompletionTextField($this, "name");
      echo "</td>";
      echo "<td>" . __('Active') . "</td>";
      echo "<td>";
      Html::showCheckbox(['name' => 'is_active', 'checked' => $this->fields['is_active']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td>" . __('Entity') . "</td>";
      echo "<td>";
      Entity::dropdown(['value' => $this->fields['entities_id']]);
      echo "</td>";
      echo "<td>" . __('Ticket Category') . "</td>";
      echo "<td>";
      ITILCategory::dropdown(['value' => $this->fields['itilcategories_id']]);
      echo "</td>";
      echo "</tr>";

      echo "<tr class='tab_bg_1'>";
      echo "<td class='middle right'>" . __('Comments') . "</td>";
      echo "<td class='center middle' colspan='3'><textarea cols='100' rows='5' name='comment' >" .
           $this->fields["comment"] . "</textarea></td>";
      echo "</tr>";

      $this->showFormButtons($options);
      return true;
   }
}
