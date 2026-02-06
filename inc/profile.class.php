<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginFlowProfile extends Profile
{
    public function getTabNameForItem(CommonGLPI $item, $withtemplate = 0)
    {
        if ($item instanceof Profile) {
            return __('Flow', 'flow');
        }
        return '';
    }

    public static function displayTabContentForItem(CommonGLPI $item, $tabnum = 1, $withtemplate = 0)
    {
        if ($item instanceof Profile) {
            $profile_obj = new self();
            $profile_obj->showForm($item->getID());
        }
        return true;
    }

    public function showForm($ID, array $options = [])
    {
        global $DB;

        if (!Session::haveRight('profile', READ)) {
            return false;
        }

        echo "<div class='spaced'>";
        
        $profile = new Profile();
        $profile->getFromDB($ID);

        $canedit = Session::haveRightsOr('profile', [CREATE, UPDATE, PURGE]);

        if ($canedit) {
            echo "<form method='post' action='" . $profile->getFormURL() . "'>";
        }

        // Define rights array
        $rights = [
            [
                'itemtype' => 'PluginFlowFlow',
                'label'    => __('Flow', 'flow'),
                'field'    => 'plugin_flow',
            ],
        ];

        $matrix_options = [
            'title' => __('Flow', 'flow'),
            'canedit' => $canedit,
        ];

        // Display rights matrix
        $profile->displayRightsChoiceMatrix($rights, $matrix_options);

        if ($canedit) {
            echo "<div class='center'>";
            echo Html::hidden('id', ['value' => $ID]);
            echo Html::submit(_sx('button', 'Save'), ['name' => 'update']);
            echo "</div>\n";
            Html::closeForm();
        }

        echo "</div>";

        return true;
    }
}

