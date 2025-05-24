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

namespace App\Base\Abstracts\Controllers;

use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Routing\RouteInfo;
use App\Base\Traits\FrontendPageTrait;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Exceptions\NotFoundException;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * Base for a page displaying a model
 */
abstract class FrontendPageWithObject extends FrontendPage
{
    use FrontendPageTrait;

    /**
     * {@inheritdoc}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender() : BasePage|Response
    {
        $route_data = $this->getRouteData();

        if (isset($route_data['id'])) {
            $this->setObject($this->containerCall([static::getObjectClass(), 'load'], ['id' => $route_data['id']]));
        }

        return parent::beforeRender();
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws Exception
     * @throws Throwable
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response
    {
        $return = parent::process($route_info, $route_data);

        if (is_null($this->getObject())) {
            throw new Exception('Missing "object" property');
        }

        if (!($this->getObject() instanceof BaseModel && is_a($this->getObject(), $this->getObjectClass()) && $this->getObject()->isLoaded())) {
            throw new NotFoundException();
        }

        return $return;
    }

    /**
     * gets object title
     *
     * @return string
     */
    public function getObjectTitle(): string
    {
        return $this->getObject()->getTitle() ?: '';
    }

    /**
     * gets cache key
     */
    public function getCacheKey() : string
    {

        $website_id = $this->getSiteData()->getCurrentWebsiteId();
        $locale = $this->getRewrite()?->getLocale() ?? $this->getSiteData()->getDefaultLocale();

        $prefix = 'site.'.$website_id.'.'.$locale.'.';

        if ($this->getRewriteObject() != null) {
            $prefix = 'site'.
            '.' . $this->getRewriteObject()->getWebsiteId().
            '.' . $this->getRewriteObject()->getLocale() . 
            '.';
        }

        $prefix .= trim(str_replace("/", ".", $this->getRouteInfo()->getRouteName()));

        if ($this->getObject() instanceof FrontendModel) {
            return $this->normalizeCacheKey($prefix . '.' . $this->getUtils()->slugify($this->getObject()->getUrl(), false));
        }

        return $this->normalizeCacheKey($prefix . '.id.'. $this->getObject()->getId());
    }

    /**
     * {@inheritdoc}
     */
    public function getInfo() : array
    {
        return parent::getInfo() + ['object_id' => $this->getObject()->getId()];
    }

    /**
     * author info are available
     * 
     * @return bool
     */
    public function canShowAuthorInfo() : bool
    {
        if (is_subclass_of($this->getObject(), FrontendModel::class)) {
            return true;
        }

        return false;
    }

    /**
     * gets object class name for loading
     *
     * @return string
     */
    abstract public static function getObjectClass(): string;
}
