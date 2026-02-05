<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;
use TicketValidation;
use Session;
use Sanitizer;
use Glpi\Plugin\Flow\Utils;

class RequestValidation implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $userId  = $config['user_id'] ?? 0;
        $groupId = $config['group_id'] ?? 0;
        $comment = $config['comment'] ?? "Flow Automatic Validation Request";

        $ticketId = $item->getID();
        if ($ticketId <= 0) {
            return;
        }

        $entityId = (int) ($item->fields['entities_id'] ?? 0);
        $creatorUserId = 2; // Default GLPI system user or specific ID

        // Ensure a user is logged in for the validation to have an author (if in cron)
        if (!\Session::getLoginUserID()) {
            // In a hook context, this is rare, but for safety in cron:
            // \Session::init(new \User()->getFromDB(2)); 
        }

        $validation = new TicketValidation();
        // Resolve Keywords for User ID
        $userId = Utils::resolveActorId($item, $userId, 'User');

        // Resolve Keywords for Group ID
        $groupId = Utils::resolveActorId($item, $groupId, 'Group');

        $input = [
            'tickets_id'         => $ticketId,
            'entities_id'        => $entityId,
            'users_id_validate'  => ($groupId > 0) ? 0 : $userId,
            'itemtype_target'    => ($groupId > 0) ? 'Group' : 'User',
            'items_id_target'    => ($groupId > 0) ? $groupId : $userId,
            'status'             => TicketValidation::WAITING,
            'submission_date'    => date('Y-m-d H:i:s'),
            'comment_submission' => $comment,
            'users_id'           => $creatorUserId
        ];

        $input = \Toolbox::addslashes_deep($input);
        $validation->add($input);
    }
}
