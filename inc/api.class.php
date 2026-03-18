<?php

namespace Glpi\Plugin\Flow;

use Glpi\Plugin\Flow\Service\FlowBuilderService;

class Api
{
    private FlowBuilderService $flowBuilderService;

    public function __construct(?FlowBuilderService $flowBuilderService = null)
    {
        $this->flowBuilderService = $flowBuilderService ?? new FlowBuilderService();
    }

    public function handleRequest()
    {
        header('Content-Type: application/json');
        $method = $_SERVER['REQUEST_METHOD'];
        $action = $_GET['action'] ?? '';

        try {
            switch ($action) {
                case 'get_flows':
                    $this->sendResponse($this->flowBuilderService->getFlows());
                    break;
                case 'get_flow':
                    $this->sendResponse($this->flowBuilderService->getFlow((int) ($_GET['id'] ?? 0)));
                    break;
                case 'save_flow':
                    if ($method !== 'POST') {
                        throw new \Exception('Method not allowed');
                    }
                    $this->sendResponse($this->flowBuilderService->saveFlow($this->getJsonBody()));
                    break;
                case 'delete_flow':
                    $this->sendResponse($this->flowBuilderService->deleteFlow((int) ($_GET['id'] ?? 0)));
                    break;
                case 'toggle_active':
                    if ($method !== 'POST') {
                        throw new \Exception('Method not allowed');
                    }
                    $this->sendResponse($this->flowBuilderService->toggleActive($this->getJsonBody()));
                    break;
                case 'get_metadata':
                    $this->sendResponse($this->flowBuilderService->getMetadata());
                    break;
                case 'log_js_error':
                    \Toolbox::logInFile('php-errors', 'JS Error: ' . json_encode($this->getJsonBody()));
                    $this->sendResponse(['status' => 'logged']);
                    break;
                case 'get_tables':
                    $this->sendResponse($this->flowBuilderService->getTables());
                    break;
                case 'get_fields':
                    $this->sendResponse($this->flowBuilderService->getFields($_GET['table'] ?? ''));
                    break;
                case 'get_task_template_preview':
                    $this->sendResponse($this->flowBuilderService->getTaskTemplatePreview((int) ($_GET['id'] ?? 0)));
                    break;
                case 'get_tags':
                    $this->sendResponse($this->flowBuilderService->getTags());
                    break;
                case 'import_flow':
                    if ($method !== 'POST') {
                        throw new \Exception('Method not allowed');
                    }
                    $this->sendResponse($this->flowBuilderService->importFlow($this->getJsonBody()));
                    break;
                default:
                    throw new \Exception('Action not found');
            }
        } catch (\Exception $e) {
            http_response_code(400);
            $this->sendResponse(['error' => $e->getMessage()]);
        }
    }

    private function sendResponse($data): void
    {
        echo json_encode($data);
        exit;
    }

    private function getJsonBody(): array
    {
        $data = json_decode(file_get_contents('php://input'), true);
        return is_array($data) ? $data : [];
    }
}
