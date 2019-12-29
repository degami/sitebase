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
use \App\Base\Traits\WithWebsiteTrait;
use \App\Base\Traits\WithOwnerTrait;

/**
 * Rewrite Model
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getRoute()
 * @method int getUserId()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 */
class Rewrite extends Model
{
    /** @var array rewrite translations */
    protected $translations = [];

    use WithWebsiteTrait, WithOwnerTrait;

    /**
     * gets object translations
     * @return array
     */
    public function getTranslations()
    {
        $this->checkLoaded();

        if (empty($this->translations)) {
            $elements = array_filter(array_map(function ($el) {
                if ($el->id == $this->getId() || $el->locale == $this->getLocale()) {
                    return null;
                }
                return [
                    'locale' => $el->destination_locale,
                    'rewrite' => $this->getContainer()->call([Rewrite::class, 'load'], ['id' => $el->destination])
                ];
            }, [] + $this->getDb()->table('rewrite_translation')->where('source', $this->getId())->fetchAll()));

            foreach ($elements as $item) {
                $this->translations[$item['locale']] = $item['rewrite'];
            }
        }
        return $this->translations;
    }

    /**
     * gets route info object
     * @return RouteInfo
     */
    public function getRouteInfo()
    {
        return $this->getRouting()->getRequestInfo($this->getContainer(), 'GET', $this->getRoute());
    }
}
