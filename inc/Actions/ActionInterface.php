<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;

interface ActionInterface
{
    /**
     * Execute the action logic
     *
     * @param CommonITILObject $item The ticket or ITIL object being processed
     * @param array $config The configuration for this specific action instance
     * @return void
     */
    public function execute(CommonITILObject $item, array $config): void;
}
