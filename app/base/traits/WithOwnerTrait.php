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

use \App\Site\Models\User;

/**
 * Trait for elements with getOwner
 */
trait WithOwnerTrait
{
    /**
     * gets owner
     *
     * @return User
     */
    public function getOwner()
    {
        $this->checkLoaded();

        return $this->getContainer()->make(User::class, ['db_row' => $this->user()->fetch()]);
    }
}
