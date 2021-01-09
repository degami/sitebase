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
use App\Base\Traits\WithOwnerTrait;
use DateTime;
use Exception;

/**
 * Media Element Rewrite Pivot Model
 *
 * @method int getId()
 * @method int getMediaElementId()
 * @method int getRewriteId()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setMediaElementId(int $media_element_id)
 * @method self setRewriteId(int $rewrite_id)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class MediaElementRewrite extends BaseModel
{
    use WithOwnerTrait;

    /**
     * gets Media Element object
     *
     * @return MediaElement
     * @throws Exception
     */
    public function getMediaElement(): MediaElement
    {
        $this->checkLoaded();

        return $this->getContainer()->make(MediaElement::class, ['db_row' => $this->media_element()->fetch()]);
    }

    /**
     * gets Rewrite object
     *
     * @return Rewrite
     * @throws Exception
     */
    public function getRewrite(): Rewrite
    {
        $this->checkLoaded();

        return $this->getContainer()->make(Rewrite::class, ['db_row' => $this->rewrite()->fetch()]);
    }
}
