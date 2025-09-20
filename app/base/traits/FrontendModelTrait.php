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
use App\Base\Models\Rewrite;
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

        // check if a rewrite with the same url already exists, if so, append a counter to the url until a free one is found. change the frontend_url too
        $counter = 0;
        $original_rewrite_url = $rewrite_url = $this->getFrontendUrl();
        do {
            $collection = Rewrite::getCollection()
                ->where(['url' => $rewrite_url, 'id:not' => $rewrite->getId()]);

            if ($collection->count() == 0) {
                break;
            }

            $counter++;
            $rewrite_url = preg_replace('/(.*)(\-[0-9]+)?(\.html)/', '$1-' . $counter . '$3', $original_rewrite_url);
        } while ($collection->count() > 0) ;

        if ($counter > 0) {
            // cannot user persist here to vaoid loops. will do a direct db update

            $columnName = null;
            $tableInfo = App::getInstance()->getSchema()->getTable(static::defaultTableName());
            if ($tableInfo->getColumn('url')) {
                $columnName = 'url';
            } else if ($tableInfo->getColumn('url_key')) {
                $columnName = 'url_key';
            }

            if ($columnName) {
                $newValue = preg_replace('/\.html$/', '', preg_replace('/^\/'.$this->getLocale().'\//' , '', $rewrite_url));
                App::getInstance()->getDb()->update(
                    $this->getTableName(),
                    [$columnName => $newValue],
                    ['id = ' . $this->getId()]
                );
            }
        }

        $rewrite->setUrl($rewrite_url);
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