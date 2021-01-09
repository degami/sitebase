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

use App\Base\Abstracts\Models\FrontendModel;
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
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrl(string $url)
 * @method self setLocale(string $locale)
 * @method self setTitle(string $title)
 * @method self setTemplateName(string $template_name)
 * @method self setContent(string $content)
 * @method self setMetaDescription(string $meta_description)
 * @method self setMetaKeywords(string $meta_keywords)
 * @method self setHtmlTitle(string $html_title)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class Page extends FrontendModel
{
    /**
     * @var array page gallery
     */
    protected $gallery = [];

    /**
     * @var array page terms
     */
    protected $terms = [];

    /**
     * gets page gallery
     *
     * @param bool $reset
     * @return array
     * @throws Exception
     */
    public function getGallery(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->gallery) && !empty($this->gallery)) || $reset == true) {
            $this->gallery = array_map(
                function ($el) {
                    return $this->getContainer()->make(MediaElement::class, ['db_row' => $el]);
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
    public function addMedia(MediaElement $media_element): Page
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
    public function removeMedia(MediaElement $media_element): Page
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
     * @param bool $reset
     * @return array
     * @throws Exception
     */
    public function getTerms(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->terms) && !empty($this->terms)) || $reset == true) {
            $this->terms = array_map(
                function ($el) {
                    return $this->getContainer()->make(Taxonomy::class, ['db_row' => $el]);
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
    public function addTerm(Taxonomy $term): Page
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
    public function removeTerm(Taxonomy $term): Page
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
    public function getRewritePrefix(): string
    {
        return 'page';
    }
}
