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
use App\Base\Models\Country;
use App\Base\Models\Address;
use App\Base\Interfaces\Commerce\ShippingMethodInterface;
use HaydenPierce\ClassFinder\ClassFinder;

class Shipping extends FormPageWithLang
{
    use CommercePageTrait;

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::installDone() && App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false);
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
        return 'commerce/shipping';
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
        return $this->getUtils()->translate('Shippning Address', locale: $this->getCurrentLocale());
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

        $this->addNewAddressFields($form, $form_state);

        $this->addShippingMethodsAccordion($form, $form_state);

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

    protected function addNewAddressFields(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $countriesItems = Country::getCollection()->getItems();
        $countries = array_combine(
            array_map(fn($el) => $el->getIso2(), $countriesItems),
            array_map(fn($el) => $el->getNameEn(), $countriesItems),
        );

        $form
            ->addMarkup('<div class="row mt-3">')
            ->addField('first_name', [
                'type' => 'textfield',
                'title' => 'First Name',
                'title_suffix' => '<span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('last_name', [
                'type' => 'textfield',
                'title' => 'Last Name',
                'title_suffix' => '<span class="required">*</span>',
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
                'title' => 'Address 1',
                'title_suffix' => '<span class="required">*</span>',
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
                'title' => 'City',
                'title_suffix' => '<span class="required">*</span>',
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
                'title' => 'Post Code',
                'title_suffix' => '<span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('country_code', [
                'type' => 'select',
                'title' => 'Country',
                'title_suffix' => '<span class="required">*</span>',
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
                'title' => 'Email',
                'title_suffix' => '<span class="required">*</span>',
                'container_class' => 'col-sm-6',
                'default_value' => '',
            ])
            ->addMarkup('</div>');

        return $form;
    }

    protected function addShippingMethodsAccordion(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $form->addMarkup('<h4 class="mt-3">'.$this->getUtils()->translate('Choose your shipping method').'</h4>');

        /** @var FAPI\Containers\Accordion $accordion */
        $accordion = $form->addField('shipping_methods', [
            'type' => 'accordion',
            'container_class' => 'mt-2',
        ]);

        foreach ($this->getShippingMethods() as $key => $shippingMethod) {
            /** @var ShippingMethodInterface $shippingMethod */
            if (!$shippingMethod->isActive($this->getCart())) {
                continue;
            }

            if (!$shippingMethod->isApplicable($this->getCart())) {
                continue;
            }

            $accordion
                ->addAccordion($shippingMethod->getName())
                ->addField('shipping_'.$key.'_code', [
                    'type' => 'hidden',
                    'default_value' => $this->getShippingMethodCode($shippingMethod),
                ])
                ->addField($this->getShippingMethodCode($shippingMethod), $shippingMethod->getShippingFormFieldset($this->getCart(), $form, $form_state));
        }

        $accordion->addJs('
            $("#shipping_methods").on("accordionactivate", function(event, ui) {
                var activeIndex = $(this).accordion("option", "active");
                $("#selected_shipping_method").val($("#shipping_"+activeIndex+"_code").val());
            });
        ');

        $form->addField('selected_shipping_method', [
            'type' => 'hidden',
            'default_value' => $this->getShippingMethods() ? $this->getShippingMethodCode($this->getShippingMethods()[$accordion->getActive()]) : '',
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

        $selected_shipping_method = $values['selected_shipping_method'];
        $shippingValues = $values['payment_methods'][$selected_shipping_method];

        /** @var ShippingMethodInterface $shippingMethod */
        $shippingMethod = current(array_filter($this->getShippingMethods(), function($shippingMethod) use ($selected_shipping_method) {
            return $this->getShippingMethodCode($shippingMethod) == $selected_shipping_method;
        }));

        $shippingResult = $shippingMethod?->calculateShipping($shippingValues, $this->getCart());

        $this->getCart()
            ->setShippingAddressId($address->getId())
            ->setShippingMethod($shippingMethod?->getCode())
            ->setShippingAmount($shippingResult['shipping_cost'] ?? 0)
            ->persist();

        if ($this->hasLang()) {
            return $this->doRedirect($this->getUrl('frontend.commerce.checkout.payment.withlang', ['lang' => $this->getCurrentLocale()]));
        }

        return $this->doRedirect($this->getUrl('frontend.commerce.checkout.payment'));
    }

    protected function getShippingMethods() : array
    {
        return array_values(array_map(function($shippingClassName) {
            return $this->containerMake($shippingClassName);
        }, array_filter(array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_COMMERCE_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::COMMERCE_NAMESPACE, ClassFinder::RECURSIVE_MODE)
        ), function($className) {
            if (!is_subclass_of($className, ShippingMethodInterface::class)) {
                return false;
            }

            $method = $this->containerMake($className);
            return App::getInstance()->getSiteData()->getConfigValue('shipping/'.$method->getCode().'/active') == 1;
        })));
    }

    protected function getShippingMethodCode(ShippingMethodInterface $shipping_method) : string
    {
        return $shipping_method->getCode() ?: strtolower(str_replace("\\",'_', trim(str_replace([App::BASE_COMMERCE_NAMESPACE, App::COMMERCE_NAMESPACE], '', get_class($shipping_method)), "\\")));
    }
}