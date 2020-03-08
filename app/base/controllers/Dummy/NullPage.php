<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Base\Controllers\Dummy;

use \Psr\Container\ContainerInterface;
use \App\App;
use \App\Base\Abstracts\Controllers\BaseHtmlPage;
use \App\Site\Routing\RouteInfo;
use \Symfony\Component\HttpFoundation\Response;

/**
 * This page does nothing
 */
class NullPage extends BaseHtmlPage
{
    /**
     * {@inheritdocs}
     */
    protected function getTemplateName()
    {
        return '';
    }

    /**
     * {@inheritdocs}
     */
    protected function getTemplateData()
    {
        return [];
    }
}
