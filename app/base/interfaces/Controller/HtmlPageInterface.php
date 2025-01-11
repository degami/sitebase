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

namespace App\Base\Interfaces\Controller;

interface HtmlPageInterface extends PageInterface
{
    public const FLASHMESSAGE_INFO = 'info';
    public const FLASHMESSAGE_SUCCESS = 'success';
    public const FLASHMESSAGE_WARNING = 'warning';
    public const FLASHMESSAGE_ERROR = 'danger';

    public const FLASHMESSAGE_PRIMARY = 'primary';
    public const FLASHMESSAGE_SECONDARY = 'secondary';
    public const FLASHMESSAGE_LIGHT = 'light';
    public const FLASHMESSAGE_DARK = 'dark';

    /**
     * gets current page template name
     *
     * @return string
     */
    public function getTemplateName(): string;

    /**
     * gets current page template data
     *
     * @return array
     */
    public function getTemplateData(): array;
}