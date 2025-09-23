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
 * Mistral AI Model
 */
class Mistral extends ContainerAwareObject implements AIModelInterface
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

    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Mistral Token");
        }

        $endPoint = "https://api.mistral.ai/" . $this->getVersion() . "/chat/completions";

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::MISTRAL_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::MISTRAL_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::MISTRAL_MAX_TOKENS;

        $messages = $previousMessages ?? [];
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Content-Type: application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => $this->getModel($model),
                'max_tokens' => $maxTokens,
                "temperature" => 0.7,
                'messages' => $messages,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['choices'][0]['message']['content'] ?? null;

        // update remaining tokens configuration
        // $this->getSiteData()->setConfigValue(self::MISTRAL_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return trim($generatedText);
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
