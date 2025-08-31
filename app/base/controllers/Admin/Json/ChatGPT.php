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
 * ChatGPT Admin
 */
class ChatGPT extends AdminJsonPage
{

    public const CHATGPT_MAX_TOKENS = 50;
    public const CHATGPT_TOKEN_PATH = 'app/chatgpt/token';
    public const CHATGPT_REMAINING_TOKENS_PATH = 'app/chatgpt/remaining_tokens';

    protected string $endpoint = 'https://api.openai.com/v1/engines/gpt-3.5-turbo/completions';

    /**
     * determines if route is available for router
     * 
     * @return bool
     */
    public static function isEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH));
    }

    /**
     * returns model name
     * 
     * @return string
     */
    public static function getModelName() : string
    {
        return 'ChatGPT';
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
        $apiKey = $this->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH);
        if (empty($apiKey)) {
            throw new Exception("Missing ChatGPT Token");
        }

//        $remainingTokens = intval($this->getSiteData()->getConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH));
//        $maxTokens = min(self::CHATGPT_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::CHATGPT_MAX_TOKENS;
        $client = $this->getGuzzle();

        $messageId = $this->getMessageId($this->getRequest());

        $prompt = $this->getPrompt($this->getRequest());
        if (empty($prompt)) {
            throw new Exception("Missing ChatGPT prompt text");
        }

        $response = $client->post($this->endpoint, [
            'headers' => [
                'Authorization' => "Bearer ".$apiKey,
            ],
            'json' => [
                'prompt' => $prompt,
                'max_tokens' => $maxTokens, // Adjust the max tokens as needed
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['choices'][0]['text'];

        // update remaining tokens configuration
        //$this->getSiteData()->setConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return ['success' => true, 'prompt' => $prompt, 'text' => $generatedText, 'messageId' => $messageId];
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
