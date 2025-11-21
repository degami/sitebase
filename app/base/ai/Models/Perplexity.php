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
 * Perplexity AI Model
 */
class Perplexity extends AbstractLLMAdapter
{
    public const PERPLEXITY_MODEL = 'pplx-70b-online'; // modello di default
    public const PERPLEXITY_TOKEN_PATH = 'app/perplexity/token';
    public const PERPLEXITY_MODEL_PATH = 'app/perplexity/model';

    public static function getCode() : string
    {
        return 'perplexity';
    }

    public static function getName() : string
    {
        return "Perplexity AI";
    }

    public static function isEnabled() : bool
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::PERPLEXITY_TOKEN_PATH));
    }

    public function getCompletionsEndpoint(?string $model = null) : string
    {
        return "https://api.perplexity.ai/chat/completions";
    }

    public function getEmbeddingsEndpoint(?string $model = null) : string
    {
        return "https://api.perplexity.ai/embeddings";
    }

    public function prepareRequest(array $payload) : array
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::PERPLEXITY_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Perplexity Token");
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
        ];
    }

    public function normalizeCompletionsResponse(array $raw): array
    {
        $text = $raw['choices'][0]['text'] ?? null;

        return [
            'assistantText' => $text,
            'functionCalls' => [],
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

        return array_values($messages);
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

    public function normalizeEmbeddingsResponse(array $raw) : array
    {
        return [
            'embedding' => $raw['data'][0]['embedding'] ?? [],
            'raw' => $raw
        ];
    }

    public function getAvailableModels(bool $reset = false) : array
    {
        // perplexity has no endpoint for models list, currently
        return [
            'pplx-70b-online',
            'pplx-70b-chat',
            'pplx-7b-online',
            'pplx-7b-chat',
        ];
    }

    public function getDefaultModel() : string
    {
        return self::PERPLEXITY_MODEL;
    }

    public function getModel(?string $model = null) : string
    {
        $available = $this->getAvailableModels();
        if (!is_null($model) && in_array($model, $available)) {
            return $model;
        }

        $configured = $this->getSiteData()->getConfigValue(self::PERPLEXITY_MODEL_PATH);
        if (!empty($configured) && in_array($configured, $available)) {
            return $configured;
        }

        return $this->getDefaultModel();
    }

    // perplexity has no version on urls. this method is defined only for compatibility with interface
    public function getVersion() : string
    {
        return 'none';
    }
}
