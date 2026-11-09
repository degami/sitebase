<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Abstracts\Controllers;

use App\Base\Abstracts\Controllers\AdminJsonPage;
use Symfony\Component\HttpFoundation\Request;

/**
 * Base AI Admin Json Page
 */
abstract class AIAdminJsonPage extends AdminJsonPage
{
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
        $content = json_decode($request->getContent(), true);
        if (is_array($content) && !empty($content['messageId'])) {
            return (string) $content['messageId'];
        }

        return $request->request->get('messageId') ?? $request->query->get('messageId');
    }
}
