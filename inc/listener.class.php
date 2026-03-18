<?php

namespace Glpi\Plugin\Flow;

if (!defined('GLPI_ROOT')) {
    die('Sorry. You can\'t access this file directly');
}

use CommonITILObject;
use Glpi\Plugin\Flow\Service\FlowExecutionService;

class Listener
{
    public static function onTicketAdd(CommonITILObject $item)
    {
        (new FlowExecutionService())->handleTicketAdd($item);
    }


    public static function onTicketPreUpdate(CommonITILObject $item)
    {
        return (new FlowExecutionService())->handleTicketPreUpdate($item);
    }
}
