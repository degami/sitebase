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
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Routing\RouteInfo;
use App\Base\Traits\WithChildrenTrait;
use App\Base\Traits\WithParentTrait;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * Rewrite Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getRoute()
 * @method string getLocale()
 * @method int getUserId()
 * @method int!null getParentId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrl(string $url)
 * @method self setRoute(string $route)
 * @method self setLocale(string $locale)
 * @method self setUserId(int $user_id)
 * @method self setParentId(int!null $parent_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Rewrite extends BaseModel
{
    use WithOwnerTrait;
    use WithWebsiteTrait;
    use WithParentTrait;
    use WithChildrenTrait;

    /**
     * @var array rewrite translations
     */
    protected array $translations = [];

    /**
     * gets object translations
     *
     * @param bool $reset
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    public function getTranslations(bool $reset = false): array
    {
        $this->checkLoaded();

        if (empty($this->translations) || $reset == true) {
            $elements = array_filter(
                array_map(
                    function ($el) {
                        if ($el->id == $this->getId() || $el->locale == $this->getLocale()) {
                            return null;
                        }
                        return [
                            'locale' => $el->destination_locale,
                            'rewrite' => $this->containerCall([Rewrite::class, 'load'], ['id' => $el->destination])
                        ];
                    },
                    [] + $this->getDb()->table('rewrite_translation')->where('source', $this->getId())->fetchAll()
                )
            );

            foreach ($elements as $item) {
                $this->translations[$item['locale']] = $item['rewrite'];
            }
        }
        return $this->translations;
    }

    /**
     * gets route info object
     *
     * @return RouteInfo
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getRouteInfo(): RouteInfo
    {
        return $this->getWebRouter()->getRequestInfo('GET', $this->getRoute());
    }
}
