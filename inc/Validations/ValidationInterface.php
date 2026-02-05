<?php

namespace Glpi\Plugin\Flow\Validations;

use CommonITILObject;

interface ValidationInterface
{
    /**
     * Validate the item based on configuration
     *
     * @param CommonITILObject $item The ticket or ITIL object being processed
     * @param array $config The configuration for this specific validation instance
     * @return bool True if validation passes, false otherwise
     */
    public function validate(CommonITILObject $item, array $config): bool;
}
