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
 * Mistral AI Model
 */
class Mistral extends AbstractLLMAdapter
{
    public const MISTRAL_MODEL = 'mistral-small-latest';
    public const MISTRAL_MODEL_PATH = 'app/mistral/model';
    public const MISTRAL_VERSION = 'v1';
    public const MISTRAL_VERSION_PATH = 'app/mistral/version';
    public const MISTRAL_TOKEN_PATH = 'app/mistral/token';
    public const MISTRAL_REMAINING_TOKENS_PATH = 'app/mistral/remaining_tokens';
    public const MISTRAL_MAX_TOKENS = 1000;

    public static function getCode() : string
    {
        return 'mistral';
    }   

    public static function getName() : string
    {
        return "Mistral";
    }
    
    public static function isEnabled() : bool
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH));
    }

    public function getCompletionsEndpoint(?string $model = null) : string
    {
        return "https://api.mistral.ai/" . $this->getVersion() . "/chat/completions";
    }

    public function getEmbeddingsEndpoint(?string $model = null) : string
    {
        return "https://api.mistral.ai/" . $this->getVersion() . "/embeddings";
    }

    public function prepareRequest(array $payload) : array
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Mistral Token");
        }

        return [
            'headers' => [
                'Content-Type: application/json',
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

        $maxTokens = self::MISTRAL_MAX_TOKENS;

        return [
            'model' => $this->getModel($model),
            'max_tokens' => $maxTokens,
            "temperature" => 0.7,
            'messages' => $messages,
        ];
    }

    public function normalizeCompletionsResponse(array $raw): array
    {
        $assistantText = null;
        $functionCalls = [];

        if (isset($raw['choices'][0]['message'])) {
            $msg = $raw['choices'][0]['message'];

            if (!empty($msg['content'])) {
                $assistantText = $msg['content'];
            }

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

        $messages[] = $this->formatUserMessage($userPrompt);

        $tools = [];
        if (!empty($flow->tools())) {
            foreach ($flow->tools() as $toolName => $tool) {
                $tools[] = [
                    'function' => [
                        'name' => $toolName,
                        'description' => $tool['description'] ?? '',
                        'parameters' => $tool['parameters'] ?? [],
                    ],
                ];
            }
        }

        return [
            'model' => $this->getDefaultModel(),
            'tools' => $tools,
            'messages' => array_values($messages),
        ];
    }

    public function sendFunctionResponse(string $functionName, array $result, ?array $tools = null, array &$history = [], ?string $id = null): array
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

    public function normalizeEmbeddingsResponse(array $raw) : array
    {
        return [
            'embedding' => $raw['data'][0]['embedding'] ?? [],
            'raw' => $raw
        ];
    }

    public function getAvailableModels(bool $reset = false) : array
    {
        $models_key = "ai.mistral.models_list";
        if (!$this->getCache()->has($models_key) || $reset) {

            $client = $this->getGuzzle();
            $apiKey = $this->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH);

            if (empty($apiKey)) {
                throw new Exception("Missing Mistral Token");
            }

            $endPoint = "https://api.mistral.ai/" . $this->getVersion() . "/models";

            $response = $client->get($endPoint, [
                'headers' => [
                    'Authorization' => 'Bearer ' . $apiKey,
                    'Content-Type' => 'application/json',
                ],
            ]);

            $data = json_decode($response->getBody(), true);
            $models = array_column($data['data'] ?? [], 'id');

            $this->getCache()->set($models_key, $models);
        } else {
            $models = $this->getCache()->get($models_key);
        }

        return $models;
    }

    public function getDefaultModel() : string
    {
        return self::MISTRAL_MODEL;
    }

    public function getVersion() : string
    {
        return $this->getSiteData()->getConfigValue(self::MISTRAL_VERSION_PATH) ?? self::MISTRAL_VERSION;
    }

    public function getModel(?string $model = null) : string
    {
        if (!is_null($model) && in_array($model, $this->getAvailableModels())) {
            return $model;
        }

        return $this->getSiteData()->getConfigValue(self::MISTRAL_MODEL_PATH) ?? $this->getDefaultModel();
    }
}
