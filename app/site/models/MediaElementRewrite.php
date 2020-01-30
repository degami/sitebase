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

use \App\Base\Abstracts\Model;
use \App\Base\Traits\WithOwnerTrait;
use \App\App;
use \Exception;

/**
 * Media Element Rewrite Pivot Model
 *
 * @method int getId()
 * @method int getMediaElementId()
 * @method int getRewriteId()
 * @method int getUserId()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class MediaElementRewrite extends Model
{
    use WithOwnerTrait;

    /**
     * gets Media Element object
     *
     * @return MediaElement
     */
    public function getMediaElement()
    {
        $this->checkLoaded();

        return $this->getContainer()->make(MediaElement::class)->fill($this->media_element()->fetch());
    }

    /**
     * gets Rewrite object
     *
     * @return Rewrite
     */
    public function getRewrite()
    {
        $this->checkLoaded();

        return $this->getContainer()->make(Rewrite::class)->fill($this->rewrite()->fetch());
    }
}
