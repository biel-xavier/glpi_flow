<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;

class AddSla implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $slaId = $config['slas_id'] ?? 0;
        if ($slaId <= 0) {
            return;
        }

        $item->input['slas_id_tto'] = $slaId;
    }
}
