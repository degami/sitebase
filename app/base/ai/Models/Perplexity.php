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
 * Perplexity AI Model
 */
class Perplexity extends ContainerAwareObject implements AIModelInterface
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

    /**
     * Invia una richiesta all'API Perplexity e ottiene una risposta
     */
    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::PERPLEXITY_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Perplexity Token");
        }

        $modelName = $this->getModel($model);

        $messages = $previousMessages ?? [];
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post('https://api.perplexity.ai/chat/completions', [
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => $modelName,
                'messages' => $messages,
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        if (!isset($data['choices'][0]['message']['content'])) {
            throw new Exception("Invalid response from Perplexity API: " . json_encode($data));
        }

        return trim($data['choices'][0]['message']['content']);
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
