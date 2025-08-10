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

namespace App\Base\Commerce\PaymentMethods;

use App\App;
use App\Base\Interfaces\Commerce\PaymentMethodInterface;
use Degami\PHPFormsApi as FAPI;
use Degami\PHPFormsApi\Containers\SeamlessContainer;
use App\Base\Models\Cart;
use App\Base\Models\Order;
use App\Base\Models\OrderItem;
use App\Base\Models\OrderStatus;
use Degami\PHPFormsApi\Accessories\FormValues;

class Scalapay implements PaymentMethodInterface
{
    const SCALAPAY_LOGO = '<?xml version="1.0" encoding="UTF-8"?><svg id="Scalapay_checkout-badge_pink_189x42" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 189 42"><rect x="0" y="0" width="189" height="42" rx="21" ry="21" fill="#f7cbcf"/><g><path d="M24.04,31.22c-.37-.01-.68-.13-.94-.37-.09-.09-.18-.19-.27-.29-3.45-3.94-6.89-7.88-10.34-11.82-.09-.1-.18-.19-.26-.3-.37-.51-.35-1.13,.03-1.61,.08-.1,.18-.19,.26-.29,1.53-1.58,3.07-3.17,4.61-4.75,.09-.09,.18-.19,.28-.28,.52-.45,1.22-.45,1.74,0,.13,.11,.24,.23,.35,.35,1.08,1.11,2.16,2.23,3.24,3.34,.12,.12,.23,.24,.35,.35,.16,.16,.36,.26,.58,.31,.43,.11,.82,0,1.16-.27,.13-.11,.24-.23,.36-.35,1.06-1.09,2.11-2.17,3.17-3.26,.12-.12,.23-.25,.35-.35,.52-.47,1.26-.48,1.78,0,.1,.09,.18,.18,.28,.28,1.53,1.57,3.04,3.14,4.57,4.71,.06,.06,.13,.13,.19,.21,.45,.5,.5,1.19,.06,1.75-.07,.09-.14,.17-.22,.26-3.39,3.87-6.76,7.75-10.15,11.62-.11,.13-.22,.26-.34,.38-.22,.26-.53,.37-.86,.4Z"/><path d="M122.32,24.68V13.51c0-.18,0-.38,.01-.56,0-.06,.09-.14,.15-.15,.09,0,.18-.02,.28-.02h3.09c.06,0,.11,0,.17,0,.18,0,.25,.07,.26,.23,0,.17,0,.33,0,.5,0,.11,.01,.22,.02,.33,0,.04,.1,.08,.13,.06,.14-.09,.28-.19,.42-.28,1.04-.69,2.17-1.08,3.41-1.15,2.68-.14,4.8,.92,6.4,3.04,.75,.99,1.21,2.1,1.46,3.31,.14,.7,.23,1.41,.21,2.11-.04,1.5-.38,2.92-1.14,4.24-1.16,2.02-2.9,3.23-5.16,3.71-.82,.17-1.64,.18-2.46,.09-.9-.11-1.74-.43-2.51-.92-.16-.1-.31-.21-.47-.31-.06-.04-.16,0-.16,.09,0,.18,0,.38,0,.56v7.86c0,.31-.04,.35-.35,.35h-3.48c-.21,0-.28-.06-.28-.26,0-.11,0-.23,0-.33v-.62c0-3.57,0-7.15,0-10.72Zm4.12-3.91h0v2.13c0,.18,.05,.33,.16,.46,.4,.5,.87,.92,1.45,1.22,1.04,.54,2.14,.68,3.26,.35,1.23-.35,2.06-1.17,2.55-2.34,.28-.67,.38-1.36,.34-2.09-.04-.88-.29-1.69-.79-2.42-.57-.84-1.34-1.39-2.34-1.61-.54-.12-1.08-.11-1.62-.02-1.15,.21-2.09,.77-2.84,1.67-.13,.15-.18,.31-.18,.51,.01,.72,0,1.43,0,2.14Z"/><path d="M117.58,20.77v7.3c0,.67,.06,.65-.62,.65h-2.92c-.38,0-.41-.04-.42-.41,0-.23-.01-.45-.03-.67,0-.02-.06-.05-.09-.05-.04,0-.07,.02-.1,.04-.08,.05-.15,.11-.23,.16-1.16,.87-2.48,1.26-3.93,1.25-2.39-.02-4.92-1.24-6.36-3.6-.7-1.14-1.11-2.36-1.25-3.68-.17-1.52,.03-2.99,.59-4.4,.53-1.35,1.36-2.49,2.51-3.39,.99-.77,2.11-1.28,3.36-1.44,1.83-.23,3.53,.1,5.07,1.18,.11,.08,.22,.14,.33,.21,.02,0,.07-.01,.09-.04,.02-.03,.04-.06,.04-.1,0-.15,0-.3,0-.45,0-.53,0-.54,.52-.54h2.92c.13,0,.26,0,.39,.04,.04,0,.09,.06,.12,.11,.02,.05,.02,.11,.02,.16v.62c-.01,2.33-.01,4.68-.01,7.05Zm-4.13,0h0c0-.71,0-1.42,0-2.13,0-.2-.06-.36-.18-.51-.62-.74-1.37-1.26-2.29-1.54-.69-.21-1.4-.26-2.11-.11-1.02,.2-1.81,.77-2.39,1.63-.38,.57-.62,1.19-.72,1.87-.16,1.01-.05,1.98,.4,2.9,.48,.99,1.23,1.67,2.28,2.04,.48,.17,.98,.22,1.49,.21,1.39-.06,2.51-.65,3.38-1.73,.11-.13,.16-.28,.16-.46-.02-.72-.01-1.43-.01-2.14Z"/><path d="M156.89,20.74v7.23c0,.17,0,.33,0,.5,0,.16-.07,.22-.25,.23-.06,0-.11,0-.17,0h-3.14c-.06,0-.11,0-.17,0-.13,0-.21-.08-.21-.21,0-.24,0-.48-.01-.73,0-.06,0-.11-.03-.16,0-.02-.07-.04-.09-.04-.1,.05-.19,.11-.28,.18-.4,.31-.84,.56-1.31,.77-.73,.31-1.49,.48-2.29,.5-2.27,.06-4.22-.72-5.8-2.35-.89-.92-1.48-2.02-1.86-3.23-.36-1.18-.48-2.38-.36-3.6,.13-1.38,.55-2.67,1.28-3.85,.65-1.05,1.5-1.91,2.56-2.54,1.05-.62,2.18-.97,3.4-1,1.18-.04,2.31,.17,3.37,.7,.32,.16,.62,.34,.92,.54,.11,.08,.22,.14,.33,.21,.04,.02,.13-.02,.13-.06,0-.07,.01-.15,.01-.22,0-.21,0-.41,0-.62,0-.13,.07-.21,.2-.22,.08,0,.15,0,.23,0h3.09c.11,0,.22,.02,.33,.04,.03,0,.06,.04,.08,.08,.02,.05,.03,.11,.03,.16,0,.17,0,.33,0,.5,.01,2.39,.01,4.78,.01,7.17Zm-4.13,0c0-.71,0-1.42,0-2.13,0-.2-.07-.35-.19-.5-.74-.87-1.66-1.44-2.79-1.64-.69-.12-1.38-.11-2.04,.13-.99,.34-1.7,.99-2.18,1.91-.67,1.3-.75,2.65-.23,4.01,.44,1.16,1.26,1.97,2.44,2.38,.47,.16,.94,.21,1.43,.2,1.39-.05,2.51-.63,3.39-1.71,.12-.15,.18-.32,.18-.51,0-.72,0-1.43,0-2.14Z"/><path d="M89.15,28.64s-.07,.05-.11,.06c-.07,.01-.15,.02-.22,.02h-3.26c-.07,0-.15-.01-.22-.04-.03,0-.06-.04-.08-.07-.02-.05-.03-.11-.04-.16,0-.24,0-.49,0-.73,0-.05,0-.12-.08-.13-.05,0-.11,.01-.15,.04-.06,.04-.12,.09-.18,.13-1.18,.89-2.51,1.29-3.98,1.27-2.35-.02-4.89-1.21-6.36-3.61-.73-1.21-1.16-2.51-1.26-3.91-.11-1.41,.06-2.76,.57-4.07,.5-1.28,1.25-2.38,2.3-3.27,1.04-.88,2.23-1.45,3.58-1.63,1.82-.25,3.54,.09,5.07,1.16,.12,.09,.25,.16,.38,.23,.01,0,.09-.03,.09-.05,.01-.18,.02-.38,.03-.56,0-.53,0-.53,.53-.53h2.92c.09,0,.18,0,.28,0,.16,0,.22,.08,.23,.26,0,.13,0,.26,0,.39v14.65c-.01,.2,0,.38-.05,.56Zm-4.09-7.91c0-.67-.01-1.35,0-2.02,0-.28-.09-.5-.28-.7-1-1.1-2.23-1.66-3.73-1.61-.85,.03-1.6,.33-2.25,.88-.48,.4-.84,.9-1.09,1.48-.5,1.16-.58,2.35-.18,3.56,.41,1.24,1.23,2.11,2.47,2.57,.48,.18,.98,.23,1.49,.21,1.35-.05,2.44-.6,3.31-1.63,.16-.19,.25-.4,.25-.66-.01-.7,0-1.39,0-2.08Z"/><path d="M164.12,12.81c.11,.22,.2,.4,.28,.59,1.31,2.77,2.61,5.55,3.92,8.32,.08,.16,.13,.35,.28,.48,.12-.05,.14-.16,.19-.26,.26-.6,.53-1.2,.78-1.8,.98-2.26,1.97-4.53,2.95-6.79,.07-.17,.16-.34,.24-.52,.11-.01,.22-.04,.33-.05,.32,0,.64,0,.95,0h2.53c.13,0,.26,.02,.39,.04,.04,0,.07,.04,.06,.09-.03,.11-.06,.21-.1,.31-.43,.97-.87,1.95-1.3,2.92-2.95,6.63-5.9,13.26-8.84,19.89-.09,.18-.15,.38-.29,.56-.09,0-.18,.02-.27,.02h-3.65c-.07,0-.15,0-.22-.03-.02,0-.06-.06-.05-.09,.02-.09,.05-.18,.09-.26,.25-.57,.5-1.13,.75-1.69,1.01-2.27,2.03-4.54,3.04-6.81,.07-.16,.13-.31,.21-.46,.05-.11,.05-.21,0-.33-.09-.17-.16-.34-.24-.5-2.09-4.35-4.17-8.7-6.26-13.05-.07-.15-.13-.31-.2-.46-.01-.04,.04-.13,.09-.13,.07,0,.15-.01,.22-.01h3.76c.1,0,.21,.01,.37,.03Z"/><path d="M68.15,23.06c.09,.06,.18,.11,.26,.18,.92,.73,1.88,1.42,2.77,2.19,.01,.01,.01,.04,.02,.05,0,.04-.01,.08-.04,.11-.86,1.19-1.92,2.14-3.27,2.74-.86,.39-1.77,.61-2.7,.67-.5,.04-1.01,.04-1.51,.01-3.36-.19-6.32-2.43-7.3-5.85-.28-.97-.4-1.94-.31-2.94,.23-2.92,1.57-5.19,4.11-6.69,1.18-.7,2.48-1.03,3.85-1.07,1.53-.05,2.97,.28,4.31,1.04,.87,.49,1.63,1.12,2.26,1.89,.05,.06,.09,.12,.12,.18,.01,.03,0,.08-.01,.11-.06,.07-.12,.14-.19,.21-.77,.71-1.55,1.41-2.32,2.11-.07,.06-.14,.12-.21,.18-.06,.05-.14,.04-.2-.03-.09-.1-.17-.2-.26-.29-.31-.33-.65-.62-1.03-.86-1.43-.89-3.3-.79-4.59,.17-.77,.58-1.29,1.35-1.55,2.28-.26,.96-.25,1.92,.08,2.87,.61,1.8,2.16,2.65,3.61,2.75,1.52,.1,2.73-.48,3.71-1.61,.11-.13,.21-.26,.33-.38,.01-.01,.04-.01,.07-.03Z"/><path d="M53.04,14.09c-.66,1.01-1.23,2.02-1.9,2.97,0,.01-.04,.01-.08,.03-.13-.08-.27-.16-.41-.25-.55-.34-1.13-.59-1.76-.7-.57-.11-1.16-.1-1.72,.07-.22,.06-.43,.15-.6,.29-.11,.09-.23,.2-.31,.32-.27,.39-.22,.84,.12,1.17,.15,.14,.32,.26,.5,.35,.27,.13,.55,.25,.82,.35,.51,.19,1.02,.36,1.53,.55,.55,.19,1.09,.41,1.6,.68,.4,.21,.78,.45,1.12,.74,.96,.79,1.48,1.82,1.55,3.06,.04,.6,0,1.2-.18,1.77-.28,.84-.77,1.53-1.45,2.09-.69,.57-1.48,.94-2.33,1.19-.78,.22-1.58,.3-2.38,.28-1.78-.05-3.42-.56-4.95-1.46-.27-.16-.53-.35-.79-.53-.15-.1-.16-.17-.06-.33,.57-.88,1.14-1.75,1.72-2.63,.03-.04,.07-.09,.11-.12,.02-.02,.08-.03,.1-.01,.09,.06,.18,.13,.28,.19,1.16,.87,2.48,1.28,3.93,1.28,.28,0,.56-.03,.83-.13,.16-.06,.31-.12,.45-.21,.13-.08,.24-.18,.34-.28,.38-.42,.38-.99,.01-1.43-.15-.17-.33-.31-.52-.42-.25-.13-.5-.26-.75-.37-.48-.19-.98-.36-1.47-.55-.47-.18-.94-.36-1.41-.56-.35-.14-.67-.33-.99-.53s-.62-.43-.88-.69c-.6-.6-.97-1.31-1.09-2.16-.21-1.55,.25-2.88,1.37-3.97,.7-.68,1.54-1.11,2.47-1.39,.52-.16,1.06-.24,1.6-.26,1.68-.07,3.26,.31,4.77,1.06,.22,.11,.42,.25,.62,.38,.05,.04,.09,.1,.19,.18Z"/><path d="M93.95,17.04V6.21c0-.94-.1-.81,.79-.82,.88,0,1.76,0,2.64,0,.17,0,.33,0,.5,.01,.1,0,.16,.08,.17,.18,0,.15,0,.3,0,.45V28.09c0,.15,0,.3,0,.45,0,.06-.08,.15-.13,.16-.07,0-.15,.02-.22,.02h-3.36c-.07,0-.15-.01-.22-.02-.06,0-.13-.09-.14-.16,0-.06-.01-.11-.01-.17v-.5c-.01-3.61-.01-7.21-.01-10.82Z"/></g></svg>';

    public function getCode() : string
    {
        return 'scalapay';
    }

    public function getName(): string
    {
        return 'Scalapay';
    }

    public function isActive(Cart $cart): bool
    {
        return App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/active') == true;
    }

    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        $form
            ->addField('is_live', [
                'type' => 'switchbox',
                'title' => 'Is Live',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/is_live'),
            ])
            ->addField('api_key', [
                'type' => 'textfield',
                'title' => 'Api Key',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/api_key'),
            ])
            ->addField('merchant_token', [
                'type' => 'textfield',
                'title' => 'Merchant Token',
                'default_value' => App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/merchant_token'),
            ]);
        return $form;
    }

    public function getPaymentFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state) : FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('scalapay', [
            'type' => 'seamless_container',
        ]);

        $out->addMarkup('<h5>' . App::getInstance()->getUtils()->translate('Proceed with') . ' <span class="scalapay-logo" style="display: inline-block; width: 150px;">' . self::SCALAPAY_LOGO . '</span></h5>');
        $out->addMarkup('<script type="module" src="https://cdn.scalapay.com/widget/scalapay-widget-loader.js?version=V5"></script>');
        $out->addMarkup('<scalapay-widget
                amount-selectors=\'["#cart-total"]\'
                environment=\'integration\'
                merchant-token=\''.App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/merchant_token').'\'
        ></scalapay-widget>');
        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function executePayment(?FormValues $values, Cart $cart) : array
    {
        return [
            'status' => OrderStatus::WAITING_FOR_PAYMENT, 
            'transaction_id' => 'fake_payment_'.uniqid(), 
            'additional_data' => [],
            'post_create_callback' => function (Order $order) {
                $payload = $this->buildOrderPayload($order);
                $response = $this->sendScalapayOrder($payload);

                return [
                    'redirect_url' => $response->getCheckoutUrl(), //$response['checkoutUrl'],
                    'payment_id' => $response->getToken(), //$response['token'],
                ];
            },
        ];
    }


    protected function sendScalapayOrder(\Scalapay\Sdk\Model\Order\OrderDetails $payload) : \Scalapay\Sdk\Model\Order\OrderResponse
    {
        $scalapayApi = \Scalapay\Sdk\Api::configure(
            App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/api_key'), 
            App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/is_live') 
                ? \Scalapay\Sdk\Model\Api\Client::PRODUCTION_URI
                : \Scalapay\Sdk\Model\Api\Client::SANDBOX_URI
        );
        $apiResponse = $scalapayApi->createOrder($payload);

        return $apiResponse;
    }

    protected function buildOrderPayload(Order $order): \Scalapay\Sdk\Model\Order\OrderDetails
    {
        $billingAddress = $order->getBillingAddress();
        $shippingAddress = $order->getShippingAddress() ?? $billingAddress;
        $currencyCode = $order->getCurrencyCode() ?? 'EUR';

        // Create Order API
        // set Customer object
        $consumer = new \Scalapay\Sdk\Model\Customer\Consumer();
        $consumer->setEmail($billingAddress->getEmail())
            ->setGivenNames($billingAddress->getFirstName())
            ->setSurname($billingAddress->getLastName())
            ->setPhoneNumber($billingAddress->getPhone());

        // set Billing Details Contact object
        $billing = new \Scalapay\Sdk\Model\Customer\Contact();
        $billing->setName($billingAddress->getFullName())
            ->setLine1(trim($billingAddress->getAddress1() . ' '.$billingAddress->getAddress2()))
//            ->setSuburb('Test')
            ->setPostcode($billingAddress->getPostcode())
            ->setCountryCode($billingAddress->getCountryCode())
            ->setPhoneNumber($billingAddress->getPhone());

        // set Shipping Details Contact object
        $shipping = new \Scalapay\Sdk\Model\Customer\Contact();
        $shipping->setName($shippingAddress->getFullName())
            ->setLine1(trim($shippingAddress->getAddress1() . ' '.$shippingAddress->getAddress2()))
//            ->setSuburb('Test')
            ->setPostcode($shippingAddress->getPostcode())
            ->setCountryCode($shippingAddress->getCountryCode())
            ->setPhoneNumber($shippingAddress->getPhone());

        // set Item object list
        $itemList = [];
        foreach ($order->getItems() as $orderItem) {
            /** @var OrderItem $orderItem */
            $item = new \Scalapay\Sdk\Model\Order\OrderDetails\Item();
            $item->setName($orderItem->getProduct()->getName())
                ->setSku($orderItem->getProduct()->getSku())
                ->setQuantity($orderItem->getQuantity());

            $itemPrice = new \Scalapay\Sdk\Model\Order\OrderDetails\Money();
            $itemPrice->setAmount(number_format($orderItem->getUnitPrice(), 2, '.', ''))
                ->setCurrency($currencyCode);

            $item->setPrice($itemPrice);
            $itemList[] = $item;
        }


        // set Merchant Options object
        // Replace Confirm and Failure URLS with your own redirect urls
        $merchantOptions = new \Scalapay\Sdk\Model\Merchant\MerchantOptions();
        $merchantOptions->setRedirectConfirmUrl(App::getInstance()->getWebRouter()->getUrl('frontend.commerce.checkout.typ'))
                ->setRedirectCancelUrl(App::getInstance()->getWebRouter()->getUrl('frontend.commerce.checkout.ko'));

        // set Order total amount object
        $totalAmount = new \Scalapay\Sdk\Model\Order\OrderDetails\Money();
        $totalAmount->setAmount($order->getTotalInclTax())->setCurrency($currencyCode);

        // set Tax total amount object
        $taxAmount = new \Scalapay\Sdk\Model\Order\OrderDetails\Money();
        $taxAmount->setAmount($order->getTaxAmount())->setCurrency($currencyCode);

        // set Shipping total amount object
        $shippingAmount = new \Scalapay\Sdk\Model\Order\OrderDetails\Money();
        $shippingAmount->setAmount($order->getShippingAmount())->setCurrency($currencyCode);

        // set Discount total object
        $discountAmount = new \Scalapay\Sdk\Model\Order\OrderDetails\Money();
        $discountAmount->setAmount($order->getDiscountAmount())->setCurrency($currencyCode);
        $discount = new \Scalapay\Sdk\Model\Order\OrderDetails\Discount();
        $discount->setDisplayName('Discount')
            ->setAmount($discountAmount);
        $discountList = array();
        $discountList[] = $discount;

        // set Frequency object
        $frequency = new \Scalapay\Sdk\Model\Order\OrderDetails\Frequency();
        $frequency->setFrequencyType('monthly')
            ->setNumber(1);

/*            
        // set Risk object (optional)
        $risk = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Risk();
        $risk->setReturning(true) // valorize as true if the customer has placed an order with Scalapay before else false
            ->setOrderCountLifetime(10)
            ->setOrderCountL30d(3)
            ->setOrderCountL180d(9)
            ->setOrderCountLifetimeRefunded(1)
            ->setOrderCountL30dRefunded(1)
            ->setOrderAmountLifetimeEur(340.42)
            ->setOrderAmountL30dEur(78.32)
            ->setOrderAmountLifetimeRefundedEur(12.22)
            ->setOrderAmountL30dRefundedEur(12.22)
            ->setLastOrderTimestamp('2024-10-01 00:00:00')
            ->setLastRefundTimestamp('2024-01-01 00:00:00')
            ->setAccountCreationTimestamp('2022-01-01 00:00:00')
            ->setPaymentMethodCount(4)
            ->setShippingAddressCount(2)
            ->setShippingAddressTimestamp('2023-01-01 00:00:00')
            ->setShippingAddressUseCount(3)
            ->setPhoneVerifiedTimestamp('2023-01-01 00:00:00');

        // set Transportation Reservation Details object (optional)
        $transportationReservationDetails = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Industry\Travel\TransportationReservationDetails();
        $transportationReservationDetails->setType('air')
            ->setDeparture('BCN')
            ->setArrival('MXP')
            ->setTicketClass('economy')
            ->setTicketType('one_way')
            ->setLoyaltyProgramme(true);

        // set Hotel Reservation Details object (optional)
        $hotelReservationDetails = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Industry\Travel\HotelReservationDetails();
        $hotelReservationDetails->setNights(4)
            ->setHotelName('Best Western')
            ->setHotelCountry('IT')
            ->setHotelStars(4)
            ->setInsurance(true)
            ->setLoyaltyProgramme(true);

        // set Events object (optional)
        $events = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Industry\Travel\Events();
        $events->setEventName('Muse Concert in Rome')
            ->setEventCountry('IT')
            ->setCategory('concert')
            ->setTicketType('singleuse');
            
        // set Travel object (optional)
        $travel = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Industry\Travel();
        $travel->setPersonsCount(1)
            ->setStartDate('2023-06-01')
            ->setEndDate('2023-06-10')
            ->setRefundable(true)
            ->setTransportationReservationDetails([$transportationReservationDetails])
            ->setHotelReservationDetails($hotelReservationDetails)
            ->setEvents($events);

        // set Industry object (optional)
        $industry = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Industry();
        $industry->setCheckoutAttemptDeclineCount(2)
            ->setShippingLat(0.0)
            ->setShippingLng(0.0)
            ->setTravel($travel);
            
        // set Notification object (optional)
        $notification = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Type\Link\Notification();
        $notification->setPreferredLanguage('italiano')
            ->setEmailAddress('scalapay-customer-example@gmail.com')
            ->setPhoneCountryCode('+39')
            ->setPhoneNumber('3353535335')
            ->setChannels(['sms']);
            
        // set Link object (optional)
        $link = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Type\Link();
        $link->setNotification($notification);

        // set Type object (optional)
        $type = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\Type();
        $type->setLink($link);
*/
        // set Plugin Details object
        $pluginDetails = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions\PluginDetails();
        $pluginDetails->setCheckout('sitebase')
            ->setPlatform('SiteBase')
            ->setCustomized('0')
            ->setPluginVersion('1.0.0')
            ->setCheckoutVersion('1.0')
            ->setPlatformVersion('1.0');

        // set Extensions object
        $extensions = new \Scalapay\Sdk\Model\Order\OrderDetails\Extensions();
        $extensions
            //->setRisk($risk)
            //->setIndustry($industry)
            //->setType($type)
            ->setPluginDetails($pluginDetails);

        // set Order Details object
        $orderDetails = new \Scalapay\Sdk\Model\Order\OrderDetails();
        $orderDetails->setConsumer($consumer)
            ->setBilling($billing)
            ->setShipping($shipping)
            ->setMerchant($merchantOptions)
            ->setItems($itemList)
            ->setTotalAmount($totalAmount)
            ->setShippingAmount($shippingAmount)
            ->setTaxAmount($taxAmount)
            ->setDiscounts($discountList)
            ->setMerchantReference($order->getOrderNumber()) // merchant reference is the order id in your platform
            ->setType('online')
            ->setProduct('pay-in-3')
            ->setFrequency($frequency)
            ->setExtensions($extensions);

        return $orderDetails;
    }

//    protected function sendScalapayOrder(array $payload)
//    {
//        $base_uri = 'https://api.staging.scalapay.com/v2/';
//        if (App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/is_live')) {
//            $base_uri = 'https://api.scalapay.com/v2/';
//        }

//        $client = new \GuzzleHttp\Client([
//            'base_uri' => $base_uri,
//            'headers' => [
//                'Authorization' => 'Bearer ' . App::getInstance()->getSiteData()->getConfigValue('payments/scalapay/api_key'),
//                'Content-Type' => 'application/json',
//            ],
//        ]);

//        $res = $client->post('orders', ['json' => $payload]);
//        return json_decode($res->getBody()->getContents(), true);
//    }

//    protected function buildOrderPayload(Order $order): array
//    {
//        $billing = $order->getBillingAddress();
//        $currency = $order->getCurrencyCode() ?? 'EUR';

//        $items = [];
//        foreach ($order->getItems() as $item) {
//            /** @var OrderItem $item */
//            $items[] = [
//                'name' => $item->getProduct()?->getName() ?? 'Product ' . $item->getId(),
//                'category' => $item->getCategory() ?? 'general',
//                'quantity' => $item->getQuantity(),
//                'price' => [
//                    'amount' => number_format($item->getUnitPrice(), 2, '.', ''),
//                    'currency' => $currency,
//                ],
//            ];
//        }

//        return [
//            'totalAmount' => [
//                'amount' => number_format($order->getTotalInclTax(), 2, '.', ''),
//                'currency' => $currency,
//            ],
//            'consumer' => [
//                'givenNames' => $billing->getFirstName(),
//                'surname' => $billing->getLastName(),
//                'email' => $billing->getEmail(),
//            ],
//            'billing' => [
//                'name' => $billing->getFullName(),
//                'line1' => $billing->getAddressLine1(),
//                'line2' => $billing->getAddressLine2() ?? '',
//                'suburb' => $billing->getCity(),
//                'postcode' => $billing->getPostcode(),
//                'countryCode' => strtoupper($billing->getCountryCode() ?? 'IT'),
//            ],
//            'merchant' => [
//                'redirectConfirmUrl' => App::getInstance()->getWebRouter()->getUrl('frontend.commerce.checkout.typ'),
//                'redirectCancelUrl' => App::getInstance()->getWebRouter()->getUrl('frontend.commerce.checkout.ko'),
//            ],
//            'items' => $items,
//        ];
//    }
}