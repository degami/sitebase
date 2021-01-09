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

use App\Base\Abstracts\Models\BaseModel;
use DateTime;

/**
 * Mail Log Model
 *
 * @method int getId()
 * @method string getFrom()
 * @method string getTo()
 * @method string getSubject()
 * @method string getTemplateName()
 * @method int getResult()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setFrom(string $from)
 * @method self setTo(string $to)
 * @method self setSubject(string $subject)
 * @method self setTemplateName(string $template_name)
 * @method self setResult(int $result)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class MailLog extends BaseModel
{
}
