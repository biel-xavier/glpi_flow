<?php

namespace Glpi\Plugin\Flow;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

use CommonITILObject;
use PluginFlowFlow;
use DBmysql;
use Toolbox;
use User;
use UserEmail;
use Glpi\Plugin\Flow\Actions\ActionFactory;
use Glpi\Plugin\Flow\Validations\ValidationFactory;

class Listener
{
    private static $already_processed = false;

    public static function onTicketAdd(CommonITILObject $item)
    {

        Toolbox::logInFile('php-errors', 'Ticket created: ' . $item->getID());
        // 1. Check if matches a Flow
        $entityId = $item->fields['entities_id'];
        $catId = $item->fields['itilcategories_id'];

        $flow = self::getMatchingFlow($entityId, $catId);
        if (!$flow) return;

        // 2. Find Initial Step
        $initialStep = self::getInitialStep($flow['id']);
        if (!$initialStep) return;

        // 3. Register State
        self::createTicketState($item->getID(), $flow['id'], $initialStep['id']);
        $state = self::getTicketState($item->getID());

        // 4. Run Actions for Initial Step
        self::runStepActions($initialStep['id'], $item);

        // 5. Check auto-transition (if Initial step is a Condition, or has a default transition)
        self::processAutoTransitions($item, $state, $initialStep['id']);
    }


    public static function onTicketPreUpdate(CommonITILObject $item)
    {
        if (self::$already_processed) {
            return;
        }

        // 1. Get Current State
        $state = self::getTicketState($item->getID());
        if (!$state) return; // Ticket not in a flow

        $currentStepId = $state['plugin_flow_steps_id'];

        // 2. Check Validations (Manual Update trigger)
        // Only blockers are checked here because it's a manual update.
        $failedValidations = [];
        if (!self::checkValidations($currentStepId, $item, $failedValidations, true)) {
            $failedValidations = array_unique($failedValidations);
            $msg = __("Os seguintes requisitos devem ser atendidos para prosseguir:") . " " . implode(', ', $failedValidations);
            \Session::addMessageAfterRedirect($msg, true, ERROR);
            return false;
        }

        // 3. Process Transitions
        $transitions = self::getTransitions($currentStepId);
        foreach ($transitions as $transition) {
            if ($transition['transition_type'] === 'default') {
                self::moveToStep($item, $state, $transition['plugin_flow_steps_id_target']);
                return;
            }
        }
    }

    private static function getMatchingFlow($entityId, $catId)
    {
        global $DB;
        $iter = $DB->request([
            'FROM' => 'glpi_plugin_flow_flows',
            'WHERE' => [
                'entities_id' => $entityId,
                'itilcategories_id' => $catId,
                'is_active' => 1
            ],
            'LIMIT' => 1
        ]);
        return $iter->current();
    }

    private static function getInitialStep($flowId)
    {
        global $DB;
        $iter = $DB->request([
            'FROM' => 'glpi_plugin_flow_steps',
            'WHERE' => [
                'plugin_flow_flows_id' => $flowId,
                'step_type' => 'Initial'
            ],
            'LIMIT' => 1
        ]);
        return $iter->current();
    }

    private static function createTicketState($ticketId, $flowId, $stepId)
    {
        global $DB;
        $DB->insert('glpi_plugin_flow_ticket_states', [
            'tickets_id' => $ticketId,
            'plugin_flow_flows_id' => $flowId,
            'plugin_flow_steps_id' => $stepId,
            'status' => 'PENDING',
            'date_mod' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ]);
    }

    private static function updateTicketState($stateId, $nextStepId)
    {
        global $DB;
        $DB->update('glpi_plugin_flow_ticket_states', [
            'plugin_flow_steps_id' => $nextStepId,
            'date_mod' => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ], [
            'id' => $stateId
        ]);
    }

    private static function getTicketState($ticketId)
    {
        global $DB;
        $iter = $DB->request([
            'FROM' => 'glpi_plugin_flow_ticket_states',
            'WHERE' => ['tickets_id' => $ticketId],
            'LIMIT' => 1
        ]);
        return $iter->current();
    }

    private static function getTransitions($currentStepId)
    {
        global $DB;
        $iter = $DB->request([
            'FROM' => 'glpi_plugin_flow_transitions',
            'WHERE' => ['plugin_flow_steps_id_source' => $currentStepId]
        ]);
        return iterator_to_array($iter);
    }

    private static function getStep($stepId)
    {
        global $DB;
        $iter = $DB->request([
            'FROM' => 'glpi_plugin_flow_steps',
            'WHERE' => ['id' => $stepId],
            'LIMIT' => 1
        ]);
        return $iter->current();
    }

    private static function getTransitionByType($sourceId, $type)
    {
        global $DB;
        $iter = $DB->request([
            'FROM' => 'glpi_plugin_flow_transitions',
            'WHERE' => [
                'plugin_flow_steps_id_source' => $sourceId,
                'transition_type' => $type
            ],
            'LIMIT' => 1
        ]);
        return $iter->current();
    }

    private static function moveToStep(CommonITILObject $item, $state, $targetStepId)
    {
        self::updateTicketState($state['id'], $targetStepId);
        self::$already_processed = true; // Avoid recursion if same hook is triggered
        self::runStepActions($targetStepId, $item);

        // Check for automatic transitions (Conditions)
        self::processAutoTransitions($item, $state, $targetStepId);
    }

    private static function processAutoTransitions(CommonITILObject $item, $state, $stepId)
    {
        $step = self::getStep($stepId);
        if (!$step || $step['step_type'] !== 'Condition') {
            return;
        }

        // Evaluate validations for the Condition step
        // We check ALL validations assigned to this step.
        if (self::checkValidations($stepId, $item, $failed, false)) {
            // Positive Path
            $transition = self::getTransitionByType($stepId, 'condition_positive');
        } else {
            // Negative Path
            $transition = self::getTransitionByType($stepId, 'condition_negative');
        }

        if ($transition) {
            self::moveToStep($item, $state, $transition['plugin_flow_steps_id_target']);
        }
    }

    private static function checkValidations($stepId, $item, &$failedValidations = null, $onlyBlockers = true)
    {
        global $DB;

        $where = ['val.plugin_flow_steps_id' => $stepId];
        if ($onlyBlockers) {
            $where['val.severity'] = 'BLOCKER';
        }

        $iter = $DB->request([
            'SELECT' => [
                'val.*',
                'type.name AS type_name'
            ],
            'FROM' => 'glpi_plugin_flow_validations AS val',
            'LEFT JOIN' => [
                'glpi_plugin_flow_validation_types AS type' => [
                    'ON' => [
                        'val' => 'validation_type',
                        'type' => 'name'
                    ]
                ]
            ],
            'WHERE' => $where,
            'ORDER' => 'val.validation_order ASC'
        ]);

        $allPassed = true;
        foreach ($iter as $val) {
            $typeName = $val['type_name'];
            $config = json_decode($val['validation_config'], true) ?? [];

            $validator = ValidationFactory::getValidation($typeName);
            if ($validator && !$validator->validate($item, $config)) {
                if ($failedValidations !== null) {
                    $failedValidations[] = $config['name'] ?? $val['validation_type'];
                }
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    private static function runStepActions($stepId, $item)
    {
        global $DB; // GLPI Database Object
        // We need to fetch actions and their Types
        $iter = $DB->request([
            'SELECT' => [
                'act.*',
                'type.name AS type_name'
            ],
            'FROM' => 'glpi_plugin_flow_actions AS act',
            'LEFT JOIN' => [
                'glpi_plugin_flow_action_types AS type' => [
                    'ON' => [
                        'act' => 'action_type',
                        'type' => 'name'
                    ]
                ]
            ],
            'WHERE' => ['act.plugin_flow_steps_id' => $stepId],
            'ORDER' => 'act.action_order ASC'
        ]);

        foreach ($iter as $action) {
            $typeName = $action['type_name'];
            $config = json_decode($action['action_config'], true) ?? [];
            Toolbox::logInFile('php-errors', 'Iterando sobre as ações: ' . print_r($action, true) . PHP_EOL);

            $handler = ActionFactory::getAction($typeName);
            if ($handler) {
                $handler->execute($item, $config);
            }
        }
    }
}
