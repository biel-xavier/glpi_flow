<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;
use UserEmail;
use Toolbox;

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
            $actorId = 0;

            // Resolve Dynamic Keywords
            if ($id === 'REQUESTER') {
                // 1. Try input _actors (set during Ticket creation/update in UI)
                if (isset($item->input['_actors']['requester'][0]['items_id'])) {
                    $actorId = (int)$item->input['_actors']['requester'][0]['items_id'];
                }
                // 2. Try input _users_id_requester (legacy/other GLPI methods)
                elseif (isset($item->input['_users_id_requester'][0])) {
                    $actorId = (int)$item->input['_users_id_requester'][0];
                }
                // 3. Try fields (if ticket already exists and was loaded from DB)
                elseif (isset($item->fields['id']) && $item->fields['id'] > 0) {
                    global $DB;
                    $table = $itemtype === 'User' ? 'glpi_tickets_users' : 'glpi_groups_tickets';
                    $fk    = $itemtype === 'User' ? 'users_id' : 'groups_id';

                    $iter = $DB->request([
                        'SELECT' => [$fk],
                        'FROM'   => $table,
                        'WHERE'  => [
                            'tickets_id' => $item->fields['id'],
                            'type'       => \CommonITILActor::REQUESTER
                        ],
                        'LIMIT'  => 1
                    ]);

                    if ($iter->count() > 0) {
                        $row = $iter->current();
                        $actorId = (int)$row[$fk];
                    }
                }

                // 4. Fallback to users_id_recipient (the person who opened the ticket)
                if ($actorId <= 0 && isset($item->fields['users_id_recipient'])) {
                    $actorId = (int)$item->fields['users_id_recipient'];
                }
            } elseif ($id === 'AUTHOR') {
                $actorId = (int)($_SESSION['glpiID'] ?? 0);
            } else {
                $actorId = (int)$id;
            }

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
        }
    }
}
