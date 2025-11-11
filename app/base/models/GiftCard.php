<?php

namespace App\Base\Models;

use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Traits\ProductTrait;
use App\Base\GraphQl\GraphQLExport;
use App\Site\Models\MediaElement;

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
 * @method float getCredit()
 * @method int getMediaId()
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
 * @method self setCredit(float $credit)
 * @method self setMediaId(int $media_id)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
#[GraphQLExport]
class GiftCard extends FrontendModel implements ProductInterface
{
    use ProductTrait;

    protected ?MediaElement $media = null;

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function isPhysical(): bool
    {
        return false;
    }

    #[GraphQLExport]
    public function getMedia(): ?MediaElement
    {
        if (!is_null($this->media)) {
            return $this->media;
        }

        if (is_null($this->getMediaId())) {
            return null;
        }

        return $this->setMedia(MediaElement::load($this->getMediaId()))->media;
    }

    public function setMedia(?MediaElement $media): self
    {
        $this->media = $media;
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
        return $this->getData('sku')?? 'gift_card_' . $this->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return 'giftcard';
    }
}
