<?php

namespace App\Site\Models;

use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Interfaces\Model\ProductInterface;

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
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
class Book extends FrontendModel implements ProductInterface
{
    public function isPhysical(): bool
    {
        return true;
    }

    public function getId(): int
    {
        return $this->getData('id');
    }

    public function getPrice(): float
    {
        return $this->getData('price') ?? 0.0;
    }

    public function getTaxClassId(): ?int
    {
        return $this->getData('tax_class_id');
    }

    public function getName() : ?string
    {
        return $this->getData('title');
    }

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
