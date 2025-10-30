<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Commerce\ShippingMethods;

use App\App;
use Degami\PHPFormsApi as FAPI;
use Degami\PHPFormsApi\Accessories\FormValues;
use Degami\PHPFormsApi\Containers\SeamlessContainer;
use App\Base\Models\Cart;
use App\Base\Models\Address;
use App\Base\Abstracts\Commerce\BaseShippingMethod;

class MatrixRate extends BaseShippingMethod
{
    /**
     * {@inheritdoc}
     */
    public function getCode(): string
    {
        return 'matrixrate';
    }

    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'Matrix Rate';
    }

    protected function getSavedRules() : array
    {
        return  @json_decode(App::getInstance()->getSiteData()->getConfigValue('shipping/matrixrate/matrixrate_rules'), true) ?: [];
    }

    /**
     * {@inheritdoc}
     */
    public function getConfigurationForm(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $countries = App::getInstance()->getUtils()->getCountriesSelectOptions();

        $rules_container = $form->addField('rules', [
            'type' => 'tag_container',
            'id' => 'matrixrate-rules-target',
        ]);

        $saved_rules = $this->getSavedRules();
        $num_rules = 0;

        // Determina quante righe mostrare
        foreach ($form_state['input_values'] as $key => $value) {
            if (preg_match("/^country_[0-9]+$/i", $key)) {
                $num_rules++;
            }
        }

        if ($num_rules == 0) {
            $num_rules = count($saved_rules);
        } 

        if ($num_rules == 0 || $form->isPartial()) {
            $num_rules++;
        }

        for ($i = 0; $i < $num_rules; $i++) {
            $defaults = $saved_rules[$i] ?? [];

            $row = $rules_container->addField("rule_$i", [
                'type' => 'fieldset',
                'title' => "Regola #".($i + 1),
                'inner_attributes' => [
                    'class' => 'row',
                ]
            ]);

            $row
                ->addField("country_$i", [
                    'type' => 'select',
                    'title' => 'Country',
                    'container_class' => 'col-sm-6 pb-2',
                    'options' => ['*' => '-- All --'] + $countries,
                    'validate' => ['required'],
                    'default_value' => $defaults['country'] ?? '*',
                    'weight' => 0,
                ])
                ->addField("zip_pattern_$i", [
                    'type' => 'textfield',
                    'title' => 'ZIP code pattern (eg: 00*,10*,*)',
                    'container_class' => 'col-sm-6 pb-2',
                    'size' => 15,
                    'default_value' => $defaults['zip_pattern'] ?? '*',
                    'weight' => 1,
                ])
                ->addField("weight_from_$i", [
                    'type' => 'number',
                    'title' => 'Weight from (kg)',
                    'container_class' => 'col-sm-6 pb-2',
                    'step' => 0.01,
                    'min' => 0,
                    'default_value' => $defaults['weight_from'] ?? 0,
                    'weight' => 2,
                ])
                ->addField("weight_to_$i", [
                    'type' => 'number',
                    'title' => 'Weight to (kg)',
                    'container_class' => 'col-sm-6 pb-2',
                    'step' => 0.01,
                    'min' => 0,
                    'default_value' => $defaults['weight_to'] ?? 9999,
                    'weight' => 3,
                ])
                ->addField("cost_$i", [
                    'type' => 'number',
                    'title' => 'Cost',
                    'container_class' => 'col-sm-12 pb-2',
                    'step' => 0.01,
                    'min' => 0,
                    'default_value' => $defaults['cost'] ?? 0,
                    'weight' => 4,
                ]);
        }

        $form->addField('add_rule', [
            'type' => 'submit',
            'value' => 'Add Rule',
            'ajax_url' => App::getInstance()->getAdminRouter()->getUrl('crud.app.base.controllers.admin.json.shippingmethodscallback') . '?action=' . App::getInstance()->getEnvironment()->getRequest()->query->get('action') . '&code=' . App::getInstance()->getEnvironment()->getRequest()->query->get('code'),
            'event' => [[
                'event' => 'click',
                'callback' => [static::class, 'matrixrateFormCallback'],
                'target' => 'matrixrate-rules-target',
                'effect' => 'fade',
                'method' => 'replace',
            ]],
        ]);

        return $form;
    }

    public static function matrixrateFormCallback(FAPI\Form $form)
    {
        return $form->getField('rules');
    }

    /**
     * {@inheritdoc}
     */
    public function formSubmitted(FAPI\Form $form, array &$form_state): void
    {
        $values = $form_state['input_values'];
        $rules = [];

        foreach ($values as $key => $val) {
            if (preg_match('/^country_([0-9]+)$/', $key, $matches)) {
                $i = $matches[1];
                $rules[] = [
                    'country' => $values["country_$i"] ?? '*',
                    'zip_pattern' => $values["zip_pattern_$i"] ?? '*',
                    'weight_from' => (float) ($values["weight_from_$i"] ?? 0),
                    'weight_to' => (float) ($values["weight_to_$i"] ?? 9999),
                    'cost' => (float) ($values["cost_$i"] ?? 0),
                ];
            }
        }

        App::getInstance()->getSiteData()->setConfigValue('shipping/matrixrate/active', $values['active']);
        App::getInstance()->getSiteData()->setConfigValue('shipping/matrixrate/matrixrate_rules', json_encode($rules));
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateShippingCosts(Address $shippingAddress, Cart $cart): float
    {
        $rules = $this->getSavedRules();
        if (empty($rules)) {
            return 0.0;
        }

        $country = strtoupper($shippingAddress?->getCountryCode() ?? '*');
        $zip = $shippingAddress?->getPostcode() ?? '';
        $weight = $cart->getBillableWeight();

        foreach ($rules as $rule) {
            $countryMatch = ($rule['country'] === '*' || strtoupper($rule['country']) === $country);
            $zipMatch = ($rule['zip_pattern'] === '*' || fnmatch($rule['zip_pattern'], $zip));
            $weightMatch = ($weight >= $rule['weight_from'] && $weight <= $rule['weight_to']);

            if ($countryMatch && $zipMatch && $weightMatch) {
                return (float) $rule['cost'];
            }
        }

        return 0.0;
    }

    /**
     * {@inheritdoc}
     */
    public function getShippingFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state): FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('matrixrate', ['type' => 'seamless_container']);

        $totalCosts = $this->evaluateShippingCosts($cart->getShippingAddress() ?? App::getInstance()->containerMake(Address::class), $cart);
        $formatted = App::getInstance()->getUtils()->formatPrice($totalCosts, $cart->getCurrencyCode());

        $out->addField('shipping_costs', [
            'type' => 'markup',
            'value' => App::getInstance()->getUtils()->translate("Shipping costs: %s", [$formatted]),
        ]);

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function calculateShipping(?FormValues $values, Cart $cart): array
    {
        $cost = 0.0;
        $shippingAddress = $cart->getShippingAddress();

        if ($shippingAddress) {
            $cost = $this->evaluateShippingCosts($shippingAddress, $cart);
        }

        return [
            'shipping_cost' => $cost,
            'additional_data' => ['calculate_when' => time()],
        ];
    }

    public function showEvenIfNotCheapest() : bool
    {
        return true;
    }
}
