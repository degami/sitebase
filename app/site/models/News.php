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

use \App\Base\Abstracts\FrontendModel;
use \App\Base\Traits\WithOwnerTrait;

/**
 * News Model
 *
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getTitle()
 * @method string getContent()
 * @method \DateTime getDate()
 * @method int getUserId()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class News extends FrontendModel
{
    use WithOwnerTrait;

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getRewritePrefix()
    {
        return 'news';
    }

    public function getDate()
    {
        return (new \DateTime($this->date))->format('Y-m-d');
    }
}
