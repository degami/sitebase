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

use App\Base\Abstracts\Controllers\AIAdminJsonPage;
use App\Base\AI\Manager;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;

/**
 * ChatGPT Admin
 */
class ChatGPT extends AIAdminJsonPage
{
    /**
     * determines if route is available for router
     * 
     * @return bool
     */
    public static function isEnabled() : bool 
    {
        return Manager::isChatGPTEnabled();
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
        $messageId = $this->getMessageId($this->getRequest());

        $prompt = $this->getPrompt($this->getRequest());
        if (empty($prompt)) {
            throw new Exception("Missing ChatGPT prompt text");
        }

        $generatedText = $this->getAI()->askChatGPT($prompt);

        return ['success' => true, 'prompt' => $prompt, 'text' => $generatedText, 'messageId' => $messageId];
    }
}
