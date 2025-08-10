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
use DateTime;

/**
 * Language Model
 *
 * @method int getId()
 * @method string getIso2()
 * @method string getIso3()
 * @method string getNameEn()
 * @method string getNameNative()
 * @method string getCapital()
 * @method string getLatitude()
 * @method string getLongitude()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method string setIso2()
 * @method string setIso3()
 * @method string setNameEn()
 * @method string setNameNative()
 * @method string setCapital()
 * @method string setLatitude()
 * @method string setLongitude()
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Country extends BaseModel
{
        /**
     * gets latitude
     *
     * @return float
     */
    public function getLatitude(): float
    {
        return (float) $this->getData('latitude');
    }

    /**
     * gets longitude
     *
     * @return float
     */
    public function getLongitude(): float
    {
        return (float) $this->getData('longitude');
    }

    /**
     * gets location
     */
    public function getCapitalLocation(): array
    {
        return ['latitude' => $this->getLatitude(), 'longitude' => $this->getLongitude()];
    }
}