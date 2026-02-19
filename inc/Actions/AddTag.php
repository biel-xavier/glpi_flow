<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;

class AddTag implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $tagId = $config['tag_id'] ?? 0;
        if ($tagId <= 0) {
            return;
        }

        unset($item->input['_plugin_tag_tag_values']);

        // // GLPI Tag plugin structure
        // if (!isset($item->input['_plugin_tag_tag_values']) || !is_array($item->input['_plugin_tag_tag_values'])) {
        //     $item->input['_plugin_tag_tag_values'] = [];
        // }
        $item->input['_plugin_tag_tag_values'][] = $tagId;
    }
}
