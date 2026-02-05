<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;
use UserEmail;
use Toolbox;
use Glpi\Plugin\Flow\Utils;

class AddActor implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $actorType  = $config['actor_type'] ?? 'observer'; // assign, observer, requester
        $rawIds     = $config['user_id'] ?? $config['group_id'] ?? 0; // Support user_id or group_id key
        $itemtype   = $config['itemtype'] ?? (($config['group_id'] ?? 0) > 0 ? 'Group' : 'User');
        $mode       = $config['mode'] ?? 'replace';

        // Map internal names to GLPI input keys
        $map = [
            'tech'      => 'assign',
            'assign'    => 'assign',
            'observer'  => 'observer',
            'requester' => 'requester'
        ];

        $glpiType = $map[$actorType] ?? 'observer';

        if ($mode === 'replace') {
            unset($item->input['_actors'][$glpiType]);
        }

        $ids = is_array($rawIds) ? $rawIds : [$rawIds];

        foreach ($ids as $id) {
            $actorId = Utils::resolveActorId($item, $id, $itemtype);

            if ($actorId <= 0) {
                continue;
            }

            $makeDataActor = [
                'itemtype'          => $itemtype,
                'items_id'          => $actorId,
                'use_notification'  => 0,
                'alternative_email' => '',
                'default_email'     => ''
            ];

            if ($itemtype === 'User') {
                $userMail = new UserEmail();
                $emailUser = $userMail->getDefaultForUser($actorId);
                $makeDataActor['use_notification'] = (empty($emailUser)) ? 0 : 1;
                $makeDataActor['default_email'] = $emailUser ?? '';
            }

            $item->input['_actors'][$glpiType][] = $makeDataActor;

            // Special handling for legacy ADD_ACTOR where logic was complex
            // If we are replacing, make sure we aren't appending to an existing list in a way that GLPI duplicate check fails
        }
    }
}
