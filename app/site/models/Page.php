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

use \App\Base\Abstracts\Models\FrontendModel;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;

/**
 * Page Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getTitle()
 * @method string getTemplateName()
 * @method string getContent()
 * @method string getMetaDescription()
 * @method string getMetaKeywords()
 * @method string getHtmlTitle()
 * @method int getUserId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 */
class Page extends FrontendModel
{
    /**
     * @var array page gallyer
     */
    protected $gallery = [];

    /**
     * @var array page terms
     */
    protected $terms = [];

    /**
     * gets page gallery
     *
     * @param false $reset
     * @return array
     * @throws Exception
     */
    public function getGallery($reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->gallery) && !empty($this->gallery)) || $reset == true) {
            $this->gallery = array_map(
                function ($el) {
                    return $this->getContainer()->make(MediaElement::class, ['dbrow' => $el]);
                },
                $this->page_media_elementList()->media_element()->fetchAll()
            );
        }
        return $this->gallery;
    }

    /**
     * adds media to page
     *
     * @param MediaElement $media_element
     * @return self
     * @throws BasicException
     */
    public function addMedia($media_element)
    {
        $new_page_media_row = $this->getDb()->table('page_media_element')->createRow();
        $new_page_media_row->update(
            [
            'page_id' => $this->id,
            'media_element_id' => $media_element->id,
            ]
        );
        return $this;
    }

    /**
     * removes media from page
     *
     * @param MediaElement $media_element
     * @return self
     * @throws BasicException
     */
    public function removeMedia($media_element)
    {
        $this->getDb()->table('page_media_element')->where(
            [
            'page_id' => $this->id,
            'media_element_id' => $media_element->id,
            ]
        )->delete();
        return $this;
    }

    /**
     * get page terms
     *
     * @param false $reset
     * @return array
     * @throws Exception
     */
    public function getTerms($reset = false)
    {
        $this->checkLoaded();

        if (!(is_array($this->terms) && !empty($this->terms)) || $reset == true) {
            $this->terms = array_map(
                function ($el) {
                    return $this->getContainer()->make(Taxonomy::class, ['dbrow' => $el]);
                },
                $this->page_taxonomyList()->taxonomy()->fetchAll()
            );
        }
        return $this->terms;
    }

    /**
     * adds a term to page
     *
     * @param Taxonomy $term
     * @return self
     * @throws BasicException
     */
    public function addTerm($term)
    {
        $new_page_taxonomy_row = $this->getDb()->table('page_taxonomy')->createRow();
        $new_page_taxonomy_row->update(
            [
            'page_id' => $this->id,
            'taxonomy_id' => $term->id,
            ]
        );
        return $this;
    }

    /**
     * removes a term from page
     *
     * @param Taxonomy $term
     * @return self
     * @throws BasicException
     */
    public function removeTerm($term)
    {
        $this->getDb()->table('page_taxonomy')->where(
            [
            'page_id' => $this->id,
            'taxonomy_id' => $term->id,
            ]
        )->delete();
        return $this;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getRewritePrefix()
    {
        return 'page';
    }
}
