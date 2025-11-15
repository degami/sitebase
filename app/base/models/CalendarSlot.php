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
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;

/**
 * Calendar Slot Model
 * 
 * @method int getId()
 * @method int getCalendarId()
 * @method \DateTime getStart()
 * @method \DateTime getEnd()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setCalendarId(int $calendar_id)
 * @method self setStart(\DateTime $start)
 * @method self setEnd(\DateTime $end)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
class CalendarSlot extends BaseModel
{
    use WithOwnerTrait, WithWebsiteTrait;

    protected ?Calendar $calendar = null;

    /**
     * Get calendar
     *
     * @return Calendar|null
     */
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

    /**
     * Set calendar
     * 
     * @param Calendar $calendar
     * @return self
     */
    public function setCalendar(Calendar $calendar): self
    {
        $this->calendar = $calendar;
        $this->setCalendarId($calendar->getId());
        return $this;
    }
}
