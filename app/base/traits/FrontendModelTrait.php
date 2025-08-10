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

use App\App;
use App\Base\Abstracts\Models\BaseModel;
use Exception;

/**
 * Trait for frontend models
 */
trait FrontendModelTrait
{
    /**
     * gets frontend url for object
     *
     * @return string
     * @throws Exception
     */
    public function getFrontendUrl(): string
    {
        $this->checkLoaded();

        return '/' . $this->getLocale() . '/' . App::getInstance()->getUtils()->slugify($this->getUrl() ?? $this->getUrlKey(), false) . '.html';
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
        try {
            $this->getRewrite()->remove();
        } catch (Exception $e) {
        }

        return parent::preRemove();
    }
}