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

use \App\Base\Abstracts\Models\BaseModel;
use DateTime;

/**
 * Language Model
 *
 * @method int getId()
 * @method string getLocale()
 * @method string getName()
 * @method string getNative()
 * @method string getFamily()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setLocale(string $locale)
 * @method self setName(string $name)
 * @method self setNative(string $native)
 * @method self setFamily(string $family)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Language extends BaseModel
{
}
