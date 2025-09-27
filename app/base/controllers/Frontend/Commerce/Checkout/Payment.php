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
use App\Base\Interfaces\Commerce\PaymentMethodInterface;
use App\Base\Models\Order;
use App\Base\Models\OrderStatus;
use App\Base\Traits\CommercePageTrait;
use App\Base\Models\UserSession;
use Degami\PHPFormsApi as FAPI;
use HaydenPierce\ClassFinder\ClassFinder;

class Payment extends FormPageWithLang
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
        return 'commerce/payment';
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
        return $this->getUtils()->translate('Payment', locale: $this->getCurrentLocale());
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
        $form->setFormId('payment-form');

        $form->addMarkup('<h4>'.$this->getUtils()->translate('Choose your payment method').'</h4>');

        /** @var FAPI\Containers\Accordion $accordion */
        $accordion = $form->addField('payment_methods', [
            'type' => 'accordion',
        ]);

        foreach ($this->getPaymentMethods() as $key => $paymentMethod) {
            /** @var PaymentMethodInterface $paymentMethod */
            if (!$paymentMethod->isActive($this->getCart())) {
                continue;
            }

            $accordion
                ->addAccordion($paymentMethod->getName())
                ->addField('payment_'.$key.'_code', [
                    'type' => 'hidden',
                    'default_value' => $this->getPaymentMethodCode($paymentMethod),
                ])
                ->addField($this->getPaymentMethodCode($paymentMethod), $paymentMethod->getPaymentFormFieldset($this->getCart(), $form, $form_state));
        }

        $accordion->addJs('
            $("#payment_methods").on("accordionactivate", function(event, ui) {
                var activeIndex = $(this).accordion("option", "active");
                $("#selected_payment_method").val($("#payment_"+activeIndex+"_code").val());
            });
        ');

        $form->addField('selected_payment_method', [
            'type' => 'hidden',
            'default_value' => $this->getPaymentMethodCode($this->getPaymentMethods()[$accordion->getActive()]),
        ]);

        $this->addSubmitButton($form, isConfirmation: true, buttonText: 'Submit Payment');

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
        $selected_payment_method = $values['selected_payment_method'];
        $paymentValues = $values['payment_methods'][$selected_payment_method];
        /** @var PaymentMethodInterface $paymentMethod */
        $paymentMethod = current(array_filter($this->getPaymentMethods(), function($paymentMethod) use ($selected_payment_method) {
            return $this->getPaymentMethodCode($paymentMethod) == $selected_payment_method;
        }));
        $paymentResult = $paymentMethod->executePayment($paymentValues, $this->getCart());

        $status = $paymentResult['status'] ?? OrderStatus::CREATED;
        $transaction_id = $paymentResult['transaction_id'] ?? null;
        $additional_data = $paymentResult['additional_data'] ?? null;
        $post_create_callback = $paymentResult['post_create_callback'] ?? null;
        $redirect_url = $paymentResult['redirect_url'] ?? null;

        $orderStatus = OrderStatus::getByStatus($status);

        if (!in_array($status, [OrderStatus::CANCELED, OrderStatus::NOT_PAID])) {
            $url = $this->getUrl('frontend.commerce.checkout.typ');
            if ($this->hasLang()) {
                $url = $this->getUrl('frontend.commerce.checkout.typ.withlang', ['lang' => $this->getCurrentLocale()]);
            }
        } else {
            $url = $this->getUrl('frontend.commerce.checkout.ko');
            if ($this->hasLang()) {
                $url = $this->getUrl('frontend.commerce.checkout.ko.withlang', ['lang' => $this->getCurrentLocale()]);
            }
        }

        /** @var Order $order */
        $order = Order::createFromCart($this->getCart())
            ->setOrderStatus($orderStatus)
            ->persist();

        if ($orderStatus->getStatus() == OrderStatus::PAID) {
            $order->pay($paymentMethod->getName(), $transaction_id, $additional_data);
        }

        // register order id to user session
        /** @var UserSession $userSession */
        $userSession = $this->getCurrentUser()->getUserSession();
        $userSession->addSessionData('commerce.checkout.order', $order->getId())->persist();

        // set cart as "closed"
        $this->getCart()->setIsActive(false)->persist();

        // save payment method into additional_data for later use if needed
        $orderAdditionalData = ['payment_method' => $paymentMethod->getName()]; 

        if (!is_null($post_create_callback)) {
            $callback_result = @call_user_func_array($post_create_callback, [$order]);

            if (is_array($callback_result)) {
                $orderAdditionalData = array_merge($orderAdditionalData, $callback_result);                

                if (isset($callback_result['redirect_url'])) {
                    $redirect_url = $callback_result['redirect_url'];
                }
            }
        }

        // save additional data to order
        $order->setAdditionalData(json_encode($orderAdditionalData))->persist();

        if (!is_null($redirect_url)) {
            $url = $redirect_url;
        }

        return $this->doRedirect($url);
    }

    protected function getPaymentMethods() : array
    {
        return array_values(array_map(function($paymentClassName) {
            return $this->containerMake($paymentClassName);
        }, array_filter(array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_COMMERCE_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::COMMERCE_NAMESPACE, ClassFinder::RECURSIVE_MODE)
        ), function($className) {
            if (!is_subclass_of($className, PaymentMethodInterface::class)) {
                return false;
            }

            $method = $this->containerMake($className);
            return App::getInstance()->getSiteData()->getConfigValue('payments/'.$method->getCode().'/active') == 1;
        })));
    }

    protected function getPaymentMethodCode(PaymentMethodInterface $payment_method) : string
    {
        return $payment_method->getCode() ?: strtolower(str_replace("\\",'_', trim(str_replace([App::BASE_COMMERCE_NAMESPACE, App::COMMERCE_NAMESPACE], '', get_class($payment_method)), "\\")));
    }
}