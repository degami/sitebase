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

namespace App\Base\Controllers\Frontend\Commerce\Checkout;

use App\App;
use App\Base\Abstracts\Controllers\FormPageWithLang;
use App\Base\Traits\CommercePageTrait;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Address;

class Billing extends FormPageWithLang
{
    use CommercePageTrait;

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && App::getInstance()->getEnv('ENABLE_COMMERCE', false);
    }

    /**
     * specifies if this controller is eligible for full page cache
     *
     * @return bool
     */
    public function canBeFPC(): bool
    {
        return false;
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'commerce/billing';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('Billing Address', locale: $this->getCurrentLocale());
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        return $this->template_data + [
            'cart' => $this->getCart(),
            'user' => $this->getCurrentUser(),
            'locale' => $this->getCurrentLocale(),
        ];
    }

    /**
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $countries = $this->getUtils()->getCountriesSelectOptions();

        $addressesItems = $this->getAddresses();
        $addresses = array_combine(
            array_map(fn($el) => $el->getId(), $addressesItems),
            array_map(fn($el) => $el->getFullAddress(), $addressesItems),
        );

        $form->addMarkup('<div class="row mt-3">'.$this->getUtils()->translate('Choose an existing Address').'</div>');

        $form->addField('copy_address', [
            'type' => 'select',
            'title' => '',
            'options' => ['' => '-- Select --'] + $addresses,
            'default_value' => $this->getCart()->getBillingAddressId(),
        ]);

        $form->addMarkup('<div class="row mt-3">'.$this->getUtils()->translate('or create a new one').'</div>');

        $form
            ->addMarkup('<div class="row mt-3">')
            ->addField('first_name', [
                'type' => 'textfield',
                'title' => 'First Name <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('last_name', [
                'type' => 'textfield',
                'title' => 'Last Name <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('company', [
                'type' => 'textfield',
                'title' => 'Company',
                'container_class' => 'col-sm-12 pb-2',
                'default_value' => '',
            ])
            ->addField('address1', [
                'type' => 'textfield',
                'title' => 'Address 1 <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('address2', [
                'type' => 'textfield',
                'title' => 'Address 2',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('city', [
                'type' => 'textfield',
                'title' => 'City <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('state', [
                'type' => 'textfield',
                'title' => 'State',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('postcode', [
                'type' => 'textfield',
                'title' => 'Post Code <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('country_code', [
                'type' => 'select',
                'title' => 'Country <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'options' => ['' => '-- Select --'] + $countries,
                'default_value' => '',
            ])
            ->addField('phone', [
                'type' => 'textfield',
                'title' => 'Phone',
                'container_class' => 'col-sm-6',
                'default_value' => '',
            ])
            ->addField('email', [
                'type' => 'textfield',
                'title' => 'Email <span class="required">*</span>',
                'container_class' => 'col-sm-6',
                'default_value' => '',
            ])
            ->addMarkup('</div>');

        $form->addField('submit', [
            'type' => 'submit',
            'value' => $this->getUtils()->translate('Continue'),
            'attributes' => [
                'class' => 'btn btn-primary',
            ],
            'container_class' => 'col-12 mt-3',
        ]);

        return $form;
    }

    /**
     * validates form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, array &$form_state): bool|string
    {
        $values = $form->getValues();

        if (empty($values->copy_address)) {
            $requiredFields = ['first_name', 'last_name', 'address1', 'city', 'postcode', 'country_code', 'email'];
            foreach ($requiredFields as $field) {
                if (empty($values->{$field})) {
                    return $this->getUtils()->translate('Please fill in all required fields.');
                }
            }

            if (!filter_var($values->email, FILTER_VALIDATE_EMAIL)) {
                return $this->getUtils()->translate('Please provide a valid email address.');
            }
        }

        return true;
    }

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, array &$form_state): mixed
    {
        $values = $form->getValues();

        if (!empty($values->copy_address)) {
            $address = Address::load($values->copy_address);
        } else {
            $address = new Address();
            $address
                ->setUserId($this->getCurrentUser()->getId())
                ->setWebsiteId($this->getCurrentWebsiteId())
                ->setFirstName($values->first_name)
                ->setLastName($values->last_name)
                ->setCompany($values->company)
                ->setAddress1($values->address1)
                ->setAddress2($values->address2)
                ->setCity($values->city)
                ->setState($values->state)
                ->setPostcode($values->postcode)
                ->setCountryCode($values->country_code)
                ->setPhone($values->phone)
                ->setEmail($values->email)
                ->persist();
        }

        $this->getCart()
            ->setBillingAddressId($address->getId())
            ->persist();

        $redirectUrl = $this->getUrl('frontend.commerce.checkout.shipping');
        if ($this->hasLang()) {
            $redirectUrl = $this->getUrl('frontend.commerce.checkout.shipping.withlang', ['lang' => $this->getCurrentLocale()]);
        }
        if (!$this->getCart()->requireShipping()) {
            // If the cart does not require shipping, redirect to payment
            $redirectUrl = $this->getUrl('frontend.commerce.checkout.payment');
            if ($this->hasLang()) {
                $redirectUrl = $this->getUrl('frontend.commerce.checkout.payment.withlang', ['lang' => $this->getCurrentLocale()]);
            }
        }

        return $this->doRedirect($redirectUrl);
    }
}