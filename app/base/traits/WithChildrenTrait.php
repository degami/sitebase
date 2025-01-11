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

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\ModelWithChildren;

/**
 * Trait for elements with children
 */
trait WithChildrenTrait
{
    /**
     * @var array children
     */
    protected array $children = [];

    /**
     * gets children
     *
     * @param string|null $locale
     * @param bool $reset
     * @return array
     */
    public function getChildren($locale = null, $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->children) && !empty($this->children)) || $reset == true) {
            $query = null;
            if ($locale != null) {
                $query = $this->getDb()->table($this->table_name)->where(['parent_id' => $this->id, 'locale' => $locale])->orderBy('position');
            } else {
                $query = $this->getDb()->table($this->table_name)->where(['parent_id' => $this->id])->orderBy('position');
            }

            $this->children = array_map(
                function ($el) {
                    return $this->container->make(static::class, ['db_row' => $el]);
                },
                $query->fetchAll()
            );

            if (!empty($this->children)) {
                $this->sortChildren();
            }
        }
        return $this->children;
    }

    /**
     * sort children by position
     */
    protected function sortChildren() : void
    {
        usort($this->children, [$this, 'cmpPosition']);
    }

    /**
     * @param ModelWithChildren $a
     * @param ModelWithChildren $b
     * @return int
     */
    protected function cmpPosition(ModelWithChildren $a, ModelWithChildren $b): int
    {
        if ($a->getPosition() == $b->getPosition()) {
            return 0;
        }
        return ($a->getPosition() < $b->getPosition()) ? -1 : 1;
    }

    /**
     * pre remove hook
     *
     * @return BaseModel
     */
    public function preRemove(): BaseModel
    {
        $parent_id = $this->parent_id;
        foreach ($this->getChildren() as $child) {
            $child->setParentId($parent_id)->persist();
        }
        return $this;
    }
}
