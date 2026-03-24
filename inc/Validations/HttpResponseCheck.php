<?php

namespace Glpi\Plugin\Flow\Validations;

use CommonITILObject;
use Toolbox;

class HttpResponseCheck implements ValidationInterface
{
    public function validate(CommonITILObject $item, array $config): bool
    {
        $expectedCode = $config['expected_code'] ?? 200;
        $operator = $config['operator'] ?? 'EQUAL'; // EQUAL, DIFFERENT, MAJOR, MINOR, REGEX

        // Check if we have a recorded request from the immediate previous action
        if (!isset($item->input['_last_request_http_code'])) {
            // No request was made in this step execution cycle?
            return false;
        }

        $actualCode = (int)$item->input['_last_request_http_code'];

        return $this->verifyValue($expectedCode, $actualCode, $operator);
    }

    private function verifyValue($expected, $actual, $operator): bool
    {
        switch ($operator) {
            case "EQUAL":
                return $actual == $expected;
            case "DIFFERENT":
                return $actual != $expected;
            case "MAJOR": // Greater than
                return $actual > $expected;
            case "MINOR": // Less than
                return $actual < $expected;
            case "REGEX": // Regex match on code (e.g. 2..)
                return preg_match("/$expected/", (string)$actual);
        }
        return false;
    }
}
