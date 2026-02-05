<?php

namespace Glpi\Plugin\Flow\Actions;

use CommonITILObject;
use Toolbox;

class RequestHttp implements ActionInterface
{
    /**
     * Execute the HTTP request action
     *
     * @param CommonITILObject $item
     * @param array $config
     * @return void
     */
    public function execute(CommonITILObject $item, array $config): void
    {
        $url = $config["url"] ?? "\\";
        if (empty($url)) {
            Toolbox::logInFile("flow-debug", "RequestHttp: URL is empty Skipping");
            return;
        }

        $method = strtoupper($config["method"] ?? "POST");
        $rawBody = $config["body"] ?? "";
        $headersConfig = $config["headers"] ?? [];
        $isInternal = (bool)($config["is_internal"] ?? false);

        // Parse headers if it implies JSON (from new frontend schema) or is array
        if (is_string($headersConfig)) {
            $parsed = json_decode($headersConfig, true);
            if (is_array($parsed)) {
                $headersConfig = $parsed;
            } else {
                // If invalid JSON, treat as empty or you could log warning
                $headersConfig = [];
            }
        }

        // 1. Placeholder Replacement
        $ticketId = (string)$item->getID();
        $ticketInputJson = json_encode($item->input ?? $item->fields, JSON_UNESCAPED_UNICODE);

        $replacements = [
            "{{ticket_id}}" => $ticketId,
            "{{ticket_input}}" => $ticketInputJson
        ];

        $url = strtr($url, $replacements);
        $body = strtr($rawBody, $replacements);

        // 2. Prepare Headers
        $headers = [];
        foreach ($headersConfig as $key => $value) {
            $headers[] = "$key: $value";
        }

        // Add Content-Type if not present and body is not empty
        $hasContentType = false;
        foreach ($headers as $h) {
            if (stripos($h, "Content-Type:") === 0) {
                $hasContentType = true;
                break;
            }
        }
        if (!$hasContentType && !empty($body)) {
            $headers[] = "Content-Type: application/json";
        }

        // 3. Prepare Cookies (Current Session) - ONLY IF INTERNAL
        $cookieHeader = "";
        if ($isInternal) {
            $sessionName = session_name();
            $sessionId = session_id();
            $cookieHeader = "$sessionName=$sessionId";
        }

        // 4. Initialize Curl
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_COOKIE, $cookieHeader);

        if (!empty($body) && in_array($method, ["POST", "PUT", "PATCH"])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }

        Toolbox::logInFile("flow-debug", "RequestHttp: Sending $method to $url");

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        curl_close($ch);

        if ($response === false) {
            Toolbox::logInFile("flow-debug", "RequestHttp Error: $error");
        } else {
            Toolbox::logInFile("flow-debug", "RequestHttp Response [$httpCode]: " . substr($response, 0, 200) . "...");
        }
    }
}
