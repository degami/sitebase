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

namespace App\Site\Models;

use App\App;
use App\Base\Abstracts\Models\FrontendModel;
use DateTime;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use App\Base\GraphQl\GraphQLExport;
use App\Base\Models\MediaElement;

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
 * @method string getMetaTitle()
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
 * @method self setMetaTitle(string $meta_title)
 * @method self setMetaDescription(string $meta_description)
 * @method self setMetaKeywords(string $meta_keywords)
 * @method self setHtmlTitle(string $html_title)
 * @method self setUserId(int $user_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
#[GraphQLExport]
class Page extends FrontendModel
{
    /**
     * @var array page gallery
     */
    protected array $gallery = [];

        /**
     * @var array page media elements
     */
    protected array $medias = [];

    /**
     * @var array page terms
     */
    protected array $terms = [];

    /**
     * gets page gallery
     *
     * @param bool $reset
     * @return \App\Base\Models\MediaElement[]
     * @throws Exception
     */
    #[GraphQLExport]
    public function getGallery(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->gallery) && !empty($this->gallery)) || $reset == true) {
            $this->gallery = array_filter(array_map(
                function ($el) {
                    /** @var MediaElement $mediaElement */
                    $mediaElement = App::getInstance()->containerMake(MediaElement::class, ['db_row' => $el]);

                    return $mediaElement->isImage() ? $mediaElement : null;
                },
                $this->page_media_elementList()->media_element()->fetchAll()
            ));
        }
        return $this->gallery;
    }

    /**
     * gets page gallery
     *
     * @param bool $reset
     * @return \App\Base\Models\MediaElement[]
     * @throws Exception
     */
    #[GraphQLExport]
    public function getMedias(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->medias) && !empty($this->medias)) || $reset == true) {
            $this->medias = array_filter(array_map(
                function ($el) {
                    return App::getInstance()->containerMake(MediaElement::class, ['db_row' => $el]);
                },
                $this->page_media_elementList()->media_element()->fetchAll()
            ));
        }
        return $this->medias;
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
        $new_page_media_row = App::getInstance()->getDb()->table('page_media_element')->createRow();
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
        App::getInstance()->getDb()->table('page_media_element')->where(
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
     * @return \App\Site\Models\Taxonomy[]
     * @throws Exception
     */
    #[GraphQLExport]
    public function getTerms(bool $reset = false): array
    {
        $this->checkLoaded();

        if (!(is_array($this->terms) && !empty($this->terms)) || $reset == true) {
            $this->terms = array_map(
                function ($el) {
                    return App::getInstance()->containerMake(Taxonomy::class, ['db_row' => $el]);
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
        $new_page_taxonomy_row = App::getInstance()->getDb()->table('page_taxonomy')->createRow();
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
        App::getInstance()->getDb()->table('page_taxonomy')->where(
            [
                'page_id' => $this->id,
                'taxonomy_id' => $term->id,
            ]
        )->delete();
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'page';
    }
}
