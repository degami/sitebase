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

namespace App\Site\GraphQL\Resolvers;

use App\App;
use App\Base\Interfaces\GraphQl\ResolverInterface;
use App\Base\Models\User;
use App\Base\Models\Cart as CartModel;
use App\Base\Models\CartItem;
use App\Base\Models\CartDiscount;
use App\Base\Models\Website;

class Cart implements ResolverInterface
{
    public static function resolve(array $args, mixed $source = null): mixed
    {
        $app = App::getInstance();

        if (!$app->getEnv('ENABLE_COMMERCE', false)) {
            return null;
        }

        $currentUser = $app->getAuth()->getCurrentUser();
        if (!$currentUser || !$currentUser->getId()) {
            return null; // or throw an exception if needed
        }

        $currentWesite = $app->getSiteData()->getCurrentWebsite();

        $cart = static::getCart($currentUser, $currentWesite);

        return static::getCartReturnArray($cart);
    }

    public static function getCartReturnArray(CartModel $cart): array
    {
        $out = [
            'id' => $cart->getId(),
            'user_id' => $cart->getUserId(),
            'website_id' => $cart->getWebsiteId(),
            'created_at' => $cart->getCreatedAt(),
            'updated_at' => $cart->getUpdatedAt(),
            'items' => array_map(function (CartItem $item) {
                        return [
                            'id' => $item->getId(),
                            'product_id' => $item->getProductId(),
                            'quantity' => $item->getQuantity(),
                            'price' => $item->getUnitPrice(),
                            'subtotal' => $item->getSubTotal(),
                            'discount_amount' => $item->getDiscountAmount(),
                            'tax_amount' => $item->getTaxAmount(),
                            'total_incl_tax' => $item->getTotalInclTax(),
                            'currency' => $item->getCurrencyCode(),
                            'product' => $item->getProduct() ? [
                                'id' => $item->getProductId(),
                                'class' => $item->getProductClass(),
                                'name' => $item->getProduct()->getName(),
                                'sku' => $item->getProduct()->getSku(),
                                'price' => $item->getProduct()->getPrice(),
                                'tax_class_id' => $item->getProduct()->getTaxClassId(),
                                'is_physical' => $item->getProduct()->isPhysical(),
                            ] : null,
                        ];
                    }, $cart->getItems()),
            'discounts' => array_map(function (CartDiscount $discount) {
                        return [
                            'id' => $discount->getId(),
                            'code' => $discount->getInitialDiscount()->getCode(),
                            'type' => $discount->getInitialDiscount()->getDiscountType(),
                            'description' => $discount->getInitialDiscount()->getTitle(),
                            'discount_amount' => $discount->getDiscountAmount(),
                            'currency' => $discount->getCurrencyCode(),
                        ];
                    }, $cart->getDiscounts() ?? []),
            'subtotal' => $cart->getSubTotal(),
            'tax_amount' => $cart->getTaxAmount(),
            'discount_amount' => $cart->getDiscountAmount(),
            'total_incl_tax' => $cart->getTotalInclTax(),
            'currency' => $cart->getCurrencyCode(),
        ];

        return $out;
    }

    public static function getCart(User $user, Website $website) : ?CartModel
    {
        $cart = CartModel::getCollection()->where([
            'user_id' => $user->getId(),
            'website_id' => $website->getId(),
            'is_active' => true,
        ])->getFirst();

        if ($cart instanceof CartModel) {
            return $cart;
        }

        // prepare a new empty cart and persist it
        $cart = App::getInstance()->containerMake(CartModel::class);
        $cart
            ->setUserId($user->getId())
            ->setWebsiteId($website->getId())
            ->setIsActive(true)

            ->setAdminCurrencyCode($website->getDefaultCurrencyCode())
            ->setCurrencyCode($website->getDefaultCurrencyCode())

            ->persist();

        return $cart;
    }
}