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
namespace App\Base\Traits;

use \App\Base\Abstracts\ContainerAwareObject;
use \App\Site\Models\Website;

/**
 * Trait for elements with Website
 */
trait WithWebsiteTrait
{
    /**
     * gets website
     * @return Website
     */
    public function getWebsite()
    {
        $this->checkLoaded();

        return $this->getContainer()->make(Website::class)->fill($this->website()->fetch());
    }
}
