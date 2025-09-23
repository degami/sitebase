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
 * GoogleGemini AI Model
 */
class GoogleGemini extends ContainerAwareObject implements AIModelInterface
{
    public const GEMINI_MODEL = 'gemini-1.5-flash-latest';
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

    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Gemini Token");
        }

        $endPoint = "https://generativelanguage.googleapis.com/" . $this->getVersion() . "/models/" . $this->getModel($model) . ":generateContent?key={$apiKey}";

        $contents = $previousMessages ?? [];
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Content-Type' => "application/json",
            ],
            'json' => [
                'contents' => $contents,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];

        return trim($generatedText);
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
