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
use App\Base\Traits\WithParentTrait;
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
    use WithWebsiteTrait, WithParentTrait;

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
            return $this->getWebRouter()->getUrl('/') . '/' . ltrim($rewrite->getUrl(), '/');
        }

        return "#";
    }

    /**
     * gets all menu names
     *
     * @param ContainerInterface $container
     * @return array
     */
    public static function allMenusNames(ContainerInterface $container)
    {
        return array_map(
            function ($el) use ($container) {
                return (object)($el);
            },
            $container->get('db')->query(
                "SELECT menu_name FROM `" . static::defaultTableName() . "` WHERE 1 GROUP BY menu_name"
            )->fetchAll()
        );
    }

    public function prePersist()
    {
        $this->breadcrumb = $this->getParentIds();
        $this->level = max(count(explode("/", $this->breadcrumb))-1, 0);
        return parent::prePersist();
    }
}
