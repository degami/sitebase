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

namespace App\Base\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Controllers\BasePage;
use DateTime;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Traits\WithWebsiteTrait;

/**
 * Request Log Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getMethod()
 * @method int getUserId()
 * @method string getIpAddress()
 * @method string getUserAgent()
 * @method int getResponseCode()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrl(string $url)
 * @method self setMethod(string $method)
 * @method self setUserId(int $user_id)
 * @method self setIpAddress(string $ip_address)
 * @method self setUserAgent(string $user_agent)
 * @method self setResponseCode(int $response_code)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class RequestLog extends BaseModel
{
    use WithWebsiteTrait;

    /**
     * fills log with request object
     *
     * @param Request $request
     * @param BasePage|null $controller
     * @return $this
     */
    public function fillWithRequest(Request $request, ?BasePage $controller = null): RequestLog
    {
        $this->setUrl($request->getUri());
        $this->setMethod($request->getMethod());
        $this->setIpAddress($request->getClientIp());
        $this->setUserAgent($_SERVER['HTTP_USER_AGENT']);
        $this->setWebsiteId($this->matchWebsite($request->getHost()));

        if ($controller instanceof BasePage && method_exists($controller, 'hasLoggedUser') && $controller->hasLoggedUser()) {
            $this->setUserId($controller->getCurrentUser()->id);
        }

        return $this;
    }

    /**
     * matches request log with website
     *
     * @param string $host
     * @return int|null
     */
    private function matchWebsite(string $host): ?int
    {
        foreach (Website::getCollection() as $website) {
            /** @var Website $website */
            if (preg_match("/" . $website->getDomain() . "/", $host)) {
                return $website->getId();
            }
        }

        return null;
    }
}
