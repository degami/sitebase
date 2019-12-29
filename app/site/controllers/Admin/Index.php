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
namespace App\Site\Controllers\Admin;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\AdminPage;
use \App\Site\Routing\RouteInfo;

/**
 * "Index" Admin Page
 */
class Index extends AdminPage
{
    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'index';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getTemplateData()
    {
        return [];
    }

    /**
     * {@inheritdocs}
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            return $this->getUtils()->errorPage(403);
        }
        
        $this->addFlashMessage('info', 'test message');
        return $this->doRedirect($this->getUrl('admin.dashboard'));
    }
}
