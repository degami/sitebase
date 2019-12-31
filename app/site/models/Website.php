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

/**
 * Website Model
 *
 * @method string getSiteName()
 * @method string getDomain()
 * @method string getAliases()
 * @method integer getDefaultLocale()
 */
class Website extends Model
{
    public function prePersist()
    {
        $this->aliases = implode(",", array_filter(array_map('trim', explode(",", $this->aliases))));
    }
}
