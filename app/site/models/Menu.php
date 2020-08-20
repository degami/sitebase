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

use \App\Base\Abstracts\Models\ModelWithChildren;
use \App\Base\Traits\WithWebsiteTrait;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use \Psr\Container\ContainerInterface;

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
 * @method string getBreadcumb()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class Menu extends ModelWithChildren
{
    use WithWebsiteTrait;

    /**
     * gets link URL
     *
     * @return string
     * @throws BasicException
     */
    public function getLinkUrl()
    {
        if (trim($this->getHref()) != '') {
            return $this->getHref();
        }

        if ($this->getRewriteId()) {
            /**
             * @var Rewrite $rewrite
            */
            $rewrite = $this->getContainer()->call([Rewrite::class, 'load'], ['id' => $this->getRewriteId()]);
            return $this->getRouting()->getUrl('/') . '/' . ltrim($rewrite->getUrl(), '/');
        }

        return "#";
    }

    /**
     * gets all menu names
     *
     * @param  ContainerInterface $container
     * @return array
     */
    public static function allMenusNames(ContainerInterface $container)
    {
        return array_map(
            function ($el) use ($container) {
                return  (object)($el);
            },
            $container->get('db')->query(
                "SELECT menu_name FROM `".static::defaultTableName()."` WHERE 1 GROUP BY menu_name"
            )->fetchAll()
        );
    }

    /**
     * gets parent object if any
     *
     * @return self|null
     */
    public function getParentObj()
    {
        if ($this->parent_id == null) {
            return null;
        }

        return $this->getContainer()->call([Menu::class, 'load'], ['id' => $this->parent_id]);
    }

    /**
     * gets parent ids tree
     *
     * @return string
     */
    public function getParentIds()
    {
        if ($this->parent_id == null) {
            return $this->id;
        }

        return $this->getParentObj()->getParentIds() . '/' . $this->id;
    }
}
