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
 * Claude Admin
 */
class Claude extends AdminJsonPage
{
    public const CLAUDE_TOKEN_PATH = 'app/claude/token';

    /**
     * determines if route is available for router
     * 
     * @return bool
     */
    public static function isEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH));
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
        $apiKey = $this->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH);
        if (empty($apiKey)) {
            throw new Exception("Missing Claude Token");
        }

        $model = 'claude-3-5-sonnet-20241022';
        $maxTokens = 1000;

        $client = $this->getGuzzle();

        $messageId = $this->getMessageId($this->getRequest());

        $prompt = $this->getPrompt($this->getRequest());
        if (empty($prompt)) {
            throw new Exception("Missing Claude prompt text");
        }

        $response = $client->post($this->getEndpoint(), [
            'headers' => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            'json' => [
                'model' => $model,
                'max_tokens' => $maxTokens,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $prompt
                    ]
                ]
            ],
        ]);
        $data = json_decode($response->getBody(), true);

        $generatedText = $data['content'][0]['text'];

        return ['success' => true, 'prompt' => $prompt, 'text' => $generatedText, 'messageId' => $messageId];
    }

    protected function getEndpoint() : string
    {
        return "https://api.anthropic.com/v1/messages";
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
