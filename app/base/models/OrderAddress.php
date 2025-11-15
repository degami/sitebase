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

namespace App\Base\Models;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Traits\WithLatLngTrait;
use App\Base\Traits\WithOwnerTrait;
use App\Base\Traits\WithWebsiteTrait;
use DateTime;

/**
 * Order Address Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getUserId()
 * @method int getOrderId()
 * @method string getType()
 * @method string getFirstName()
 * @method string getLastName()
 * @method string getCompany()
 * @method string getAddress1()
 * @method string getAddress2()
 * @method string getCity()
 * @method string getState()
 * @method string getPostcode()
 * @method string getCountryCode()
 * @method string getPhone()
 * @method string getEmail()
 * @method DateTime getCreatedAt()
 * @method DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setWebsiteId(int $website_id)
 * @method self setUserId(int $user_id)
 * @method self setOrderId(int $order_id)
 * @method self setType(string $type)
 * @method self setFirstName(string $first_name)
 * @method self setLastName(string $last_name)
 * @method self setCompany(string $company)
 * @method self setAddress1(string $address1)
 * @method self setAddress2(string $address2)
 * @method self setCity(string $city)
 * @method self setState(string $state)
 * @method self setPostcode(string $postcode)
 * @method self setCountryCode(string $country_code)
 * @method self setPhone(string $phone)
 * @method self setEmail(string $email)
 * @method self setCreatedAt(DateTime $created_at)
 * @method self setUpdatedAt(DateTime $updated_at)
 */
class OrderAddress extends BaseModel
{
    use WithLatLngTrait, WithOwnerTrait, WithWebsiteTrait;

    /**
     * {@inheritdoc}
     */
    public static function canBeDuplicated() : bool
    {
        return false;
    }

    /**
     * Set the order for this address
     * 
     * @param Order $order
     * @return self
     */
    public function setOrder(Order $order): self
    {
        return $this
            ->setWebsiteId($order->getWebsiteId())
            ->setUserId($order->getUserId())
            ->setOrderId($order->getId());
    }

    /**
     * Create an order address from a generic address
     *
     * @param Address $address
     * @return self
     */
    public static function createFromAddress(Address $address): self
    {
        $orderAddress = new self();
        return $orderAddress
            ->setFirstName($address->getFirstName())
            ->setLastName($address->getLastName())
            ->setCompany($address->getCompany())
            ->setAddress1($address->getAddress1())
            ->setAddress2($address->getAddress2())
            ->setCity($address->getCity())
            ->setState($address->getState())
            ->setPostcode($address->getPostcode())
            ->setCountryCode($address->getCountryCode())
            ->setPhone($address->getPhone())
            ->setEmail($address->getEmail())
            ->setLatitude($address->getLatitude())
            ->setLongitude($address->getLongitude());
    }

    /**
     * Get the full name of the person associated with this address
     *
     * @return string
     */
    public function getFullName(): string
    {
        return trim($this->getFirstName() . ' ' . $this->getLastName());
    }

    /**
     * Get the full address as a formatted string
     *
     * @return string
     */
    public function getFullAddress(): string
    {
        $addressParts = [
            $this->getAddress1(),
            $this->getAddress2(),
            $this->getCity(),
            $this->getState(),
            $this->getPostcode(),
            $this->getCountryCode()
        ];
        return implode(', ', array_filter($addressParts));
    }

    /**
     * Get the full contact information as a string
     *
     * @return string
     */
    public function getFullContact(): string
    {
        $contactParts = [
            $this->getFullName(),
            $this->getCompany(),
            $this->getEmail(),
            $this->getPhone()
        ];
        return implode(' | ', array_filter($contactParts));
    }

    /**
     * Get the type of this address as a human-readable label
     *
     * @return string
     */
    public function getTypeLabel(): string
    {
        return match ($this->getType()) {
            'billing' => 'Billing Address',
            'shipping' => 'Shipping Address',
            default => 'Unknown Address Type'
        };
    }

    /**
     * Get the icon class for this address type
     *
     * @return string
     */
    public function getTypeIcon(): string
    {
        return match ($this->getType()) {
            'billing' => 'fa fa-credit-card',
            'shipping' => 'fa fa-truck',
            default => 'fa fa-question'
        };
    }
}
