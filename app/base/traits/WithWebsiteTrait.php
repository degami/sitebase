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

namespace App\Base\Traits;

use App\App;
use App\Base\Models\Website;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\GraphQl\GraphQLExport;

/**
 * Trait for elements with Website
 */
trait WithWebsiteTrait
{
    /** @var Website|null  */
    protected ?Website $websiteModel = null;

    /**
     * gets website
     *
     * @return Website
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[GraphQLExport]
    public function getWebsite(): Website
    {
        $this->checkLoaded();

        if ($this->websiteModel == null) {
            $this->websiteModel = App::getInstance()->containerMake(Website::class, ['db_row' => $this->website()->fetch()]);
        }

        return $this->websiteModel;
    }
}
