<?php
include('../../../inc/includes.php');

Html::header(PluginFlowFlow::getTypeName(2), $_SERVER['PHP_SELF'], "plugins", "flow");

Search::show('PluginFlowFlow');

Html::footer();