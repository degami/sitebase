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
use App\Base\Interfaces\Model\PhysicalProductInterface;
use App\Base\Traits\PhysicalProductTrait;
use App\Base\Traits\ProductTrait;
use App\Base\GraphQl\GraphQLExport;

/**
 * Book Model
 * 
 * @method string getSku()
 * @method string getTitle()
 * @method string getContent()
 * @method int getTaxClassId()
 * @method int getWebsiteId()
 * @method int getUserId()
 * @method float getPrice()
 * @method string getUrl()
 * @method string getLocale()
 * @method string getMetaKeywords()
 * @method string getMetaDescription()
 * @method string getHtmlTitle()
 * @method float getWeight()
 * @method float getLength()
 * @method float getWidth()
 * @method float getHeight()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setSku(string $sku)
 * @method self setTitle(string $title)
 * @method self setContent(string $content)
 * @method self setTaxClassId(int $tax_class_id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUserId(int $user_id)
 * @method self setPrice(float $price)
 * @method self setUrl(string $url)
 * @method self setLocale(string $locale)
 * @method self setMetaKeywords(string $meta_keywords)
 * @method self setMetaDescription(string $meta_description)
 * @method self setHtmlTitle(string $html_title)
 * @method self setWeight(float $weight)
 * @method self setLength(float $length)
 * @method self setWidth(float $width)
 * @method self setHeight(float $height)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
#[GraphQLExport]
class Book extends FrontendModel implements PhysicalProductInterface
{
    use ProductTrait;
    use PhysicalProductTrait;

    /**
     * @var array page gallery
     */
    protected array $gallery = [];

    public function isPhysical(): bool
    {
        return true;
    }


    /**
     * gets book gallery
     *
     * @param bool $reset
     * @return \App\Site\Models\MediaElement[]
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
                $this->book_media_elementList()->media_element()->fetchAll()
            ));
        }
        return $this->gallery;
    }

    /**
     * adds media to book gallery
     *
     * @param MediaElement $media_element
     * @return self
     * @throws BasicException
     */
    public function addMedia(MediaElement $media_element): static
    {
        $new_page_media_row = App::getInstance()->getDb()->table('book_media_element')->createRow();
        $new_page_media_row->update(
            [
                'book_id' => $this->id,
                'media_element_id' => $media_element->id,
            ]
        );
        return $this;
    }

    /**
     * removes media from book gallery
     *
     * @param MediaElement $media_element
     * @return self
     * @throws BasicException
     */
    public function removeMedia(MediaElement $media_element): static
    {
        App::getInstance()->getDb()->table('book_media_element')->where(
            [
                'book_id' => $this->id,
                'media_element_id' => $media_element->id,
            ]
        )->delete();
        return $this;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    #[GraphQLExport]
    public function getSku(): string
    {
        return $this->getData('sku')?? 'book_' . $this->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'book';
    }
}
