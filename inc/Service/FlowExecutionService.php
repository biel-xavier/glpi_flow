<?php

namespace Glpi\Plugin\Flow\Service;

use CommonITILObject;
use Glpi\Plugin\Flow\Actions\ActionFactory;
use Glpi\Plugin\Flow\Repository\FlowRepository;
use Glpi\Plugin\Flow\Repository\StepHistoryRepository;
use Glpi\Plugin\Flow\Repository\TicketStateRepository;
use Glpi\Plugin\Flow\Validations\ValidationFactory;
use Toolbox;

class FlowExecutionService
{
    private FlowRepository $flowRepository;
    private TicketStateRepository $ticketStateRepository;
    private StepHistoryRepository $stepHistoryRepository;
    private bool $alreadyProcessed = false;

    public function __construct(
        ?FlowRepository $flowRepository = null,
        ?TicketStateRepository $ticketStateRepository = null,
        ?StepHistoryRepository $stepHistoryRepository = null
    ) {
        $this->flowRepository = $flowRepository ?? new FlowRepository();
        $this->ticketStateRepository = $ticketStateRepository ?? new TicketStateRepository();
        $this->stepHistoryRepository = $stepHistoryRepository ?? new StepHistoryRepository();
    }

    public function handleTicketAdd(CommonITILObject $item): void
    {
        Toolbox::logInFile('php-errors', 'Ticket created: ' . $item->getID());

        $state = $this->initializeTicketFlowState($item);
        if ($state === null) {
            return;
        }

        $this->runStepActions((int) $state['plugin_flow_steps_id'], $item);
        $this->processAutoTransitions($item, $state, (int) $state['plugin_flow_steps_id']);
    }

    public function handleTicketPreUpdate(CommonITILObject $item)
    {
        if ($this->alreadyProcessed) {
            return;
        }

        $state = $this->ticketStateRepository->findByTicket((int) $item->getID());
        if ($state === null) {
            $state = $this->initializeTicketFlowState($item);
            if ($state === null) {
                return;
            }
        }

        $currentStepId = (int) $state['plugin_flow_steps_id'];
        $failedValidations = [];
        if (!$this->checkValidations($currentStepId, $item, $failedValidations, true)) {
            $failedValidations = array_unique($failedValidations);
            $msg = __("Os seguintes requisitos devem ser atendidos para prosseguir:")
                . ' ' . implode(', ', $failedValidations);
            \Session::addMessageAfterRedirect($msg, true, WARNING);
            $item->input = false;
            return false;
        }

        foreach ($this->flowRepository->findTransitions($currentStepId) as $transition) {
            if (($transition['transition_type'] ?? '') !== 'default') {
                continue;
            }

            $this->moveToStep($item, $state, (int) $transition['plugin_flow_steps_id_target']);
            return;
        }

        return null;
    }

    private function initializeTicketFlowState(CommonITILObject $item): ?array
    {
        $entityId = $this->getEffectiveFieldValue($item, 'entities_id');
        $categoryId = $this->getEffectiveFieldValue($item, 'itilcategories_id');
        if ($entityId <= 0 || $categoryId <= 0) {
            return null;
        }

        $flow = $this->flowRepository->findMatchingFlow($entityId, $categoryId);
        if ($flow === null) {
            return null;
        }

        $initialStep = $this->flowRepository->findInitialStep((int) $flow['id']);
        if ($initialStep === null) {
            return null;
        }

        $existingState = $this->ticketStateRepository->findByTicket((int) $item->getID());
        if ($existingState !== null) {
            return $existingState;
        }

        $this->ticketStateRepository->create((int) $item->getID(), (int) $flow['id'], (int) $initialStep['id']);
        $this->stepHistoryRepository->logStepEntry((int) $item->getID(), (int) $flow['id'], (int) $initialStep['id']);

        Toolbox::logInFile(
            'php-errors',
            sprintf(
                'Flow: Initialized state for ticket %d with flow %d and step %d',
                (int) $item->getID(),
                (int) $flow['id'],
                (int) $initialStep['id']
            )
        );

        return $this->ticketStateRepository->findByTicket((int) $item->getID());
    }

    private function getEffectiveFieldValue(CommonITILObject $item, string $field): int
    {
        if (isset($item->input) && is_array($item->input) && array_key_exists($field, $item->input)) {
            return (int) $item->input[$field];
        }

        return (int) ($item->fields[$field] ?? 0);
    }

    private function moveToStep(CommonITILObject $item, array $state, int $targetStepId): void
    {
        $targetStep = $this->flowRepository->findStep($targetStepId);

        $this->ticketStateRepository->updateStep((int) $state['id'], $targetStepId);
        $this->stepHistoryRepository->logStepEntry((int) $item->getID(), (int) $state['plugin_flow_flows_id'], $targetStepId);

        if ($targetStep !== null && ($targetStep['step_type'] ?? '') === 'End') {
            $this->markFlowComplete((int) $state['id']);
        }

        $this->alreadyProcessed = true;
        $this->runStepActions($targetStepId, $item);
        $this->processAutoTransitions($item, $state, $targetStepId);
    }

    private function processAutoTransitions(CommonITILObject $item, array $state, int $stepId): void
    {
        $step = $this->flowRepository->findStep($stepId);
        if ($step === null || !in_array($step['step_type'], ['Condition', 'Request'], true)) {
            return;
        }

        if ($this->checkValidations($stepId, $item, $failed, false)) {
            $transition = $this->flowRepository->findTransitionByType($stepId, 'condition_positive');
        } else {
            $transition = $this->flowRepository->findTransitionByType($stepId, 'condition_negative');
        }

        if ($transition !== null) {
            $this->moveToStep($item, $state, (int) $transition['plugin_flow_steps_id_target']);
        }
    }

    private function checkValidations(int $stepId, CommonITILObject $item, ?array &$failedValidations = null, bool $onlyBlockers = true): bool
    {
        $allPassed = true;

        foreach ($this->flowRepository->findStepValidations($stepId, $onlyBlockers) as $validation) {
            $typeName = $validation['type_name'] ?? '';
            $config = json_decode($validation['validation_config'] ?? '{}', true) ?? [];

            $validator = ValidationFactory::getValidation($typeName);
            if ($validator !== null && !$validator->validate($item, $config)) {
                if ($failedValidations !== null) {
                    $failedValidations[] = $config['name'] ?? $validation['validation_type'];
                }
                $allPassed = false;
            }
        }

        return $allPassed;
    }

    private function runStepActions(int $stepId, CommonITILObject $item): void
    {
        foreach ($this->flowRepository->findStepActions($stepId) as $action) {
            $typeName = $action['type_name'] ?? '';
            $config = json_decode($action['action_config'] ?? '{}', true) ?? [];

            $handler = ActionFactory::getAction($typeName);
            if ($handler === null) {
                continue;
            }

            $config['_step_id'] = $stepId;
            $config['_action_id'] = $action['id'];
            $handler->execute($item, $config);
        }
    }

    private function markFlowComplete(int $stateId): void
    {
        try {
            $this->ticketStateRepository->markComplete($stateId);
            Toolbox::logInFile('php-errors', 'Flow: Marked flow as COMPLETE for state ID: ' . $stateId);
        } catch (\Exception $e) {
            Toolbox::logInFile('php-errors', 'Flow: Failed to mark flow complete: ' . $e->getMessage());
        }
    }
}
