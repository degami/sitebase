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
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Models\Rewrite;
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
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * gets Media Element object
     *
     * @return MediaElement
     * @throws Exception
     */
    public function getMediaElement(): MediaElement
    {
        $this->checkLoaded();

        return App::getInstance()->containerMake(MediaElement::class, ['db_row' => $this->media_element()->fetch()]);
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

        return App::getInstance()->containerMake(Rewrite::class, ['db_row' => $this->rewrite()->fetch()]);
    }
}
