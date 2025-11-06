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
use Degami\PHPFormsApi as FAPI;
use Degami\PHPFormsApi\Containers\SeamlessContainer;
use App\Base\Models\Cart;
use App\Base\Models\OrderStatus;
use Degami\PHPFormsApi\Accessories\FormValues;
use App\Base\Abstracts\Commerce\BasePaymentMethod;
use App\Base\Models\StoreCredit as StoreCreditModel;

class StoreCredit extends BasePaymentMethod
{
    public function getCode() : string
    {
        return 'store_credit';
    }

    public function getName(): string
    {
        return 'Store Credit';
    }

    /**
     * {@inheritdoc}
     */
    public function isActive(Cart $cart): bool
    {
        if (!parent::isActive($cart)) {
            return false;
        }

        $storeCredit = StoreCreditModel::getCollection()->where(['user_id' => $cart->getUserId(), 'website_id' => $cart->getWebsiteId()])->getFirst();

        if ($storeCredit === null || $storeCredit->getCredit() <= $cart->getTotalInclTax()) {
            return false;
        }

        return true;
    }


    public function getConfigurationForm(FAPI\Form $form, array &$form_state) : FAPI\Form
    {
        return $form;
    }

    public function getPaymentFormFieldset(Cart $cart, FAPI\Form $form, array &$form_state) : FAPI\Interfaces\FieldsContainerInterface
    {
        /** @var SeamlessContainer $out */
        $out = $form->getFieldObj('store_credit', [
            'type' => 'seamless_container',
        ]);

        $storeCredit = StoreCreditModel::getCollection()->where(['user_id' => $cart->getUserId(), 'website_id' => $cart->getWebsiteId()])->getFirst()?->getCredit() ?? 0;

        $formattedStoreCredit = App::getInstance()->getUtils()->formatPrice($storeCredit, $cart->getCurrencyCode());
        $out->addMarkup(App::getInstance()->getUtils()->translate('You can use your store credit to pay for this order. Your current store credit balance is: <strong>%s</strong>', [$formattedStoreCredit]));

        return $out;
    }

    /**
     * {@inheritdoc}
     */
    public function executePayment(?FormValues $values, Cart $cart) : array
    {
        $status = OrderStatus::NOT_PAID;
        /** @var StoreCreditModel|null $storeCredit */
        $storeCredit = StoreCreditModel::getCollection()->where(['user_id' => $cart->getUserId(), 'website_id' => $cart->getWebsiteId()])->getFirst();

        /** @var StoreCreditTransaction|null $transaction */
        $transaction = null;
        if ($storeCredit && $storeCredit->getCredit() >= $cart->getTotalInclTax()) {
            $transaction = $storeCredit->makeTransaction(-$cart->getTotalInclTax(), $cart->getOwner(), $cart->getWebsite());
            $status = OrderStatus::PAID;
        }

        return [
            'status' => $status,
            'transaction_id' => $transaction?->getTransactionId(),
            'additional_data' => ['when' => time()]
        ];
    }
}