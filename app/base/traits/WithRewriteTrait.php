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

use App\Site\Models\Rewrite;
use Exception;

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
    public function getRewrite(): ?Rewrite
    {
        $this->checkLoaded();

        if (!($this->rewriteObj instanceof Rewrite)) {
            try {
                $this->rewriteObj = $this->containerCall([Rewrite::class, 'loadBy'], ['field' => 'route', 'value' => '/' . $this->getRewritePrefix() . '/' . $this->getId()]);
            } catch (Exception $e) {
                $this->rewriteObj = $this->containerCall([Rewrite::class, 'new']);
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
                $modelClass = $this->containerCall([$routeInfo->getHandler()[0], 'getObjectClass']);
                $model = $this->containerCall([$modelClass, 'load'], $routeInfo->getVars());
                return $model->getRewrite()->getUrl();
            },
            $this->getRewrite()->getTranslations()
        );
    }

    /**
     * gets rewrite prefix
     *
     * @return string
     */
    abstract public function getRewritePrefix(): string;
}
