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
 * Rewrite Translation Model
 *
 * @method int getId()
 * @method int getSource()
 * @method string getSourceLocale()
 * @method int getDestination()
 * @method string getDestinationLocale()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class RewriteTranslation extends BaseModel
{
}
