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

use \App\Base\Abstracts\ContainerAwareObject;

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
     * @param  string  $locale
     * @param  boolean $reset
     * @return array
     */
    public function getChildren($locale = null, $reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->children) && !empty($this->children)) || $reset == true) {
            $query = null;
            if ($locale != null) {
                $query = $this->getDb()->table($this->tablename)->where(['parent_id' => $this->id, 'locale' => $locale]);
            } else {
                $query = $this->getDb()->table($this->tablename)->where(['parent_id' => $this->id]);
            }

            $this->children = array_map(
                function ($el) {
                    return $this->container->make(static::class, ['dbrow' => $el]);
                },
                $query->fetchAll()
            );
        }
        return $this->children;
    }
}
