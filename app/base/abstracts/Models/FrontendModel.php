<?php

/**
 * SiteBase
 * PHP Version 8.0
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
use League\Plates\Template\Func;

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
}
