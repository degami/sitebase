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

namespace App\Base\Abstracts\Models;

use \App\Site\Models\Rewrite;
use \Exception;
use \App\Base\Traits\WithWebsiteTrait;
use \App\Base\Traits\WithOwnerTrait;

/**
 * A model that will be shown on frontend
 */
abstract class FrontendModel extends BaseModel
{
    use WithWebsiteTrait, WithOwnerTrait;

    /**
     * @var Rewrite|null rewrite object
     */
    protected $rewriteObj = null;

    /**
     * Field names to be exposed to indexer
     *
     * @return string[]
     */
    public static function exposeToIndexer(): array
    {
        return ['title', 'content'];
    }

    /**
     * gets object rewrite model
     *
     * @return Rewrite
     * @throws Exception
     */
    public function getRewrite(): ?Rewrite
    {
        $this->checkLoaded();

        if (!($this->rewriteObj instanceof Rewrite)) {
            try {
                $this->rewriteObj = $this->getContainer()->call([Rewrite::class, 'loadBy'], ['field' => 'route', 'value' => '/' . $this->getRewritePrefix() . '/' . $this->getId()]);
            } catch (Exception $e) {}
        }
        return $this->rewriteObj;
    }

    /**
     * gets frontend url for object
     *
     * @return string
     * @throws Exception
     */
    public function getFrontendUrl(): string
    {
        $this->checkLoaded();

        return '/' . $this->getLocale() . '/' . $this->getUtils()->slugify($this->getUrl(), false) . '.html';
    }

    /**
     * post persist hook
     *
     * @return self
     * @throws Exception
     */
    public function postPersist(): BaseModel
    {
        $rewrite = $this->getRewrite();
        $rewrite->setWebsiteId($this->getWebsiteId());
        $rewrite->setUrl($this->getFrontendUrl());
        $rewrite->setRoute('/' . $this->getRewritePrefix() . '/' . $this->getId());
        $rewrite->setUserId($this->getUserId());
        $rewrite->setLocale($this->getLocale());
        $rewrite->persist();

        return parent::postPersist();
    }

    /**
     * pre remove hook
     *
     * @return self
     * @throws Exception
     */
    public function preRemove(): BaseModel
    {
        $this->getRewrite()->remove();

        return parent::preRemove();
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
                $modelClass = $this->getContainer()->call([$routeInfo->getHandler()[0], 'getObjectClass']);
                $model = $this->getContainer()->call([$modelClass, 'load'], $routeInfo->getVars());
                return $model->getRewrite()->getUrl();
            },
            $this->getRewrite()->getTranslations()
        );
    }

    /**
     * return page title
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->html_title ?: $this->title;
    }

    /**
     * gets rewrite prefix
     *
     * @return string
     */
    abstract public function getRewritePrefix(): string;
}
