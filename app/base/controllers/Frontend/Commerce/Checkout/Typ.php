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
use App\Base\Abstracts\Controllers\FrontendPage;
use App\Base\Traits\CommercePageTrait;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Models\OrderStatus;

class Typ extends FrontendPage
{
    use CommercePageTrait;

    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return App::getInstance()->getEnv('ENABLE_COMMERCE', false);
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
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('Thank you for your Order', locale: $this->getCurrentLocale());
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'commerce/typ';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        if ($this->getOrder()->getOrderPayment() == null) {
            // if payment is missing, create it

            $additionalData = json_decode($this->getOrder()->getAdditionalData() ?? 'null', true);

            $payment_method = $additionalData['payment_method'] ?? 'Unknown';
            $transaction_id = $additionalData['payment_id'] ?? 'trx_' . uniqid();

            // create order payment
            $this->getOrder()->pay($payment_method, $transaction_id, $this->getOrder()->getAdditionalData());

            // set order status
            $this->getOrder()->setOrderStatus(OrderStatus::getByStatus(OrderStatus::PAID))->persist();
        }

        $this->template_data += [
            'order' => $this->getOrder(),
            'user' => $this->getCurrentUser(),
        ];

        // Clear the order from the session after rendering the page
        $this->getCurrentUser()->getUserSession()?->removeSessionData('commerce.checkout.order')->persist();

        return parent::getTemplateData();
    }

    /**
     * {@inheritdoc}
     *
     * @return Response|self
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        if (!$this->getOrder()) {
            return $this->doRedirect($this->getUrl('frontend.commerce.cart'));            
        }

        return parent::beforeRender();
    }
}