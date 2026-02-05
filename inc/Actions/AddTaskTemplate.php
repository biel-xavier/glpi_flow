<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;

class AddTaskTemplate implements ActionInterface
{
    public function execute(CommonITILObject $item, array $config): void
    {
        $templateId = $config['tasktemplates_id'] ?? 0;
        if ($templateId <= 0) {
            return;
        }

        $template = $this->getTemplate($templateId);
        if (!$template) {
            return;
        }

        $this->insertTask($item->getID(), $template);
    }

    private function getTemplate($templateId): ?array
    {
        global $DB;
        $iter = $DB->request([
            'SELECT' => [
                'id',
                'content',
                'taskcategories_id',
                'is_private',
                'users_id_tech',
                'groups_id_tech'
            ],
            'FROM' => 'glpi_tasktemplates',
            'WHERE' => ['id' => $templateId]
        ]);

        return $iter->current() ?: null;
    }

    private function insertTask($ticketId, $template): void
    {
        global $DB;
        $tableName = 'glpi_tickettasks';

        $params = [
            'uuid'               => $this->generateUuidV4(),
            'tickets_id'         => $ticketId,
            'taskcategories_id'  => $template['taskcategories_id'],
            'users_id_editor'    => 0,
            'users_id'           => 2, // Default user
            'is_private'         => $template['is_private'],
            'state'              => 0,
            'content'            => $template['content'],
            'actiontime'         => 0,
            'users_id_tech'      => $template['users_id_tech'],
            'groups_id_tech'     => $template['groups_id_tech'],
            'tasktemplates_id'   => $template['id'],
            'timeline_position'  => 1,
            'date'               => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
            'date_mod'           => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s'),
            'date_creation'      => $_SESSION['glpi_currenttime'] ?? date('Y-m-d H:i:s')
        ];

        $DB->insert($tableName, $params);
    }

    private function generateUuidV4(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }
}
