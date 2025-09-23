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
 * Claude AI Model
 */
class Claude extends ContainerAwareObject implements AIModelInterface
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

    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Claude Token");
        }

        $endPoint = "https://api.anthropic.com/" . $this->getVersion() . "/messages";

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::CLAUDE_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::CLAUDE_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::CLAUDE_MAX_TOKENS;

        $messages = $previousMessages ?? [];
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            'json' => [
                'model' => $this->getModel($model),
                'max_tokens' => $maxTokens,
                'messages' => $messages,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['content'][0]['text'];

        // update remaining tokens configuration
        // $this->getSiteData()->setConfigValue(self::CLAUDE_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return trim($generatedText);
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
