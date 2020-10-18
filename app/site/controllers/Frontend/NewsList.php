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

namespace App\Site\Controllers\Frontend;

use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\FrontendPage;
use \App\Site\Models\News;
use \App\Site\Routing\RouteInfo;

/**
 * News List Page
 */
class NewsList extends FrontendPage
{
    /**
     * @var string locale
     */
    protected $locale = null;

    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * @var RouteInfo route info object
     */
    protected $route_info = null;

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return '';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'news';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'news_list';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     */
    protected function getTemplateData()
    {
        $data = $this->getContainer()->call([News::class, 'paginate'], ['condition' => ['locale' => $this->getCurrentLocale()], 'order' => ['date' => 'DESC']]);
        return $this->templateData += [
            'page_title' => $this->getUtils()->translate('News', $this->getCurrentLocale()),
            'news' => $data['items'],
            'total' => $data['total'],
            'current_page' => $data['page'],
            'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     * @throws BasicException
     */
    public function getCurrentLocale()
    {
        if (!$this->locale) {
            $this->locale = parent::getCurrentLocale();
            if ($this->locale == null) {
                $this->locale = 'en';
            }
        }
        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }
}
