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

namespace App\Base\Controllers\Admin\Json;

use App\App;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * Mistral Admin
 */
class Mistral extends AdminJsonPage
{
    public const MISTRAL_TOKEN_PATH = 'app/mistral/token';

    /**
     * determines if route is available for router
     * 
     * @return bool
     */
    public static function isEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH));
    }

    /**
     * returns model name
     * 
     * @return string
     */
    public static function getModelName() : string
    {
        return 'Mistral';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $apiKey = $this->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH);
        if (empty($apiKey)) {
            throw new Exception("Missing Mistral Token");
        }

        $model = 'mistral-medium'; // puoi scegliere: mistral-small, mistral-large, ecc.
        $maxTokens = 200;
        $temperature = 0.7;

        $client = $this->getGuzzle();

        $messageId = $this->getMessageId($this->getRequest());

        $prompt = $this->getPrompt($this->getRequest());
        if (empty($prompt)) {
            throw new Exception("Missing Mistral prompt text");
        }

        $response = $client->post($this->getEndpoint(), [
            'headers' => [
                'Content-Type: application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => $model,
                'max_tokens' => $maxTokens,
                "temperature" => $temperature,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ],
        ]);

        $data = json_decode($response->getBody(), true);

        // La risposta di Mistral segue lo schema simile a OpenAI
        $generatedText = $data['choices'][0]['message']['content'] ?? null;

        if ($generatedText === null) {
            throw new Exception("Invalid response from Mistral API: " . $response->getBody());
        }

        return [
            'success' => true,
            'prompt' => $prompt,
            'text' => $generatedText,
            'messageId' => $messageId
        ];
    }

    protected function getEndpoint() : string
    {
        return "https://api.mistral.ai/v1/chat/completions";
    }

    /**
     * @return string|null
     */
    protected function getPrompt(Request $request) : ?string
    {
        $content = json_decode($request->getContent(), true);
        if (is_array($content) && !empty($content['prompt'])) {
            return (string) $content['prompt'];
        }

        return null;
    }

    /**
     * @return string|null
     */
    protected function getMessageId(Request $request) : ?string
    {
        return $request->get('messageId') ?: $request->get('message_id');
    }    
}
