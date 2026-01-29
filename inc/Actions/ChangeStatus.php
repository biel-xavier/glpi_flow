<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;

class ChangeStatus implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $status = $config['status'] ?? null;
        if (!$status) {
            return;
        }

        if (isset($item->input) && is_array($item->input)) {
            $item->input['status'] = $status;
        } else {
            $item->update([
                'id' => $item->getID(),
                'status' => $status,
                '_disablenotif' => true
            ]);
        }
    }
}
