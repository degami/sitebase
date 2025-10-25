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

namespace App\Base\Controllers\Dummy;

use App\Base\Abstracts\Controllers\BaseHtmlPage;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Tools\Utils\SiteData;
use Symfony\Component\HttpFoundation\Response;

/**
 * This page does nothing
 */
class NullPage extends BaseHtmlPage
{
    /**
     * {@inheritdoc}
     */
    public function getTemplateName(): string
    {
        return '';
    }

    /**
     * {@inheritdoc}
     */
    public function getTemplateData(): array
    {
        return [];
    }

    /**
     * {@inheritdoc}
     */
    protected function beforeRender() : BasePage|Response
    {
        return $this->containerCall([$this->getUtils(), 'errorPage'], ['error_code' => 404, 'route_info' => $this->getAppRouteInfo()]);
    }

    public function getCurrentLocale() : ?string
    {
        return SiteData::DEFAULT_LOCALE;
    }
}
