<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;
use Toolbox;

class TransferFromQuery implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $table      = $config['table'] ?? '';
        $field      = $config['field'] ?? '';
        $fieldIndex = $config['fieldIndex'] ?? 'items_id';
        $mapping    = $config['data_comparative'] ?? []; // Array of ['value_database' => ..., 'value_insert' => ...]
        Toolbox::logInFile('php-errors', 'Valor do campo: ' . print_r($config, true) . PHP_EOL);

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
        Toolbox::logInFile('php-errors', 'Valor do campo: ' . print_r($dbValue, true) . PHP_EOL);
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
            // Re-use AddActor logic or call it
            $addActor = new AddActor();
            $addActor->execute($item, [
                'actor_type' => 'assign',
                'user_id'    => $targetId,
                'mode'       => 'replace'
            ]);
        }
    }
}
