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

namespace App\Site\Models;

use \App\Base\Abstracts\Models\FrontendModelWithChildren;
use DateTime;
use Exception;

/**
 * Taxonomy Model
 *
 * @method int getId()
 * @method string getTitle()
 * @method string getLocale()
 * @method string getContent()
 * @method string getMetaDescription()
 * @method string getMetaKeywords()
 * @method string getHtmlTitle()
 * @method int getUserId()
 * @method int getParent()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class Taxonomy extends FrontendModelWithChildren
{
    /**
     * @var array taxonomy pages
     */
    protected $pages = [];

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getRewritePrefix()
    {
        return 'taxonomy';
    }

    /**
     * gets term pages
     *
     * @param false $reset
     * @return array
     * @throws Exception
     */
    public function getPages($reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->pages) && !empty($this->pages)) || $reset == true) {
            $this->pages = array_map(
                function ($el) {
                    return $this->getContainer()->make(Page::class, ['dbrow' => $el]);
                },
                $this->page_taxonomyList()->page()->fetchAll()
            );
        }
        return $this->pages;
    }
}
