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

use \App\Base\Abstracts\Models\BaseModel;
use \App\Base\Traits\WithWebsiteTrait;
use \App\Base\Traits\WithOwnerTrait;
use App\Site\Routing\RouteInfo;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;

/**
 * Rewrite Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getRoute()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class Rewrite extends BaseModel
{
    /**
     * @var array rewrite translations
     */
    protected $translations = [];

    use WithWebsiteTrait, WithOwnerTrait;

    /**
     * gets object translations
     *
     * @param false $reset
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    public function getTranslations($reset = false)
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
                            'rewrite' => $this->getContainer()->call([Rewrite::class, 'load'], ['id' => $el->destination])
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
     */
    public function getRouteInfo()
    {
        return $this->getRouting()->getRequestInfo($this->getContainer(), 'GET', $this->getRoute());
    }
}
