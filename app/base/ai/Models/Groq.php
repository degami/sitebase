<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\AI\Models;

use App\App;
use App\Base\Abstracts\Models\AbstractLLMAdapter;
use App\Base\AI\Flows\BaseFlow;
use Exception;

/**
 * Groq AI Model
 */
class Groq extends AbstractLLMAdapter
{
    public const GROQ_MODEL = 'llama-3.1-8b-instant';
    public const GROQ_TOKEN_PATH = 'app/groq/token';
    public const GROQ_MODEL_PATH = 'app/groq/model';
    public const GROQ_VERSION = 'v1';
    public const GROQ_VERSION_PATH = 'app/groq/version';

    public static function getCode(): string
    {
        return 'groq';
    }

    public static function getName(): string
    {
        return "Groq";
    }

    public static function isEnabled(): bool
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::GROQ_TOKEN_PATH));
    }

    public function getCompletionsEndpoint(?string $model = null) : string
    {
        return "https://api.groq.com/openai/" . $this->getVersion() . "/chat/completions";
    }

    public function getEmbeddingsEndpoint(?string $model = null) : string
    {
        return "https://api.groq.com/openai/" . $this->getVersion() . "/embeddings";
    }

    public function prepareRequest(array $payload) : array
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::GROQ_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Groq Token");
        }

        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => $payload,
        ];
    }

    public function formatUserMessage(string $prompt): array
    {
        return [
            'role' => 'user',
            'content' => $prompt,
        ];
    }

    public function formatAssistantMessage(mixed $message, ?string $messageType = null): array
    {
        return [
            'role' => 'assistant',
            $messageType ?? 'content' => $message
        ];
    }

    public function formatAssistantFunctionCallMessage(string $functionName, array $args, ?string $id = null): ?array
    {
        return $this->formatAssistantMessage("Call function $functionName with argoments " . json_encode($args) . (!is_null($id) ? " (id: $id)" : ""));
    }
    
    public function buildConversation(array $previousMessages, string $prompt, ?string $model = null): array
    {
        $messages = $previousMessages;
        $messages[] = $this->formatUserMessage($prompt);

        return [
            'model' => $this->getModel($model),
            'messages' => $messages,
            'temperature' => 0.7,
        ];
    }

    public function normalizeResponse(array $raw): array
    {
        $assistantText = null;
        $functionCalls = [];

        if (isset($raw['choices'][0]['message'])) {
            $msg = $raw['choices'][0]['message'];

            // testo normale
            if (!empty($msg['content'])) {
                $assistantText = $msg['content'];
            }

            // tool calls
            if (!empty($msg['tool_calls'])) {
                foreach ($msg['tool_calls'] as $call) {
                    $functionCalls[] = [
                        'name' => $call['function']['name'],
                        'args' => json_decode($call['function']['arguments'], true),
                        'id' => $call['id']
                    ];
                }
            }
        }

        return [
            'assistantText' => $assistantText,
            'functionCalls' => $functionCalls,
            'raw' => $raw
        ];
    }

    public function buildFlowInitialRequest(BaseFlow $flow, string $userPrompt, ?string $model = null): array
    {
        $messages = [
            [
                'role' => 'system',
                'content' => $flow->systemPrompt()
            ],
        ];

        if ($flow->schema()) {
            $messages[] = [
                'role' => 'system',
                'content' => "Schema GraphQL disponibile:\n" . $flow->schema()
            ];
        }

        $messages[] = [
            'role' => 'assistant',
            'content' => json_encode($flow->tools())
        ];

        $messages[] = $this->formatUserMessage($userPrompt);

        return [
            'model' => $this->getDefaultModel(),
            'messages' => array_values($messages),
        ];
    }

    public function sendFunctionResponse(string $functionName, array $result, array &$history = [], ?string $id = null): array
    {
        $history[] = $this->formatUserMessage("Tool response for call to function $functionName".(!is_null($id)?" (id: $id)":"").": " . json_encode($result));

        return $this->sendRaw([
            'model' => $this->getDefaultModel(),
            'messages' => $history
        ]);
    }

    public function buildEmbeddingRequest(string $input, ?string $model = null): array
    {
        return [
            'model' => $this->getModel($model),
            'input' => $input
        ];
    }

    public function getAvailableModels(bool $reset = false): array
    {
        $cacheKey = "ai.groq.models_list";

        if (!$this->getCache()->has($cacheKey) || $reset) {
            $client = $this->getGuzzle();
            $apiKey = $this->getSiteData()->getConfigValue(self::GROQ_TOKEN_PATH);

            if (empty($apiKey)) {
                throw new Exception("Missing Groq Token");
            }

            $response = $client->get("https://api.groq.com/openai/" . $this->getVersion() . "/models", [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $apiKey,
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $models = array_column($data['data'] ?? [], 'id');

            $this->getCache()->set($cacheKey, $models);
        } else {
            $models = $this->getCache()->get($cacheKey);
        }

        return $models;
    }

    public function getDefaultModel(): string
    {
        return self::GROQ_MODEL;
    }

    public function getModel(?string $model = null): string
    {
        $available = $this->getAvailableModels();
        if (!is_null($model) && in_array($model, $available)) {
            return $model;
        }

        $configured = $this->getSiteData()->getConfigValue(self::GROQ_MODEL_PATH);
        if (!empty($configured) && in_array($configured, $available)) {
            return $configured;
        }

        return $this->getDefaultModel();
    }

    public function getVersion() : string
    {
        return $this->getSiteData()->getConfigValue(self::GROQ_VERSION_PATH) ?? self::GROQ_VERSION;
    }
}
