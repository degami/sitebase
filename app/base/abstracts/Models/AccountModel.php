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
namespace App\Base\Abstracts\Models;

use \Psr\Container\ContainerInterface;
use \Exception;

/**
 * A account model
 */
abstract class AccountModel extends BaseModel
{
    /**
     * gets user role
     *
     * @return Role
     */
    abstract public function getRole();
}
