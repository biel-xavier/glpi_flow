<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;

class RequestValidationFromQuery implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $table      = $config['table'] ?? '';
        $field      = $config['field'] ?? '';
        $fieldIndex = $config['fieldIndex'] ?? 'items_id';
        $comment = $config['comment'] ?? "Flow Automatic Validation Request";
        $mapping    = $config['data_comparative'] ?? [];

        if (empty($table) || empty($field)) {
            return;
        }

        global $DB;
        $iter = $DB->request([
            'SELECT' => [$field],
            'FROM'   => $table,
            'WHERE'  => [$fieldIndex => $item->getID()],
            'LIMIT'  => 1
        ]);

        $result = $iter->current();
        if (!$result) {
            return;
        }

        $dbValue = $result[$field];
        $targetId = null;

        if (empty($mapping)) {
            $targetId = $dbValue;
        } else {
            foreach ($mapping as $map) {
                if ($map['value_database'] == $dbValue) {
                    $targetId = $map['value_insert'];
                    break;
                }
            }
        }

        if ($targetId) {
            $requestValidation = new RequestValidation();
            $requestValidation->execute($item, [
                'user_id' => $targetId,
                'comment' => $comment
            ]);
        }
    }
}
