<?php

use Glpi\Application\View\TemplateRenderer;
use Glpi\Event;
use Glpi\DBAL\DB;



class PluginFlowActorManager {

    /**
     * Define ou adiciona um ator (usuário ou grupo) a um ticket.
     *
     * Compatível com GLPI 11.
     *
     * @param int $ticket_id
     * @param string $actor_type  'user' ou 'group'
     * @param int $actor_id
     * @param string $role        'technician', 'observer', 'assign', 'requester'
     * @param bool $log
     *
     * @return bool
     */
    public static function setActor(int $ticket_id, string $actor_type, int $actor_id, string $role, bool $log = true): bool {
        global $DB;

        if (!in_array($actor_type, ['user', 'group']) || !$actor_id || !$ticket_id) {
            return false;
        }

        $ticket = new Ticket();
        if (!$ticket->getFromDB($ticket_id)) {
            return false;
        }

        switch ($role) {
            /**
             * 🧑‍🔧 Técnico principal
             */
            case 'technician':
                $field = $actor_type === 'user' ? 'users_id_tech' : 'groups_id_tech';
                $ticket->update([
                    'id' => $ticket_id,
                    $field => $actor_id
                ]);
                if ($log) {
                    self::logFlowAction($ticket_id, "Definido $actor_type #$actor_id como técnico principal");
                }
                return true;

            /**
             * 👀 Observador
             */
            case 'observer':
                $type = CommonITILActor::OBSERVER;
                break;

            /**
             * 👨‍💼 Técnico adicional
             */
            case 'assign':
                $type = CommonITILActor::ASSIGN;
                break;

            /**
             * 🙋 Solicitante
             */
            case 'requester':
                $type = CommonITILActor::REQUESTER;
                break;

            default:
                return false;
        }

        // Evita duplicação
        $criteria = [
            'SELECT' => ['id'],
            'FROM' => 'glpi_ticket_users',
            'WHERE' => [
                'tickets_id' => $ticket_id,
                'type' => $type,
                $actor_type === 'user' ? 'users_id' : 'groups_id' => $actor_id
            ]

        ];

        $exists = $DB->request($criteria);
        // $qb = new QueryBuilder($DB);
        // $qb->select('id')
        //     ->from('glpi_tickets_users')
        //     ->where([
        //         'tickets_id' => $ticket_id,
        //         'type' => $type,
        //         $actor_type === 'user' ? 'users_id' : 'groups_id' => $actor_id
        //     ]);

        // $exists = $qb->execute()->fetch();

        if (!$exists->current()) {
            $ticket_user = new Ticket_User();
            $data = [
                'tickets_id' => $ticket_id,
                'type' => $type
            ];
            if ($actor_type === 'user') {
                $data['users_id'] = $actor_id;
            } else {
                $data['groups_id'] = $actor_id;
            }

            $ticket_user->add($data);

            if ($log) {
                self::logFlowAction($ticket_id, "Adicionado $actor_type #$actor_id como $role");
            }
        }

        return true;
    }

    /**
     * 💾 Log interno do plugin
     */
    private static function logFlowAction(int $ticket_id, string $message): void {
        $log_dir = GLPI_ROOT . '/marketplace/flow/';
        if (!is_dir($log_dir)) {
            mkdir($log_dir, 0775, true);
        }

        $logfile = $log_dir . 'flow_actions.log';
        $datetime = date('Y-m-d H:i:s');
        $user = Session::getLoginUserID() ?: 'system';
        $entry = "[$datetime] Ticket #$ticket_id | User #$user | $message\n";
        file_put_contents($logfile, $entry, FILE_APPEND);
    }
}
