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

namespace App\Base\Traits;

use App\Base\Models\Rewrite;
use App\Base\Routing\RouteInfo;
use App\Base\Abstracts\Controllers\FrontendPageWithObject;
use App\App;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Exceptions\NotFoundException;
use Exception;
use App\Base\GraphQl\GraphQLExport;

/**
 * Trait for elements with rewrite
 */
trait WithRewriteTrait
{
    /**
     * @var Rewrite|null rewrite object
     */
    protected ?Rewrite $rewriteObj = null;


    /**
     * gets object rewrite model
     *
     * @return Rewrite|null
     * @throws Exception
     */
    #[GraphQLExport]
    public function getRewrite(): ?Rewrite
    {
        $this->checkLoaded();

        if (!($this->rewriteObj instanceof Rewrite)) {
            try {
                $rewriteObj = Rewrite::getCollection()->where(['route' => '/' . $this->getRewritePrefix() . '/' . $this->getId()])->getFirst();
                if (!$rewriteObj) {
                    throw new NotFoundException();
                }
                $this->rewriteObj = $rewriteObj;
            } catch (Exception $e) {
                $this->rewriteObj = App::getInstance()->containerCall([Rewrite::class, 'new']);
            }
        }
        return $this->rewriteObj;
    }

    /**
     * returns object translations urls
     *
     * @return array
     * @throws Exception
     */
    public function getTranslations(): array
    {
        return array_map(
            function ($el) {
                $routeInfo = $el->getRouteInfo();
                $modelClass = App::getInstance()->containerCall([$routeInfo->getHandler()[0], 'getObjectClass']);
                $model = App::getInstance()->containerCall([$modelClass, 'load'], $routeInfo->getVars());
                return $model->getRewrite()->getUrl();
            },
            $this->getRewrite()->getTranslations()
        );
    }

    /**
     * return Controller by RouteInfo and App
     * 
     * @param App $app
     * @return BasePage
     */
    public function getControllerUsingRewrite(App $app) : BasePage
    {
        /** @var RouteInfo $routeInfo */
        $routeInfo = $this->getRewrite()->getRouteInfo();
        return $this->getControllerByRouteInfo($routeInfo, $app);
    }

    /**
     * return Controller by RouteInfo and App
     * 
     * @param RouteInfo $routeinfo
     * @param App $app
     * @return BasePage
     */
    protected function getControllerByRouteInfo(RouteInfo $routeInfo, App $app) : BasePage
    {
        $handler = $routeInfo->getHandler();

        $handlerType = reset($handler); $handlerMethod = end($handler);
        $currentPage = $app->containerMake($handlerType);

        $vars = $routeInfo->getVars();

        // inject routeInfo
        $vars['route_info'] = $routeInfo;

        // add route collected data
        $vars['route_data'] = $routeInfo->getVars();
        $vars['route_data']['_noLog'] = true;

        $currentPage->setRouteInfo($routeInfo);

        if ($currentPage instanceof FrontendPageWithObject) {
            $app->containerCall([$currentPage, $handlerMethod], $vars);
        }

        return $currentPage;
    }

    /**
     * gets rewrite prefix
     *
     * @return string
     */
    abstract public function getRewritePrefix(): string;
}
