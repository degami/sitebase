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
use DateTime;

/**
 * User Notification Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getUserId()
 * @method int getSenderId()
 * @method mixed getMessage()
 * @method boolean getRead()
 * @method int getReplyTo()
 * @method DateTime getReadAt()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUserId(int $user_id)
 * @method self setSenderId(int $sender)
 * @method self setMessage(mixed $session_data)
 * @method self setRead(boolean $read)
 * @method self setReplyTo(int $notificagtion_id)
 * @method self setReadAt(DateTime $read_at)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class UserNotification extends BaseModel
{
    use WithOwnerTrait;
    use WithWebsiteTrait;

    /**
     * gets owner
     *
     * @return User|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSender(): ?User
    {
        $this->checkLoaded();

        if ($this->getSenderId() == null) {
            return null;
        }

        return $this->containerCall([User::class, 'load'], ['id' => $this->getSenderId()]);
        //return $this->containerMake(User::class, ['db_row' => $this->referenced('user', ['id' => $this->getSenderId()])->fetch()]);
    }
}
