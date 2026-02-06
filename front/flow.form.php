<?php
include '../../../inc/includes.php';

Session::checkRight('plugin_flow', READ);

$flow = new PluginFlowFlow();

// Handle form submission (create/update)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
   // Validate JSON payload before saving
   if (isset($_POST['json_data'])) {
      $json = trim($_POST['json_data']);
      if ($json !== '') {
         json_decode($json, true);
         if (json_last_error() !== JSON_ERROR_NONE) {
            Session::addMessageAfterRedirect(sprintf(__('JSON inválido: %s', 'flow'), json_last_error_msg()), false, ERROR);
            Html::redirect($_SERVER['REQUEST_URI']);
         }
      }
   }
   // Update existing
   if (!empty($_POST['id'])) {
      $id = (int)$_POST['id'];
      // if (!$flow->can($id, UPDATE)) {
      //    Html::displayRightError();
      // }
      $input = $_POST;
      $input['id'] = $id;
      $res = $flow->update($input);
      if ($res) {
         Html::redirect(PluginFlowFlow::getFormURLWithID($id, false));
      }
      // on failure, fall through to display form with messages
   } else {
      // Create new
      // if (!$flow->can(-1, CREATE)) {
      //    Html::displayRightError();
      // }
      $input = $_POST;
      $newid = $flow->add($input);
      if ($newid) {
         Html::redirect(PluginFlowFlow::getFormURLWithID($newid, false));
      }
      // on failure, fall through
   }
}

Html::header(PluginFlowFlow::getTypeName(2), $_SERVER['PHP_SELF'], 'plugins', 'plugin_flow');
echo '<div class="container">';
$flow->display();
echo '</div>';
Html::footer();
