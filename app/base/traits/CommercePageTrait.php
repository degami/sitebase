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

namespace App\Base\Traits;

use App\App;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Models\Cart;
use App\Base\Models\Order;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Models\UserSession;
use App\Base\Models\Address;

/**
 * Commerce Page Trait
 */
trait CommercePageTrait
{
    use TemplatePageTrait, PageTrait;

    protected ?Cart $cart = null;

    public function getCart() : ?Cart
    {
        if ($this->cart instanceof Cart) {
            return $this->cart;
        }

        $cart = Cart::getCollection()->where([
            'user_id' => $this->getCurrentUser()->getId(),
            'website_id' => $this->getCurrentWebsite()->getId(),
            'is_active' => true,
        ])->getFirst();

        if ($cart instanceof Cart) {
            $this->cart = $cart;
            return $this->cart;
        }

        if (!$this->hasLoggedUser()) {
            return null;
        }

        // prepare a new empty cart and persist it
        $this->cart = App::getInstance()->containerMake(Cart::class);
        $this->cart
            ->setUserId($this->getCurrentUser()->getId())
            ->setWebsiteId($this->getCurrentWebsite()->getId())
            ->setIsActive(true)

            ->setAdminCurrencyCode($this->getAdminCurrencyCode())
            ->setCurrencyCode($this->getCurrentWebsite()->getDefaultCurrencyCode())

            ->persist();

        return $this->cart;
    }

    protected function getAddresses() : array
    {
        return Address::getCollection()->where(['user_id' => $this->getCurrentUser()->getId()])->getItems();
    }

    /**
     * {@intheritdocs}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender() : BasePage|Response
    {
        if (!$this->hasLoggedUser()) {
            return $this->returnAfterLogin();
        }

        return parent::beforeRender();
    }

    /**
     * Gets the current order from the user session.
     */
    public function getOrder() : ?Order
    {
        /** @var UserSession $userSession */
        $userSession = $this->getCurrentUser()->getUserSession();
        $orderId = $userSession?->getSessionKey('commerce.checkout.order');
        if (!$orderId) {
            return null;
        }

        $order = Order::getCollection()->where([
            'id' => $orderId,
            'user_id' => $this->getCurrentUser()->getId(),
            'website_id' => $this->getCurrentWebsite()->getId(),
        ])->getFirst();

        if (!($order instanceof Order)) {
            return null;
        }

        return $order;
    }

    public function getAdminCurrencyCode() : string
    {
        return $this->getCurrentWebsite()->getDefaultCurrencyCode();
    }
}
