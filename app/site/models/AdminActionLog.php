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

use App\Base\Abstracts\Controllers\BasePage;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\HttpFoundation\Request;

/**
 * Admin Action Log Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getMethod()
 * @method string getUserId()
 * @method string getIpAddress()
 * @method string getAction()
 * @method string getUserAgent()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrl(string $url)
 * @method self setMethod(string $method)
 * @method self setUserId(string $user_id)
 * @method self setIpAddress(string $ip_address)
 * @method self setAction(string $action)
 * @method self setRouteInfo(string $route_info)
 * @method self setLogData(string $log_data)
 * @method self setUserAgent(string $user_agent)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class AdminActionLog extends RequestLog
{
    /**
     * fills log with request object
     *
     * @param Request $request
     * @param BasePage|null $controller
     * @return $this|AdminActionLog
     * @throws BasicException
     */
    public function fillWithRequest(Request $request, BasePage $controller = null): RequestLog
    {
        parent::fillWithRequest($request, $controller);
        if ($controller instanceof BasePage) {
            $this->setRouteInfo(serialize($controller->getRouteInfo()->getData()));
            $this->setAction($controller->getRouteInfo()->getRouteName());
            if (method_exists($controller, 'getAdminActionLogData')) {
                $this->setLogData(serialize($this->getContainer()->call([$controller, 'getAdminActionLogData'])));
            }
        }
        return $this;
    }

    /**
     * gets route info
     *
     * @return mixed
     */
    public function getRouteInfo(): mixed
    {
        return unserialize($this->getData('route_info'));
    }

    /**
     * gets log data
     *
     * @return mixed
     */
    public function getLogData(): mixed
    {
        return unserialize($this->getData('log_data'));
    }
}
