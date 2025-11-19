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
 * Claude AI Model
 */
class Claude extends AbstractLLMAdapter
{
    public const CLAUDE_MODEL = 'claude-3-5-sonnet-20241022';
    public const CLAUDE_MODEL_PATH = 'app/claude/model';
    public const CLAUDE_VERSION = 'v1';
    public const CLAUDE_VERSION_PATH = 'app/claude/version';
    public const CLAUDE_TOKEN_PATH = 'app/claude/token';
    public const CLAUDE_REMAINING_TOKENS_PATH = 'app/claude/remaining_tokens';
    public const CLAUDE_MAX_TOKENS = 1000;

    public static function getCode() : string
    {
        return 'claude';
    }

    public static function getName() : string
    {
        return "Claude";
    }
    
    public static  function isEnabled() : bool
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH));
    }

    public function getEndpoint(?string $model = null) : string
    {
        return "https://api.anthropic.com/" . $this->getVersion() . "/messages";
    }

    public function prepareRequest(array $payload) : array
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Claude Token");
        }

        return [
            'headers' => [
                'Content-Type' => 'application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            'json' => $payload,
        ];
    }

    public function formatUserMessage(string $prompt): array
    {
        return [
            'role' => 'user',
            'content' => $prompt
        ];
    }

    public function buildConversation(array $previousMessages, string $prompt, ?string $model = null): array
    {
        $messages = $previousMessages;
        $messages[] = $this->formatUserMessage($prompt);

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::CLAUDE_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::CLAUDE_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::CLAUDE_MAX_TOKENS;

        return [
            'model' => $this->getModel($model),
            'max_tokens' => $maxTokens,
            'messages' => $messages,
        ];
    }

    public function normalizeResponse(array $raw): array
    {
        $assistantText = '';
        $functionCalls = [];

        if (!empty($raw['content'])) {
            foreach ($raw['content'] as $item) {

                if ($item['type'] === 'text') {
                    $assistantText .= $item['text'];
                }

                if ($item['type'] === 'tool_use') {
                    $functionCalls[] = [
                        'id' => $item['id'],
                        'name' => $item['name'],
                        'args' => $item['input']
                    ];
                }
            }
        }

        return [
            'assistantText' => $assistantText ?: null,
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

    public function sendFunctionResponse(string $toolUseId, array $result, array &$history = []): array
    {
        return $this->sendRaw([
            'messages' => [
                [
                    'role' => 'assistant',
                    'content' => [
                        [
                            'type' => 'tool_result',
                            'tool_use_id' => $toolUseId,
                            'content' => json_encode($result)
                        ]
                    ]
                ]
            ]
        ]);
    }

    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string
    {
        $generatedText = parent::ask($prompt, $model, $previousMessages);

        // update remaining tokens configuration
        // $maxTokens = self::CHATGPT_MAX_TOKENS;
        // $this->getSiteData()->setConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return $generatedText;
    }

    public function getAvailableModels(bool $reset = false) : array
    {
        $models_key = "ai.claude.models_list";
        if (!$this->getCache()->has($models_key) || $reset) {

            $client = $this->getGuzzle();
            $apiKey = $this->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH);

            if (empty($apiKey)) {
                throw new Exception("Missing Claude Token");
            }

            $endPoint = "https://api.anthropic.com/" . $this->getVersion() . "/models";

            $response = $client->get($endPoint, [
                'headers' => [
                    'x-api-key' => $apiKey,
                    'anthropic-version' => '2023-06-01',
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
        return self::CLAUDE_MODEL;
    }

    public function getVersion() : string
    {
        return $this->getSiteData()->getConfigValue(self::CLAUDE_VERSION_PATH) ?? self::CLAUDE_VERSION;
    }

    public function getModel(?string $model = null) : string
    {
        if (!is_null($model) && in_array($model, $this->getAvailableModels())) {
            return $model;
        }

        return $this->getSiteData()->getConfigValue(self::CLAUDE_MODEL_PATH) ?? $this->getDefaultModel();
    }
}
