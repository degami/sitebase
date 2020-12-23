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

use \App\Site\Models\Website;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Trait for elements with Website
 */
trait WithWebsiteTrait
{
    /** @var Website  */
    protected $websiteModel = null;

    /**
     * gets website
     *
     * @return Website
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getWebsite(): Website
    {
        $this->checkLoaded();

        if ($this->websiteModel == null) {
            $this->websiteModel = $this->getContainer()->make(Website::class, ['db_row' => $this->website()->fetch()]);
        }

        return $this->websiteModel;
    }
}
