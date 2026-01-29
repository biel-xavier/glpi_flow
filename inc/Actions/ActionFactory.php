<?php

namespace Glpi\Plugin\Flow\Actions;

class ActionFactory
{
    /**
     * Get an action handler by its type name
     *
     * @param string $actionType
     * @return ActionInterface|null
     */
    public static function getAction(string $actionType): ?ActionInterface
    {
        switch ($actionType) {
            case 'ADD_ACTOR':
                return new AddActor();
            case 'CHANGE_STATUS':
                return new ChangeStatus();
            case 'ADD_SLA_TTO':
                return new AddSla();
            case 'ADD_TASK_TEMPLATE':
                return new AddTaskTemplate();
            case 'REQUEST_VALIDATION':
                return new RequestValidation();
            case 'ADD_TAG':
                return new AddTag();
            case 'TRANSFER_FROM_QUERY':
                return new TransferFromQuery();
            case 'REQUEST_VALIDATION_FROM_QUERY':
                return new RequestValidationFromQuery();
            default:
                return null;
        }
    }
}
