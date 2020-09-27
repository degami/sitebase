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
namespace App\Base\Traits;

/**
 * Trait for elements with children
 */
trait WithChildrenTrait
{
    /**
     * @var array children
     */
    protected $children = [];

    /**
     * gets children
     *
     * @param  string|null  $locale
     * @param  boolean $reset
     * @return array
     */
    public function getChildren($locale = null, $reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->children) && !empty($this->children)) || $reset == true) {
            $query = null;
            if ($locale != null) {
                $query = $this->getDb()->table($this->tablename)->where(['parent_id' => $this->id, 'locale' => $locale])->orderBy('position');
            } else {
                $query = $this->getDb()->table($this->tablename)->where(['parent_id' => $this->id])->orderBy('position');
            }

            $this->children = array_map(
                function ($el) {
                    return $this->container->make(static::class, ['dbrow' => $el]);
                },
                $query->fetchAll()
            );

            if (!empty($this->children)) {
                $this->sortChildren();
            }
        }
        return $this->children;
    }

    protected function sortChildren()
    {
        usort($this->children, [$this, 'cmpPosition']);
    }

    protected function cmpPosition($a, $b)
    {
        if ($a->position == $b->position) {
            return 0;
        }
        return ($a->position < $b->position) ? -1 : 1;
    }


    public function preRemove()
    {
        $parent_id = $this->parent_id;
        foreach($this->getChildren() as $child) {
            $child->setParentId($parent_id)->persist();
        }
    }
}
