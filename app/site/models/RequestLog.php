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

use \App\Base\Abstracts\Model;
use \App\Base\Abstracts\BaseHtmlPage;
use \Symfony\Component\HttpFoundation\Request;
use \App\Base\Traits\WithWebsiteTrait;

/**
 * Request Log Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getMethod()
 * @method string getUserId()
 * @method string getIpAddress()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class RequestLog extends Model
{
    use WithWebsiteTrait;

    /**
     * fills log with request object
     *
     * @param  Request           $request
     * @param  BaseHtmlPage|null $controller
     * @return self
     */
    public function fillWithRequest(Request $request, BaseHtmlPage $controller = null)
    {
        $this->setUrl($request->getUri());
        $this->setMethod($request->getMethod());
        $this->setIpAddress($request->getClientIp());

        $this->setWebsiteId($this->matchWebsite($request->getHost()));

        if ($controller != null && $controller->hasLoggedUser()) {
            $this->setUserId($controller->getCurrentUser()->id);
        }

        return $this;
    }

    /**
     * matches request log with website
     *
     * @param  string $host
     * @return integer|null
     */
    private function matchWebsite($host)
    {
        foreach ($this->getDb()->table('website') as $website_row) {
            if (preg_match("/".$website_row->domain."/", $host)) {
                return $website_row->id;
            }
        }

        return null;
    }
}
