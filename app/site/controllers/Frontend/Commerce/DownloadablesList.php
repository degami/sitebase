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

namespace App\Site\Controllers\Frontend\Commerce;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\FrontendPage;
use App\Base\Routing\RouteInfo;
use App\Site\Models\DownloadableProduct;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * Downloadables List Page
 */
class DownloadablesList extends FrontendPage
{
    /**
     * @var RouteInfo|null route info object
     */
    protected ?RouteInfo $route_info = null;

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
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'downloadables';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'commerce/downloadables_list';
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && boolval(\App\App::getInstance()->getEnv('ENABLE_COMMERCE'));
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
        $collection = $this->containerCall([DownloadableProduct::class, 'getCollection']);
        $data = $this->containerCall([$collection, 'paginate'] /*, ['page_size' => 10]*/);
        return $this->template_data += [
            'page_title' => $this->getUtils()->translate('Downloadables', locale: $this->getCurrentLocale()),
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
                $this->locale = 'en';
            }
        }
        $this->getApp()->setCurrentLocale($this->locale);
        return $this->locale;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('Downloadables', locale: $this->getCurrentLocale());
    }
}
