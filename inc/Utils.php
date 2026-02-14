<?php

namespace Glpi\Plugin\Flow;

use CommonITILObject;
use Session;

class Utils
{
    /**
     * Resolve dynamic actor keywords to IDs
     * 
     * @param CommonITILObject $item The ITIL Object (Ticket)
     * @param string|int $keyword The keyword (REQUESTER, TECH, etc) or ID
     * @param string $type Link Type ('User' or 'Group')
     * @return int
     */
    public static function resolveActorId(CommonITILObject $item, $keyword, $type = 'User')
    {
        // If it's already a number or numeric string, return it cast to int
        if (is_numeric($keyword)) {
            return (int)$keyword;
        }

        $keyword = strtoupper($keyword);
        $id = 0;

        // 1. AUTHOR / MYSELF
        if ($keyword === 'AUTHOR' || $keyword === 'MYSELF') {
            return (int)($_SESSION['glpiID'] ?? 0);
        }

        // 2. REQUESTER
        if ($keyword === 'REQUESTER') {
            // Try input _actors (set during Ticket creation/update in UI)
            if (isset($item->input['_actors']['requester'][0]['items_id']) && $type === 'User') {
                return (int)$item->input['_actors']['requester'][0]['items_id'];
            }
            // Try legacy form input
            if (isset($item->input['_users_id_requester'][0]) && $type === 'User') {
                return (int)$item->input['_users_id_requester'][0];
            }
            // Try existing object fields
            if (isset($item->fields['id']) && $item->fields['id'] > 0) {
                global $DB;
                $table = ($type === 'User') ? 'glpi_tickets_users' : 'glpi_groups_tickets';
                $fk    = ($type === 'User') ? 'users_id' : 'groups_id';

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
                    return (int)$iter->current()[$fk];
                }
            }
            // Fallback
            if (isset($item->fields['users_id_recipient']) && $type === 'User') {
                return (int)$item->fields['users_id_recipient'];
            }
        }

        // 3. TECH / ASSIGNED
        if ($keyword === 'TECH' || $keyword === 'ASSIGNED' || $keyword === 'ASSIGN') {
            // Try input _actors (set during Ticket creation/update in UI)
            if (isset($item->input['_actors']['assign'][0]['items_id']) && $type === 'User') {
                return (int)$item->input['_actors']['assign'][0]['items_id'];
            }

            if (isset($item->fields['id']) && $item->fields['id'] > 0) {
                global $DB;
                $table = ($type === 'User') ? 'glpi_tickets_users' : 'glpi_groups_tickets';
                $fk    = ($type === 'User') ? 'users_id' : 'groups_id';

                $iter = $DB->request([
                    'SELECT' => [$fk],
                    'FROM'   => $table,
                    'WHERE'  => [
                        'tickets_id' => $item->fields['id'],
                        'type'       => \CommonITILActor::ASSIGN
                    ],
                    'LIMIT'  => 1
                ]);
                if ($iter->count() > 0) {
                    return (int)$iter->current()[$fk];
                }
            }
        }

        // 4. OBSERVER
        if ($keyword === 'OBSERVER') {
            if (isset($item->fields['id']) && $item->fields['id'] > 0) {
                global $DB;
                $table = ($type === 'User') ? 'glpi_tickets_users' : 'glpi_groups_tickets';
                $fk    = ($type === 'User') ? 'users_id' : 'groups_id';

                $iter = $DB->request([
                    'SELECT' => [$fk],
                    'FROM'   => $table,
                    'WHERE'  => [
                        'tickets_id' => $item->fields['id'],
                        'type'       => \CommonITILActor::OBSERVER
                    ],
                    'LIMIT'  => 1
                ]);
                if ($iter->count() > 0) {
                    return (int)$iter->current()[$fk];
                }
            }
        }

        return $id;
    }
}
