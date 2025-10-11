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

namespace App\Site\Models;

use App\App;
use App\Base\Abstracts\Models\ModelWithLocation;
use App\Base\Abstracts\Models\ModelWithLocationCollection;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithRewriteTrait;
use App\Base\Traits\IndexableTrait;
use App\Base\Traits\FrontendModelTrait;
use DateTime;
use App\Base\GraphQl\GraphQLExport;

/**
 * Event Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getTitle()
 * @method string getContent()
 * @method int getUserId()
 * @method DateTime getDate()
 * @method float getLatitude()
 * @method float getLongitude()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrl(string $url)
 * @method self setLocale(string $locale)
 * @method self setTitle(string $title)
 * @method self setContent(string $content)
 * @method self setDate(DateTime $date)
 * @method self setUserId(int $user_id)
 * @method self setLatitude(float $latitude)
 * @method self setLongitude(float $longitude)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
#[GraphQLExport]
class Event extends ModelWithLocation
{
    use WithOwnerTrait;
    use WithWebsiteTrait;
    use WithRewriteTrait;
    use IndexableTrait;
    use FrontendModelTrait;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'event';
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public static function exposeToIndexer(): array
    {
        return ['title', 'content', 'date', 'latitude', 'longitude'];
    }

    /**
     * gets formatted Date
     *
     * @return string
     * @throws Exception
     */
    #[GraphQLExport]
    public function getDate(): string
    {
        $date_format = App::getInstance()->getSiteData()->getDateTimeFormat();
        return (new DateTime($this->date))->format($date_format);
    }

    /**
     * gets latitude
     *
     * @return float
     */
    #[GraphQLExport]
    public function getLatitude(): float
    {
        return (float) $this->getData('latitude');
    }

    /**
     * gets longitude
     *
     * @return float
     */
    #[GraphQLExport]
    public function getLongitude(): float
    {
        return (float) $this->getData('longitude');
    }

    /**
     * gets location
     */
    public function getLocation(): array
    {
        return ['latitude' => $this->getLatitude(), 'longitude' => $this->getLongitude()];
    }

    /**
     * get elements nearby within radius (in meters)
     */
    public function nearBy(float $radius) : ModelWithLocationCollection|BaseCollection
    {
        $collection = static::getCollection();
        if (is_callable([$collection, 'withinRange'])) {
            return $collection->withinRange($this->getLatitude(), $this->getLongitude(), $radius)
                ->addCondition(['locale' => $this->getLocale()])
                ->addCondition(['id:not' => $this->getId()]);
        }

        return $collection;
    }

    /**
     * return page title
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->html_title ?: $this->title;
    }

    public Function canSaveVersions() : bool
    {
        return true;
    }
}