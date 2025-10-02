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
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Interfaces\AI\AIModelInterface;
use Exception;

/**
 * Groq AI Model
 */
class Groq extends ContainerAwareObject implements AIModelInterface
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

    /**
     * Invia una richiesta chat compatibile OpenAI
     */
    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null): string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::GROQ_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Groq Token");
        }

        $modelName = $this->getModel($model);

        // costruzione messages stile OpenAI
        $messages = $previousMessages ?? [];
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post("https://api.groq.com/openai/" . $this->getVersion() . "/chat/completions", [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => $modelName,
                'messages' => $messages,
                'temperature' => 0.7,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception("Invalid response from Groq API: " . json_encode($data));
        }

        return trim($data['choices'][0]['message']['content']);
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
