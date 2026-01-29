<?php

namespace Glpi\Plugin\Flow\Validations;

use CommonITILObject;
use Toolbox;

class QueryCheck implements ValidationInterface
{
    public function validate(CommonITILObject $item, array $config): bool
    {
        $base = $config['base_for_consultation'] ?? 'form';
        $function = $config['function'] ?? 'verify_value';
        $validator = $config['validator'] ?? 'EQUAL';
        $expectedValue = $config['value'] ?? 0;

        $actualValue = null;

        if ($base === 'database') {
            $actualValue = $this->getFieldValueInDB($item->getID(), $config);
        } else {
            $actualValue = $this->getFieldValueInForm($item, $config);
        }

        if ($function === 'verify_length') {
            return $this->verifyLength($expectedValue, $actualValue, $validator);
        }

        return $this->verifyValue($expectedValue, $actualValue, $validator);
    }

    private function getFieldValueInForm(CommonITILObject $item, array $config): mixed
    {
        $field = $config['field'] ?? '';
        $fieldIndex = $config['fieldIndex'] ?? null;

        // If fieldIndex is provided, we might be looking at a nested array (like in the user's example)
        if ($fieldIndex && isset($item->input[$fieldIndex][$field])) {
            return $item->input[$fieldIndex][$field];
        }

        // Fallback to standard input or fields
        if (isset($item->input[$field])) {
            return $item->input[$field];
        }

        return $item->fields[$field] ?? null;
    }

    private function getFieldValueInDB($ticketId, array $config): mixed
    {
        global $DB;

        $table = $config['table'] ?? '';
        $field = $config['field'] ?? '';
        $fieldIndex = $config['fieldIndex'] ?? 'items_id';

        if (empty($table) || empty($field)) {
            return null;
        }

        $criteria = [
            'SELECT' => [$field],
            'FROM'   => $table,
            'WHERE'  => [
                $fieldIndex => $ticketId
            ]
        ];

        // Others where
        if (isset($config['othersWhereField']) && is_array($config['othersWhereField'])) {
            foreach ($config['othersWhereField'] as $other) {
                $criteria['WHERE'][$other['field']] = $other['value'];
            }
        }

        // Order by
        if (isset($config['orderBy']) && is_array($config['orderBy'])) {
            $criteria['ORDERBY'] = [];
            foreach ($config['orderBy'] as $orderBy) {
                $criteria['ORDERBY'][] = $orderBy['field'] . " " . ($orderBy['order'] ?? 'ASC');
            }
        }

        $iter = $DB->request($criteria);
        $results = iterator_to_array($iter, false);

        if (empty($results)) {
            return null;
        }

        // If a field is specified, we want its value for comparison (even for length)
        if (!empty($field)) {
            return $results[0][$field] ?? null;
        }

        // If no field, return the results array (useful for counting rows)
        return $results;
    }

    private function verifyLength($expected, $actual, $operator): bool
    {
        $count = 0;
        if (is_string($actual)) {
            $count = strlen($actual);
        } elseif (is_array($actual)) {
            $count = count($actual);
        } elseif (is_null($actual)) {
            $count = 0;
        } else {
            $count = (int)$actual;
        }


        Toolbox::logInFile('php-errors', $expected . '_' . print_r($actual, true));
        switch ($operator) {
            case "MAJOR":
                return $count > $expected;
            case "MINOR":
                return $count < $expected;
            case "EQUAL":
                return $count == $expected;
            case "DIFFERENT":
                return $count != $expected;
        }
        return false;
    }

    private function verifyValue($expected, $actual, $operator): bool
    {
        switch ($operator) {
            case "EQUAL":
                return $actual == $expected;
            case "DIFFERENT":
                return $actual != $expected;
            case "MAJOR":
                return $actual > $expected;
            case "MINOR":
                return $actual < $expected;
        }
        return false;
    }
}
