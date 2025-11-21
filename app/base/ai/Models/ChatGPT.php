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
 * ChatGPT AI Model
 */
class ChatGPT extends AbstractLLMAdapter
{
    public const CHATGPT_MODEL = 'gpt-3.5-turbo';
    public const CHATGPT_MODEL_PATH = 'app/chatgpt/model';
    public const CHATGPT_VERSION = 'v1';
    public const CHATGPT_VERSION_PATH = 'app/chatgpt/version';
    public const CHATGPT_TOKEN_PATH = 'app/chatgpt/token';
    public const CHATGPT_REMAINING_TOKENS_PATH = 'app/chatgpt/remaining_tokens';
    public const CHATGPT_MAX_TOKENS = 50;

    public static function getCode() : string
    {
        return 'chatgpt';
    }

    public static function getName() : string
    {
        return "ChatGPT";
    }

    public static function isEnabled() : bool
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH));
    }

    public function getCompletionsEndpoint(?string $model = null) : string
    {
        return "https://api.openai.com/" . $this->getVersion() . "/chat/completions";
    }

    public function getEmbeddingsEndpoint(?string $model = null) : string
    {
        return "https://api.openai.com/" . $this->getVersion() . "/embeddings";
    }

    public function prepareRequest(array $payload) : array
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing ChatGPT Token");
        }

        return [
            'headers' => [
                'Authorization' => "Bearer ".$apiKey,
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

    public function formatAssistantMessage(mixed $message, ?string $messageType = null): array
    {
        return [
            'role' => 'assistant',
            $messageType ?? 'content' => $message
        ];
    }

    public function formatAssistantFunctionCallMessage(string $functionName, array $args, ?string $id = null): array
    {
        return [
            'role' => 'assistant',
            'tool_calls' => [
                [
                    'id' => $id ?? $functionName,
                    'type' => 'function',
                    'function' => [
                        'name' => $functionName,
                        'arguments' => json_encode($args, JSON_UNESCAPED_UNICODE),
                    ],
                ],
            ],
        ];
    }

    public function buildConversation(array $previousMessages, string $prompt, ?string $model = null): array
    {
        $messages = $previousMessages;
        $messages[] = $this->formatUserMessage($prompt);

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::CHATGPT_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::CHATGPT_MAX_TOKENS;

        return [
            'model' => $this->getModel($model),
            'messages' => $messages,
            'temperature' => 0,
            'max_tokens' => $maxTokens, // Adjust the max tokens as needed
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

        $messages[] = $this->formatUserMessage($userPrompt);

        $tools = [];
        if (!empty($flow->tools())) {
            foreach ($flow->tools() as $toolName => $tool) {
                $tools[] = [
                    'name' => $toolName,
                    'description' => $tool['description'] ?? '',
                    'parameters' => $tool['parameters'] ?? [],
                ];
            }
        }

        return [
            'model' => $this->getDefaultModel(),
            'tools' => $tools,
            'messages' => array_values($messages),
        ];
    }

    public function sendFunctionResponse(string $name, array $result, array &$history = [], ?string $id = null): array
    {
        return $this->sendRaw([
            'model' => $this->getDefaultModel(),
            'messages' => [
                [
                    'role' => 'tool',
                    'tool_call_id' => $id ?? $name,
                    'content' => json_encode($result)
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

    public function buildEmbeddingRequest(string $input, ?string $model = null): array
    {
        return [
            'model' => $this->getModel($model),
            'input' => $input
        ];
    }

    public function getAvailableModels(bool $reset = false) : array
    {
        $models_key = "ai.chatgpt.models_list";
        if (!$this->getCache()->has($models_key) || $reset) {

            $client = $this->getGuzzle();
            $apiKey = $this->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH);

            if (empty($apiKey)) {
                throw new Exception("Missing ChatGPT Token");
            }

            $endPoint = "https://api.openai.com/" . $this->getVersion() . "/models";

            $response = $client->get($endPoint, [
                'headers' => [
                    'Authorization' => "Bearer " . $apiKey,
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
        return self::CHATGPT_MODEL;
    }

    public function getVersion() : string
    {
        return $this->getSiteData()->getConfigValue(self::CHATGPT_VERSION_PATH) ?? self::CHATGPT_VERSION;
    }

    public function getModel(?string $model = null) : string
    {
        if (!is_null($model) && in_array($model, $this->getAvailableModels())) {
            return $model;
        }

        return $this->getSiteData()->getConfigValue(self::CHATGPT_MODEL_PATH) ?? $this->getDefaultModel();
    }
}
