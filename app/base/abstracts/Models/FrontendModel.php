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
     * gets object rewrite model
     *
     * @return Rewrite
     * @throws Exception
     */
    public function getRewrite()
    {
        $this->checkLoaded();

        if (!($this->rewriteObj instanceof Rewrite)) {
            $this->rewriteObj = $this->getContainer()->make(
                Rewrite::class,
                ['dbrow' => $this->getDb()->table('rewrite')->where('route', '/'.$this->getRewritePrefix().'/'.$this->getId())->fetch()]
            );
        }
        return $this->rewriteObj;
    }

    /**
     * gets frontend url for object
     *
     * @return string
     * @throws Exception
     */
    public function getFrontendUrl()
    {
        $this->checkLoaded();

        return '/'.$this->getLocale().'/'.$this->getUtils()->slugify($this->getUrl()).'.html';
    }

    /**
     * post persist hook
     *
     * @return self
     * @throws Exception
     */
    public function postPersist()
    {
        $rewrite = $this->getRewrite();
        $rewrite->website_id = $this->getWebsiteId();
        $rewrite->url = $this->getFrontendUrl();
        $rewrite->route = '/'.$this->getRewritePrefix().'/'.$this->getId();
        $rewrite->user_id = $this->getUserId();
        $rewrite->locale = $this->getLocale();
        $rewrite->persist();

        return parent::postPersist();
    }

    /**
     * pre remove hook
     *
     * @return self
     * @throws Exception
     */
    public function preRemove()
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
    public function getTranslations()
    {
        return array_map(
            function ($el) {
                $routeInfo = $el->getRouteInfo();
                $modelClass = $this->getContainer()->call([$routeInfo->getHandler()[0], 'getObjectClass']);
                $model = $this->getContainer()->call([$modelClass,'load'], $routeInfo->getVars());
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
    public function getPageTitle()
    {
        return $this->html_title ?: $this->title;
    }

    /**
     * gets rewrite prefix
     *
     * @return string
     */
    abstract public function getRewritePrefix();
}
