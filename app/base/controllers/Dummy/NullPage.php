<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Controllers\Dummy;

use App\Base\Abstracts\Controllers\BaseHtmlPage;

/**
 * This page does nothing
 */
class NullPage extends BaseHtmlPage
{
    /**
     * {@inheritdoc}
     */
    protected function getTemplateName(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    protected function getTemplateData(): array
    {
        return [];
    }
}
