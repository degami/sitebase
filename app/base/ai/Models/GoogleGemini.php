<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\AI\Models;

use App\App;
use App\Base\Abstracts\Models\AbstractLLMAdapter;
use App\Base\AI\Flows\BaseFlow;
use Exception;

/**
 * GoogleGemini AI Model
 */
class GoogleGemini extends AbstractLLMAdapter
{
    public const GEMINI_MODEL = 'gemini-flash-latest';
    public const GEMINI_MODEL_PATH = 'app/gemini/model';
    public const GEMINI_VERSION = 'v1beta';
    public const GEMINI_VERSION_PATH = 'app/gemini/version';
    public const GEMINI_TOKEN_PATH = 'app/gemini/token';

    public static function getCode() : string
    {
        return 'googlegemini';
    }

    public static function getName() : string
    {
        return "Google Gemini";
    }
    
    public static function isEnabled() : bool
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH));
    }

    public function getCompletionsEndpoint(?string $model = null) : string
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Gemini Token");
        }

        return "https://generativelanguage.googleapis.com/" . $this->getVersion() . "/models/" . $this->getModel($model) . ":generateContent?key={$apiKey}";
    }

    public function getEmbeddingsEndpoint(?string $model = null) : string
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Gemini Token");
        }

        return "https://generativelanguage.googleapis.com/" . $this->getVersion() . "/models/" . $this->getModel($model) . ":embedContent?key={$apiKey}";
    }

    public function prepareRequest(array $payload) : array
    {
        return [
            'headers' => [
                'Content-Type' => "application/json",
            ],
            'json' => $payload,
        ];
    }

    public function formatUserMessage(string $prompt): array
    {
        return [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];
    }

    public function formatAssistantMessage(mixed $message, ?string $messageType = null): array
    {
        return [
            'role' => 'model',
            'parts' => [
                [$messageType ?? 'text' => $message]
            ]
        ];
    }

    public function formatAssistantFunctionCallMessage(string $functionName, array $args, ?string $id = null): ?array
    {
        // this method is not used by Gemini orchestrator flows, but we need to implement it for the interface
        return [
            'role' => 'model',
            'parts' => [
                [
                    'function_call' => [
                        'name' => $functionName,
                        'args' => json_encode($args)
                    ]
                ]
            ]
        ];
    }

    public function buildConversation(array $previousMessages, string $prompt, ?string $model = null): array
    {
        $messages = $previousMessages;
        $messages[] = $this->formatUserMessage($prompt);

        return [
            'contents' => $messages,
        ];
    }

    public function normalizeCompletionsResponse(array $raw): array
    {
        $assistantText = null;
        $functionCalls = [];
        $rawFunctionMessages = [];

        if (!empty($raw['candidates']) && is_array($raw['candidates'])) {
            foreach ($raw['candidates'] as $candidate) {
                if (!isset($candidate['content']['parts'])) {
                    continue;
                }

                foreach ($candidate['content']['parts'] as $part) {

                    $fc = $part['function_call']
                        ?? $part['functionCall']
                        ?? null;

                    if ($fc) {
                        $args = $fc['args'] ?? ($fc['arguments'] ?? []);
                        if (is_string($args)) {
                            $decoded = json_decode($args, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $args = $decoded;
                            }
                        }

                        $functionCalls[] = [
                            'name' => $fc['name'] ?? null,
                            'args' => $args ?? []
                        ];

                        $rawFunctionMessages[] = [
                            'role' => 'model',
                            'parts' => [$part]
                        ];
                    }

                    if (isset($part['text'])) {
                        $assistantText .= $part['text'];
                    }
                }
            }
        }

        return [
            'assistantText'        => $assistantText ?: null,
            'functionCalls'        => $functionCalls,
            'rawFunctionMessages'  => $rawFunctionMessages,
            'raw'                  => $raw
        ];
    }

    public function buildFlowInitialRequest(BaseFlow $flow, string $userPrompt, array &$history = [], ?string $model = null): array
    {
        $messages = [];

        $messages[] = (object) $this->formatUserMessage($flow->systemPrompt());

        // googlegemini does not support system messages after the first one, add schema as user message
        if ($flow->schema()) {
            $messages[] = (object) $this->formatUserMessage("Ecco il tuo schema GraphQL:\n" . $flow->schema());
        }

        // add previous history
        foreach ($history as $msg) {
            $messages[] = (object) $msg;
        }

        $messages[] =  (object) $this->formatUserMessage($userPrompt);

        // Costruiamo le dichiarazioni dei tool
        $functions = [];
        foreach ($flow->tools() as $name => $schema) {
            $functions[] = [
                'name' => $name,
                'description' => $schema['description'] ?? '',
                'parameters' => $schema['parameters'] ?? ['type' => 'object']
            ];
        }

        return [
            'contents' => array_values($messages),
            'tools' => [
                ['function_declarations' => $functions]
            ]
        ];
    }

    public function sendFunctionResponse(string $name, array $result, ?array $tools = null, array &$history = [], ?string $model = null, ?string $id = null): array
    {
        $history[] = [
            'role' => 'tool',
            'parts' => [
                [
                    'functionResponse' => [
                        'name' => $name,
                        'response' => $result
                    ]
                ]
            ]
        ];

        return $this->sendRaw([
            'contents' => $history,
            'tools' => $tools ?? [],
        ]);
    }

    public function buildEmbeddingRequest(string $input, ?string $model = null): array
    {
        return [
            'model' => $this->getModel($model),
            'content' => [
                'parts' => [[
                    'text' => $input
                ]]
            ]
        ];
    }

    public function normalizeEmbeddingsResponse(array $raw) : array
    {
        return [
            'embedding' => $raw['embedding']['values'] ?? [],
            'raw' => $raw
        ];
    }

    public function getAvailableModels(bool $reset = false) : array
    {
        $models_key = "ai.googlegemini.models_list";
        if (!$this->getCache()->has($models_key) || $reset) {

            $client = $this->getGuzzle();
            $apiKey = $this->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH);

            if (empty($apiKey)) {
                throw new Exception("Missing Gemini Token");
            }

            $endPoint = "https://generativelanguage.googleapis.com/" . $this->getVersion() . "/models?key={$apiKey}";

            $response = $client->get($endPoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $models = array_map(fn ($el) => str_replace("models/","", $el), array_column($data['models'] ?? [], 'name'));

            $this->getCache()->set($models_key, $models);
        } else {
            $models = $this->getCache()->get($models_key);
        }

        return $models;
    }

    public function getDefaultModel() : string
    {
        return self::GEMINI_MODEL;
    }

    public function getVersion() : string
    {
        return $this->getSiteData()->getConfigValue(self::GEMINI_VERSION_PATH) ?? self::GEMINI_VERSION;
    }

    public function getModel(?string $model = null) : string
    {
        if (!is_null($model) && in_array($model, $this->getAvailableModels())) {
            return $model;
        }

        return $this->getSiteData()->getConfigValue(self::GEMINI_MODEL_PATH) ?? $this->getDefaultModel();
    }
}
