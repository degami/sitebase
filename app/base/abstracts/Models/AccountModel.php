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

namespace App\Base\Abstracts\Models;

use App\Base\Interfaces\Model\AccountInterface;

/**
 * A account model
 */
abstract class AccountModel extends BaseModel implements AccountInterface
{
    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }
}
