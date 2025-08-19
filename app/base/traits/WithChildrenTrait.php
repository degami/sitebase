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
use App\Base\Abstracts\Models\ModelWithChildren;
use Exception;
use Throwable;
use App\Base\GraphQL\GraphQLExport;

/**
 * Trait for elements with children
 */
trait WithChildrenTrait
{
    /**
     * @var array children
     */
    protected array $children = [];

    public function getChildrenClass(): string
    {    
        return $this->children_class ?? static::class;
    }

    public function getChildrenTableName(): string
    {
        if ($this->getChildrenClass() == static::class) {
            return $this->table_name;
        }

        return App::getInstance()->containerCall([$this->getChildrenClass(), 'defaultTableName']);
    }

    /**
     * gets children
     *
     * @param string|null $locale
     * @param bool $reset
     * @return array
     */
    public function getChildren(?string $locale = null, bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->children) && !empty($this->children)) || $reset == true) {
            $query = null;

            $tableHasPosition = false;
            try {
                $positionColumn = App::getInstance()->getSchema()->getTable($this->getChildrenTableName())->getColumn('position');
                if ($positionColumn) {
                    $tableHasPosition = true;
                }
            } catch (Exception $e) {}


            if ($locale != null) {
                $query = App::getInstance()->getDb()->table($this->getChildrenTableName())->where(['parent_id' => $this->id, 'locale' => $locale]);
                if ($tableHasPosition) {
                    $query = $query->orderBy('position');
                }
            } else {
                $query = App::getInstance()->getDb()->table($this->getChildrenTableName())->where(['parent_id' => $this->id]);
                if ($tableHasPosition) {
                    $query = $query->orderBy('position');
                }
            }

            $this->children = array_map(
                function ($el) {
                    return App::getInstance()->containerMake($this->getChildrenClass(), ['db_row' => $el]);
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
        // can be used also if table has not position column, as null == null
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

    /**
     * Gets all descendant objects recursively
     *
     * @return static[]
     */
    public function getDescendants(): array
    {
        $descendants = [];

        try {
            ///** @var BaseCollection $children */
            //$children = $this->containerCall([static::class, 'getCollection']);
            //$children = $children->where(['parent_id' => $this->id]);

            foreach ($this->getChildren() as $child) {
                $descendants[] = $child;
                $descendants = array_merge($descendants, $child->getDescendants());
            }
        } catch (Throwable $t) {}

        return $descendants;
    }
}
