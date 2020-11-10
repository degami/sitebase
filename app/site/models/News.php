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

use \App\Base\Abstracts\Models\FrontendModel;
use DateTime;
use Exception;

/**
 * News Model
 *
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getTitle()
 * @method string getContent()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class News extends FrontendModel
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getRewritePrefix()
    {
        return 'news';
    }

    /**
     * {@inheritdocs}
     *
     * @return string[]
     */
    public static function exposeToIndexer()
    {
        return ['title', 'content', 'date'];
    }

    /**
     * gets formatted Date
     *
     * @return string
     * @throws Exception
     */
    public function getDate()
    {
        $date_format = $this->getSiteData()->getDateFormat();
        return (new DateTime($this->date))->format($date_format);
    }
}
