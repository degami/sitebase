<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Traits;

use App\App;
use App\Base\Models\User;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\GraphQl\GraphQLExport;
use App\Base\Versions\VersionExclude;
use LessQL\Result;

/**
 * Trait for elements with getOwner
 * 
 * @method Result user()
 */
trait WithOwnerTrait
{
    /**
     * gets owner
     *
     * @return User
     * @throws DependencyException
     * @throws NotFoundException
     */
    #[GraphQLExport]
    #[VersionExclude]
    public function getOwner(): User
    {
        $this->checkLoaded();

        return App::getInstance()->containerMake(User::class, ['db_row' => $this->user()->fetch()]);
    }
}
