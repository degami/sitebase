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

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\ModelWithChildren;
use App\Base\Traits\WithParentTrait;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Models\Rewrite;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use App\Base\GraphQl\GraphQLExport;

/**
 * Menu Item Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getTitle()
 * @method string getLocale()
 * @method string getMenuName()
 * @method int getRewriteId()
 * @method string getHref()
 * @method string getTarget()
 * @method int getParentId()
 * @method string getBreadcrumb()
 * @method int getLevel()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method \App\Base\Models\Menu[] getChildren()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setTitle(string $title)
 * @method self setLocale(string $locale)
 * @method self setMenuName(string $menu_name)
 * @method self setRewriteId(int $rewrite_id)
 * @method self setHref(string $href)
 * @method self setTarget(string $target)
 * @method self setParentId(int $parent_id)
 * @method self setBreadcrumb(string $breadcrumb)
 * @method self setLevel(int $level)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
#[GraphQLExport]
class Menu extends ModelWithChildren
{
    use WithParentTrait;
    use WithWebsiteTrait;

    /**
     * gets link URL
     *
     * @return string
     * @throws BasicException
     */
    #[GraphQLExport]
    public function getLinkUrl(bool $absolute = true): string
    {
        if (trim((string) $this->getHref()) != '') {
            return $this->getHref();
        }

        if ($this->getRewriteId()) {
            /**
             * @var Rewrite $rewrite
             */
            $rewrite = App::getInstance()->containerCall([Rewrite::class, 'load'], ['id' => $this->getRewriteId()]);
            return ($absolute ? App::getInstance()->getWebhooksRouter()->getUrl('/') : '') . '/' . ltrim($rewrite->getUrl(), '/');
        }

        return "#";
    }

    /**
     * gets internal route
     * 
     * @return string|null
     */
    #[GraphQLExport]
    public function getInternalRoute() : ?string 
    {    
        if ($this->getRewriteId()) {
            /**
             * @var Rewrite $rewrite
             */
            $rewrite = App::getInstance()->containerCall([Rewrite::class, 'load'], ['id' => $this->getRewriteId()]);
            return $rewrite->getRoute();
        }

        return null;
    }

    /**
     * gets all menu names
     *
     * @return array
     */
    public static function allMenusNames(): array
    {
        return array_map(
            function ($el) {
                return (object)($el);
            },
            App::getInstance()->getDb()->query(
                "SELECT menu_name FROM `" . static::defaultTableName() . "` WHERE 1 GROUP BY menu_name"
            )->fetchAll()
        );
    }

    public function prePersist(array $persistOptions = []): BaseModel
    {
        $this->setBreadcrumb($this->getParentIds());
        $this->setLevel(max(count(explode("/", (string) $this->breadcrumb)) - 1, 0));
        return parent::prePersist($persistOptions);
    }
}
