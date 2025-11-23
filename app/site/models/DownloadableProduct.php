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
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Models\MediaElement;
use Exception;
use App\Base\GraphQl\GraphQLExport;
use App\Base\Traits\ProductTrait;

/**
 * Downloadable Product Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getTitle()
 * @method int getMediaId()
 * @method float getPrice()
 * @method string getContent()
 * @method string getMetaTitle()
 * @method string getMetaDescription()
 * @method string getMetaKeywords()
 * @method string getHtmlTitle()
 * @method int getUserId()
 * @method int getTaxClassId()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUrl(string $url)
 * @method self setLocale(string $locale)
 * @method self setTitle(string $title)
 * @method self setMediaId(int $media_id)
 * @method self setPrice(float $price)
 * @method self setContent(string $content)
 * @method self setMetaTitle(string $meta_title)
 * @method self setMetaDescription(string $meta_description)
 * @method self setMetaKeywords(string $meta_keywords)
 * @method self setHtmlTitle(string $html_title)
 * @method self setUserId(int $user_id)
 * @method self setTaxClassId(int $tax_class_id)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
#[GraphQLExport]
class DownloadableProduct extends FrontendModel implements ProductInterface
{
    use ProductTrait;

    /**
     * @var MediaElement media element
     */
    protected ?MediaElement $media = null;

    /**
     * @var array page gallery
     */
    protected array $gallery = [];

    public function isPhysical(): bool
    {
        // Downloadable products are not physical
        return false;
    }

    /**
     * gets downloadable_product gallery
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
                $this->downloadable_product_media_elementList()->media_element()->fetchAll()
            ));
        }
        return $this->gallery;
    }

    /**
     * adds media to downloadable_product gallery
     *
     * @param MediaElement $media_element
     * @return self
     * @throws BasicException
     */
    public function addMedia(MediaElement $media_element): static
    {
        $new_page_media_row = App::getInstance()->getDb()->table('downloadable_product_media_element')->createRow();
        $new_page_media_row->update(
            [
                'downloadable_product_id' => $this->id,
                'media_element_id' => $media_element->id,
            ]
        );
        return $this;
    }

    /**
     * removes media from downloadable_product gallery
     *
     * @param MediaElement $media_element
     * @return self
     * @throws BasicException
     */
    public function removeMedia(MediaElement $media_element): static
    {
        App::getInstance()->getDb()->table('downloadable_product_media_element')->where(
            [
                'downloadable_product_id' => $this->id,
                'media_element_id' => $media_element->id,
            ]
        )->delete();
        return $this;
    }

    /**
     * gets media element
     *
     * @param bool $reset
     * @return MediaElement|null
     * @throws Exception
     */
    public function getMedia(bool $reset = false): ?MediaElement
    {
        $this->checkLoaded();

        if (empty($this->getMediaId())) {
            return null;
        }

        if (empty($this->media) || $reset == true) {
            try {
                $this->media = MediaElement::load($this->getMediaId()) ?: null;
            } catch (Exception $e) {}
        }

        return $this->media;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'downloadable';
    }

    #[GraphQLExport]
    public function getSku(): string
    {
        return $this->getTitle() ?? "downloadable_product_" . $this->getId();
    }
}
