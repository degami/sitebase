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
use App\Base\Interfaces\AI\AIModelInterface;
use App\Base\Abstracts\ContainerAwareObject;
use Exception;

/**
 * ChatGPT AI Model
 */
class ChatGPT extends ContainerAwareObject implements AIModelInterface
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

    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing ChatGPT Token");
        }

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::CHATGPT_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::CHATGPT_MAX_TOKENS;

        $endPoint = "https://api.openai.com/" . $this->getVersion() . "/chat/completions";

        $messages = $previousMessages ?? [];
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Authorization' => "Bearer ".$apiKey,
            ],
            'json' => [
                'model' => $this->getModel($model),
                'messages' => $messages,
                'max_tokens' => $maxTokens, // Adjust the max tokens as needed
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['choices'][0]['text'];

        // update remaining tokens configuration
        // $this->getSiteData()->setConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return trim($generatedText);
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
