<?php

namespace Glpi\Plugin\Flow\Validations;

class ValidationFactory
{
    /**
     * Get a validation handler by its type name
     *
     * @param string $validationType
     * @return ValidationInterface|null
     */
    public static function getValidation(string $validationType): ?ValidationInterface
    {
        switch ($validationType) {
            case 'FIELD_NOT_EMPTY':
                return new FieldNotEmpty();
            case 'QUERY_CHECK':
                return new QueryCheck();
            case 'HTTP_RESPONSE_CHECK':
                return new HttpResponseCheck();
            default:
                return null;
        }
    }
}
