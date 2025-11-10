<?php

namespace App\Site\Models;

use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Traits\ProductTrait;
use App\Base\GraphQl\GraphQLExport;
use App\Base\Models\Calendar;

/**
 * @method int getId()
 * @method string getSku()
 * @method string getTitle()
 * @method string getContent()
 * @method int getTaxClassId()
 * @method int getWebsiteId()
 * @method int getUserId()
 * @method float getPrice()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getMetaKeywords()
 * @method string getMetaDescription()
 * @method string getHtmlTitle()
 * @method int getCalendarId()
 * @method int getDuration()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setSku(string $sku)
 * @method self setTitle(string $title)
 * @method self setContent(string $content)
 * @method self setTaxClassId(int $tax_class_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUserId(int $user_id)
 * @method self setPrice(float $price)
 * @method self setUrl(string $url)
 * @method self setLocale(string $locale)
 * @method self setMetaKeywords(string $meta_keywords)
 * @method self setMetaDescription(string $meta_description)
 * @method self setHtmlTitle(string $html_title)
 * @method self setCalendarId(int $calendar_id)
 * @method self setDuration(int $duration)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
#[GraphQLExport]
class CalendarReservation extends FrontendModel implements ProductInterface
{
    use ProductTrait;

    protected ?Calendar $calendar = null;

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isPhysical(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    #[GraphQLExport]
    public function getSku(): string
    {
        return $this->getData('sku')?? 'calendar_reservation_' . $this->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'calendar_reservation';
    }

    public function getCalendar(): ?Calendar
    {
        if ($this->calendar != null) {
            return $this->calendar;
        }

        if ($this->getCalendarId() == null) {
            return null;
        }

        return $this->setCalendar(Calendar::load($this->getCalendarId()))->calendar;
    }

    public function setCalendar(Calendar $calendar): self
    {
        $this->calendar = $calendar;
        $this->setCalendarId($calendar->getId());

        return $this;
    }
}
