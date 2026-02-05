<?php

namespace Glpi\Plugin\Flow\Validations;

use CommonITILObject;

class FieldNotEmpty implements ValidationInterface
{
    public function validate(CommonITILObject $item, array $config): bool
    {
        $field = $config['field_name'] ?? '';
        if (empty($field)) {
            return true; // Or false if config is invalid, but usually true to not block if misconfigured
        }

        // Project projected state
        $fields = $item->fields;
        if (isset($item->input) && is_array($item->input)) {
            $fields = array_merge($fields, $item->input);
        }

        return !empty($fields[$field]);
    }
}
