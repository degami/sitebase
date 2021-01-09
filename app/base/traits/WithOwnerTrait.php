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

use App\Site\Models\User;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Trait for elements with getOwner
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
    public function getOwner(): User
    {
        $this->checkLoaded();

        return $this->getContainer()->make(User::class, ['db_row' => $this->user()->fetch()]);
    }
}
