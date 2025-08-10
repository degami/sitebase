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

namespace App\Base\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Order Status Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getStatus()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setStatus(string $sftatus)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderStatus extends BaseModel
{
    public const CREATED = 'created';
    public const PAID = 'paid';
    public const NOT_PAID = 'not_paid';
    public const WAITING_FOR_PAYMENT = 'waiting_for_payment';
    public const CANCELED = 'canceled';
    public const SHIPPED = 'shipped';
    public const COMPLETE = 'complete';

    use WithWebsiteTrait;

    public static function getByStatus(string $status) : static
    {
        return static::getCollection()->where(['status' => $status])->getFirst();
    }
}
