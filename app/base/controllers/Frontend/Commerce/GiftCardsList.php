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

namespace App\Base\Controllers\Frontend\Commerce;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\FrontendPage;
use App\Base\Abstracts\Controllers\FrontendPageWithLang;
use App\Base\Models\GiftCard;
use App\Base\Routing\RouteInfo;
use App\Base\Tools\Utils\SiteData;
use DI\DependencyException;
use DI\NotFoundException;
use Mpdf\Gif\Gif;

/**
 * Gift Cards List Page
 */
class GiftCardsList extends FrontendPageWithLang
{
    /**
     * @var RouteInfo|null route info object
     */
    protected ?RouteInfo $route_info = null;

    /**
     * @var string page title
     */
    protected ?string $page_title = 'Gift Cards';

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup(): string
    {
        return '';
    }

    /**
     * return route path
     *
     * @return array
     */
    public static function getRoutePath(): array
    {
        return [
            'frontend.commerce.giftcardslist' => 'giftcards',
            'frontend.commerce.giftcardslist.withlang' => '/{lang:[a-z]{2}}/giftcards',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'commerce/giftcards_list';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && boolval(\App\App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE'));
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getTemplateData(): array
    {
        /** @var \App\Base\Abstracts\Models\BaseCollection $collection */
        $collection = $this->containerCall([GiftCard::class, 'getCollection']);
        $collection->addCondition(['locale' => $this->getCurrentLocale()]);
        $data = $this->containerCall([$collection, 'paginate'] /*, ['page_size' => 10]*/);
        return $this->template_data += [
            'page_title' => $this->getUtils()->translate('Gift Cards', locale: $this->getCurrentLocale()),
            'products' => $data['items'],
            'total' => $data['total'],
            'current_page' => $data['page'],
            'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this, $data['page_size']),
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentLocale(): string
    {
        if (!$this->locale) {
            $this->locale = parent::getCurrentLocale();
            if ($this->locale == null) {
                $this->locale = SiteData::DEFAULT_LOCALE;
            }
        }
        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }
}
