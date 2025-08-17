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
use DateTime;
use App\Base\GraphQl\GraphQLExport;

/**
 * Address Model
 *
 * @method int getId()
 * @method int getWebsiteId()
 * @method int getUserId()
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
class Address extends BaseModel
{
    use WithOwnerTrait, WithLatLngTrait;

    public function getFullName(): string
    {
        return trim($this->getFirstName() . ' ' . $this->getLastName());
    }

    #[GraphQLExport]
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

    #[GraphQLExport]
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
}
