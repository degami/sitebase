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
    public const MISTRAL_MODEL = 'mistral-medium';
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

    public function getEndpoint(?string $model = null) : string
    {
        return "https://api.mistral.ai/" . $this->getVersion() . "/chat/completions";
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

    public function buildFlowInitialMessages(BaseFlow $flow, string $userPrompt): array
    {
        $messages = [
            [
                'role' => 'system',
                'parts' => [
                    ['text' => $flow->systemPrompt()]
                ]
            ],
        ];

        if ($flow->schema()) {
            $messages[] = [
                'role' => 'system',
                'content' => "Schema GraphQL disponibile:\n" . $flow->schema()
            ];
        }

        $messages[] = [
            'role' => 'user',
            'parts' => [
                ['text' => $userPrompt]
            ]
        ];

        $messages[] = [
            'role' => 'model',
            'parts' => [
                ['text' => json_encode($flow->tools())]
            ]
        ];

        return array_values($messages);
    }

    public function sendFunctionResponse(string $toolCallId, array $result, array &$history = []): array
    {
        return $this->sendRaw([
            'messages' => [
                [
                    'role' => 'tool',
                    'tool_call_id' => $toolCallId,
                    'content' => json_encode($result)
                ]
            ]
        ]);
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
