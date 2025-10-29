<?php

namespace App\Site\Models;

use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Interfaces\Model\PhysicalProductInterface;
use App\Base\Traits\PhysicalProductTrait;
use App\Base\Traits\ProductTrait;
use App\Base\GraphQl\GraphQLExport;

/**
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

    public function isPhysical(): bool
    {
        return true;
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
