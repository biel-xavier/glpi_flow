<?php
if (!defined('GLPI_ROOT')) {
    die("Sorry. You can't access directly to this file");
}

class PluginFlowProfile extends Profile
{
    public static $rightname = 'profile';

    public static function getAllRights(): array
    {
        return [
            [
                'itemtype' => 'PluginFlowFlow',
                'label'    => __('Flow', 'flow'),
                'field'    => 'plugin_flow',
            ],
            [
                'itemtype' => 'PluginFlowHistory',
                'label'    => __('Flow History', 'flow'),
                'field'    => 'plugin_flow_history',
                'rights'   => [READ => __('Read')],
            ],
        ];
    }

    public static function addDefaultProfileInfos(int $profiles_id, array $rights): void
    {
        $profileRight = new ProfileRight();

        foreach ($rights as $right => $value) {
            if (!countElementsInTable('glpi_profilerights', [
                'profiles_id' => $profiles_id,
                'name'        => $right,
            ])) {
                $profileRight->add([
                    'profiles_id' => $profiles_id,
                    'name'        => $right,
                    'rights'      => $value,
                ]);

                $_SESSION['glpiactiveprofile'][$right] = $value;
            }
        }
    }

    public static function createFirstAccess(int $profiles_id): void
    {
        self::addDefaultProfileInfos($profiles_id, [
            'plugin_flow'         => ALLSTANDARDRIGHT,
            'plugin_flow_history' => READ,
        ]);
    }

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
            self::addDefaultProfileInfos($item->getID(), [
                'plugin_flow'         => 0,
                'plugin_flow_history' => 0,
            ]);

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

        $matrix_options = [
            'title' => __('Flow', 'flow'),
            'canedit' => $canedit,
        ];

        // Display rights matrix
        $profile->displayRightsChoiceMatrix(self::getAllRights(), $matrix_options);

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
