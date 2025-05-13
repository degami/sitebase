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

/**
 * Trait for elements with parent
 */
trait WithParentTrait
{
    /**
     * gets parent object if any
     *
     * @return self|null
     */
    public function getParentObj(): ?static
    {
        if ($this->parent_id == null) {
            return null;
        }

        return App::getInstance()->containerCall([static::class, 'load'], ['id' => $this->parent_id]);
    }

    /**
     * gets parent ids tree
     *
     * @return string|null
     */
    public function getParentIds(): ?string
    {
        if ($this->parent_id == null) {
            return $this->id;
        }

        return $this->getParentObj()->getParentIds() . '/' . $this->id;
    }
}
