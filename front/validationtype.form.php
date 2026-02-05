<?php
include '../../../inc/includes.php';

$obj = new PluginFlowValidationType();

if (isset($_POST["add"])) {
   $obj->check(-1, CREATE, $_POST);
   if ($newID = $obj->add($_POST)) {
       Html::redirect($obj->getFormURLWithID($newID, false));
   }
   Html::back();
} else if (isset($_POST["update"])) {
   $obj->check($_POST["id"], UPDATE);
   $obj->update($_POST);
   Html::back();
} else if (isset($_POST["purge"])) {
   $obj->check($_POST["id"], PURGE);
   $obj->delete($_POST, 1);
   $obj->redirectToList();
}

Html::header($obj->getTypeName(Session::getPluralNumber()), $_SERVER['PHP_SELF'], "plugins", "plugin_flow");
$obj->display($_GET);
Html::footer();
