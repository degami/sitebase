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

namespace App\Base\Interfaces\Model;

use App\Base\Models\Role;

interface AccountInterface
{
    /**
     * gets user role
     *
     * @return Role
     */
    public function getRole(): Role;
}