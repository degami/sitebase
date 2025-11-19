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

    public function getEndpoint(?string $model = null) : string
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Gemini Token");
        }

        return "https://generativelanguage.googleapis.com/" . $this->getVersion() . "/models/" . $this->getModel($model) . ":generateContent?key={$apiKey}";
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

    public function buildConversation(array $previousMessages, string $prompt, ?string $model = null): array
    {
        $messages = $previousMessages;
        $messages[] = $this->formatUserMessage($prompt);

        return [
            'contents' => $messages,
        ];
    }

    public function normalizeResponse(array $raw): array
    {
        $assistantText = null;
        $functionCalls = [];
        $rawFunctionMessages = [];

        if (!empty($raw['candidates']) && is_array($raw['candidates'])) {

            foreach ($raw['candidates'] as $candidate) {

                // Gemini mette sempre role/parts qui
                if (!isset($candidate['content']['parts'])) {
                    continue;
                }

                foreach ($candidate['content']['parts'] as $part) {

                    //
                    // 1) function_call (nome ufficiale) oppure functionCall (variante)
                    //
                    $fc = $part['function_call']
                        ?? $part['functionCall']
                        ?? null;

                    if ($fc) {

                        // normalizza args (stringa JSON → array)
                        $args = $fc['args'] ?? ($fc['arguments'] ?? []);
                        if (is_string($args)) {
                            $decoded = json_decode($args, true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $args = $decoded;
                            }
                        }

                        // normalizzazione functionCalls per l'orchestrator
                        $functionCalls[] = [
                            'name' => $fc['name'] ?? null,
                            'args' => $args ?? []
                        ];

                        // 2) SALVO IL MESSAGGIO ORIGINALE (importantissimo)
                        // questo è ciò che Gemini vuole a history invariata
                        $rawFunctionMessages[] = [
                            'role' => 'model',
                            'parts' => [$part] // il part contiene function_call originale
                        ];
                    }

                    //
                    // 3) assistant text
                    //
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

    public function buildFlowInitialMessages(BaseFlow $flow, string $userPrompt): array
    {
        $messages =  [
            (object)[
                'role' => 'user',
                'parts' => [
                    ['text' => $flow->systemPrompt()]
                ]
            ],
            (object)[
                'role' => 'user',
                'parts' => [
                    ['text' => $userPrompt]
                ]
            ]
        ];


        if ($flow->schema()) {
            $messages[] = [
                'role' => 'user',
                'parts' => [
                    ['text' => "Ecco il tuo schema GraphQL:\n" . $flow->schema()]
                ]
            ];
        }

        return array_values($messages);
    }

    public function sendFunctionResponse(string $name, array $result, array &$history = []): array
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
            'contents' => $history
        ]);
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
