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

namespace App\Base\Abstracts\Models;

use App\Base\Traits\FrontendModelTrait;
use App\Base\Traits\IndexableTrait;
use App\Base\Traits\WithWebsiteTrait;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithRewriteTrait;
use App\App;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Models\Block;
use Exception;
use Throwable;
use App\Base\GraphQl\GraphQLExport;

/**
 * A model that will be shown on frontend
 */
abstract class FrontendModel extends BaseModel
{
    use WithOwnerTrait;
    use WithWebsiteTrait;
    use WithRewriteTrait;
    use IndexableTrait;
    use FrontendModelTrait;

    /**
     * return page title
     *
     * @return string
     */
    public function getPageTitle(): string
    {
        return $this->html_title ?: $this->title;
    }

    public static function isExportable() : bool
    {
        return true;
    }

    public static function isIndexable() : bool
    {
        return true;
    }

    public static function getExportHeader() : array
    {
        $class = new \ReflectionClass(static::class);
        $dockBlockr = $class->getDocComment();
        if (preg_match_all("/@method\s+(.*?\s+)get(.*?)\(\)/", $dockBlockr, $matches, PREG_SET_ORDER)) {
            $out = [];
            foreach($matches as $match) {
                $out[] = [
                    'data_type' => trim($match[1]),
                    'column_name' => App::getInstance()->getUtils()->pascalCaseToSnakeCase($match[2]),
                    'getter' => 'get'.$match[2],
                ];
            }
            return $out;
        }

        return [];
    }

    public Function getExportRowData() : array
    {
        $out = [];

        foreach(static::getExportHeader() as $headerInfo) {
            $out[$headerInfo['column_name']] = $this->{$headerInfo['getter']}();
        }

        return $out;
    }

    public function getTitle() : string
    {
        return $this->getData('title');
    }

    public function getContent() : string
    {
        $content = $this->getData('content');

        if (preg_match("/\[(Block: (\d+))\]/", $content) && ($currentPage = App::getInstance()?->getAppRouteInfo()?->getControllerObject()) instanceof BasePage) {
            //$currentPage = $this->getControllerUsingRewrite(App::getInstance());

            $content = preg_replace_callback("/\[(Block: (\d+))\]/", function($matches) use ($currentPage) {
                $blockId = $matches[2];
                try {
                    $block = Block::load($blockId);        
                    return $block->renderHTML($currentPage);    
                } catch (Throwable $e) {}
    
                return "";
            }, $content);    
        }

        return $content;
    }

    public Function canSaveVersions() : bool
    {
        return true;
    }

    /**
     * gets full frontend url for object
     *
     * @return string
     * @throws Exception
     */
    #[GraphQLExport]
    public function getAbsoluteFrontendUrl(): string
    {
        $this->checkLoaded();

        if (($this->getUrl() ?? $this->getUrlKey()) === null) {
            return "";
        }

        return $this->getWebsite()->getUrl() . $this->getFrontendUrl();
    }
}
