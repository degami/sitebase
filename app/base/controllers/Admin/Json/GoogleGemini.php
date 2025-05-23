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

namespace App\Base\Controllers\Admin\Json;

use App\App;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\Request;

/**
 * GoogleGemini Admin
 */
class GoogleGemini extends AdminJsonPage
{
    public const GEMINI_TOKEN_PATH = 'app/gemini/token';

    /**
     * determines if route is available for router
     * 
     * @return bool
     */
    public static function isEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH));
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
        $apiKey = $this->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH);
        if (empty($apiKey)) {
            throw new Exception("Missing Gemini Token");
        }

        $client = $this->getGuzzle();

        $messageId = $this->getMessageId($this->getRequest());

        $prompt = $this->getPrompt($this->getRequest());
        if (empty($prompt)) {
            throw new Exception("Missing Gemini prompt text");
        }

        $response = $client->post($this->getEndpoint($apiKey), [
            'headers' => [
                'Content-Type' => "application/json",
            ],
            'json' => [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt,
                            ]
                        ]
                    ]
                ],
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];

        return ['success' => true, 'prompt' => $prompt, 'text' => $generatedText, 'messageId' => $messageId];
    }

    protected function getEndpoint(string $api_key) : string
    {
        return "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$api_key}";
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
