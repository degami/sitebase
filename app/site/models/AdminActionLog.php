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
namespace App\Site\Models;

use \App\Site\Routing\RouteInfo;
use \App\Base\Abstracts\Controllers\BasePage;
use \Symfony\Component\HttpFoundation\Request;

/**
 * Admin Action Log Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getMethod()
 * @method string getUserId()
 * @method string getIpAddress()
 * @method RouteInfo getRouteInfo()
 * @method mixed getLogData()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class AdminActionLog extends RequestLog
{
    /**
     * fills log with request object
     *
     * @param  Request           $request
     * @param  BaseHtmlPage|null $controller
     * @return self
     */
    public function fillWithRequest(Request $request, BasePage $controller = null)
    {
        parent::fillWithRequest($request, $controller);
        $this->setRouteInfo(serialize($controller->getRouteInfo()));
        $this->setAction($controller->getRouteInfo()->getRouteName());
        if (method_exists($controller, 'getAdminActionLogData')) {
            $this->setLogData(serialize($this->getContainer()->call([$controller, 'getAdminActionLogData'])));
        }
        return $this;
    }

    public function getRouteInfo()
    {
        $route_info = unserialize($this->getData('route_info'));
        return $route_info;
    }

    public function getLogData()
    {
        $log_data = unserialize($this->getData('log_data'));
        return $log_data;
    }
}
