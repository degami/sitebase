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
}
