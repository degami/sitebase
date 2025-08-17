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
use App\Base\Abstracts\Models\FrontendModel;
use DateTime;
use Exception;
use App\Base\GraphQl\GraphQLExport;

/**
 * News Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getTitle()
 * @method string getContent()
 * @method int getUserId()
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
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
#[GraphQLExport]
class News extends FrontendModel
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'news';
    }

    /**
     * {@inheritdoc}
     *
     * @return string[]
     */
    public static function exposeToIndexer(): array
    {
        return ['title', 'content', 'date'];
    }

    /**
     * gets formatted Date
     *
     * @return string
     * @throws Exception
     */
    public function getDate(): string
    {
        $date_format = App::getInstance()->getSiteData()->getDateFormat();
        return (new DateTime($this->date))->format($date_format);
    }
}
