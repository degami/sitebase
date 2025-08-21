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

namespace App\Base\EventListeners\GraphQL;

use App\App;
use App\Base\Interfaces\EventListenerInterface;
use Gplanchat\EventManager\Event;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use App\Site\GraphQL\Resolvers\Cart as CartResolver;
use RuntimeException;
use App\Base\Models\CartDiscount;
use App\Base\Models\Discount as DiscountModel;

class CommerceEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'register_graphql_query_fields' => [$this, 'RegisterGraphQLQueryFields'],
            'register_graphql_mutation_fields' => [$this, 'RegisterGraphQLMutationFields']
        ];
	}

    public function RegisterGraphQLQueryFields(Event $e) 
    {
        $app = App::getInstance();
        if (!$app->getenv('ENABLE_COMMERCE', false)) {
            return; // Exit if commerce is not enabled
        }

        $object = $e->getData('object');
        $queryFields = &$object->queryFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;

        if (!isset($typesByName['CartItem'])) {
            $typesByName['CartItem'] = new ObjectType([
                'name' => 'CartItem',
                'fields' => [
                    'id' => ['type' => Type::nonNull(Type::int())],
                    'product_id' => ['type' => Type::nonNull(Type::int())],
                    'quantity' => ['type' => Type::nonNull(Type::int())],
                    'price' => ['type' => Type::nonNull(Type::float())],
                    'subtotal' => ['type' => Type::float()],
                    'discount_amount' => ['type' => Type::float()],
                    'tax_amount' => ['type' => Type::float()],
                    'total_incl_tax' => ['type' => Type::float()],
                    'created_at' => ['type' => Type::string()],
                    'updated_at' => ['type' => Type::string()],
                    'product' => ['type' => $typesByName['ProductInterface']],
                ]
            ]);
        }

        if (!isset($typesByName['CartDiscount'])) {
            $typesByName['CartDiscount'] = new ObjectType([
                'name' => 'CartDiscount',
                'fields' => [
                    'id' => ['type' => Type::nonNull(Type::int())],
                    'code' => ['type' => Type::string()],
                    'description' => ['type' => Type::string()],
                    'type' => ['type' => Type::string()],
                    'discount_amount' => ['type' => Type::nonNull(Type::float())],
                    'currency' => ['type' => Type::string()],
                ]
            ]);
        }

        if (!isset($typesByName['Cart'])) {
            $typesByName['Cart'] = new ObjectType([
                'name' => 'Cart',
                'fields' => [
                    'id' => ['type' => Type::nonNull(Type::int())],
                    'user_id' => ['type' => Type::int()],
                    'website_id' => ['type' => Type::int()],
                    'created_at' => ['type' => Type::string()],
                    'updated_at' => ['type' => Type::string()],
                    'items' => ['type' => Type::listOf($typesByName['CartItem'])],
                    'discounts' => ['type' => Type::listOf($typesByName['CartDiscount'])],
                    'subtotal' => ['type' => Type::float()],
                    'tax_amount' => ['type' => Type::float()],
                    'discount_amount' => ['type' => Type::float()],
                    'total_incl_tax' => ['type' => Type::float()],
                    'currency' => ['type' => Type::string()],
                ]
            ]);
        }

        if (!isset($queryFields['cart'])) {
            //  cart: Cart
            $queryFields['cart'] = [
                'type' => $typesByName['Cart'],
            ];
        }
    }

    public function RegisterGraphQLMutationFields(Event $e) 
    {
        $app = App::getInstance();
        if (!$app->getenv('ENABLE_COMMERCE', false)) {
            return; // Exit if commerce is not enabled
        }

        $object = $e->getData('object');
        $mutationFields = &$object->mutationFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;

        if (!isset($mutationFields['addToCart'])) {
            //  cart: Cart
            $mutationFields['addToCart'] = [
                'type' => $typesByName['Cart'],
                'args' => [
                    'productClass' => ['type' => Type::nonNull(Type::string())],
                    'productId' => ['type' => Type::nonNull(Type::int())],
                    'quantity' => ['type' => Type::nonNull(Type::int())],
                ],
                'resolve' => function ($root, $args) use ($app) {
                    $currentUser = $app->getAuth()->getCurrentUser();
                    $currentWesite = $app->getSiteData()->getCurrentWebsite();
                    if (!$currentUser || !$currentUser->getId()) {
                        return null; // or throw an exception if needed
                    }

                    $cart = CartResolver::getCart($currentUser, $currentWesite);
                    $productClass = $args['productClass'] ?? null;
                    $productId = $args['productId'] ?? null;
                    $quantity = $args['quantity'] ?? 1;

                    $cart->fullLoad();

                    $product = $app->containerCall([$productClass, 'load'], [$productId]);
                    $cart->addProduct($product, $quantity);

                    $cart->calculate()->persist();

                    return CartResolver::getCartReturnArray($cart);
                }
            ];
        }

        if (!isset($mutationFields['updateCartItem'])) {
            //  updateCartItem: CartItem
            $mutationFields['updateCartItem'] = [
                'type' => $typesByName['Cart'],
                'args' => [
                    'cartItemId' => ['type' => Type::nonNull(Type::int())],
                    'quantity' => ['type' => Type::nonNull(Type::int())],
                ],
                'resolve' => function ($root, $args) use ($app) {
                    $currentUser = $app->getAuth()->getCurrentUser();
                    $currentWesite = $app->getSiteData()->getCurrentWebsite();
                    if (!$currentUser || !$currentUser->getId()) {
                        return null; // or throw an exception if needed
                    }

                    $cart = CartResolver::getCart($currentUser, $currentWesite);
                    $cartItemId = $args['cartItemId'] ?? null;
                    $quantity = $args['quantity'] ?? null;

                    $cart->fullLoad();

                    $cartItem = $cart->getCartItem($cartItemId);
                    if (!$cartItem) {
                        throw new RuntimeException($app->getUtils()->translate('Cart item not found.'));
                    }

                    if ($quantity) {
                        $cartItem->setQuantity($quantity)->persist();
                    } else {
                        $cartItem->remove();
                    }

                    $cart->calculate()->persist();

                    return CartResolver::getCartReturnArray($cart);
                }
            ];
        }

        if (!isset($mutationFields['removeFromCart'])) {
            //  removeCartItem: CartItem
            $mutationFields['removeFromCart'] = [
                'type' => $typesByName['Cart'],
                'args' => [
                    'cartItemId' => ['type' => Type::nonNull(Type::int())],
                ],
                'resolve' => function ($root, $args) use ($app) {
                    $currentUser = $app->getAuth()->getCurrentUser();
                    $currentWesite = $app->getSiteData()->getCurrentWebsite();
                    if (!$currentUser || !$currentUser->getId()) {
                        return null; // or throw an exception if needed
                    }

                    $cart = CartResolver::getCart($currentUser, $currentWesite);
                    $cartItemId = $args['cartItemId'] ?? null;

                    $cart->fullLoad();

                    $cartItem = $cart->getCartItem($cartItemId);
                    if (!$cartItem) {
                        throw new RuntimeException($app->getUtils()->translate('Cart item not found.'));
                    }

                    $cartItem->remove();
                    $cart->calculate()->persist();

                    return CartResolver::getCartReturnArray($cart);
                }
            ];
        }

        if (!isset($mutationFields['applyCartDiscount'])) {
            //  cartDiscount: CartDiscount
            $mutationFields['applyCartDiscount'] = [
                'type' => $typesByName['Cart'],
                'args' => [
                    'code' => ['type' => Type::nonNull(Type::string())],
                ],
                'resolve' => function ($root, $args) use ($app) {
                    $currentUser = $app->getAuth()->getCurrentUser();
                    $currentWesite = $app->getSiteData()->getCurrentWebsite();
                    if (!$currentUser || !$currentUser->getId()) {
                        return null; // or throw an exception if needed
                    }

                    $cart = CartResolver::getCart($currentUser, $currentWesite);
                    $code = $args['code'] ?? null;

                    if (empty($code)) {
                        throw new RuntimeException('Discount code is required.');
                    }

                    $discount = DiscountModel::getCollection()
                        ->where([
                            'code' => $code,
                            'active' => 1,
                            'website_id' => $currentWesite->getId(),
                        ])
                        ->getFirst();

                    if (!$discount) {
                        throw new RuntimeException('Invalid or inactive discount code.');
                    }

                    $cart->fullLoad();
                    
                    if (in_array($discount->getId(), array_map(fn($d) => $d->getInitialDiscountId(), $cart->getDiscounts() ?? []))) {
                        throw new RuntimeException('Discount code already applied.');
                    }

                    $cartDiscount = CartDiscount::createFromDiscount($discount, $cart);

                    // save the cart discount
                    $cartDiscount->persist();

                    return CartResolver::getCartReturnArray($cart);
                }
            ];
        }

        if (!isset($mutationFields['removeCartDiscount'])) {
            //  cartDiscount: CartDiscount
            $mutationFields['removeCartDiscount'] = [
                'type' => $typesByName['Cart'],
                'args' => [
                    'cartDiscountId' => ['type' => Type::nonNull(Type::int())],
                ],
                'resolve' => function ($root, $args) use ($app) {
                    $currentUser = $app->getAuth()->getCurrentUser();
                    $currentWesite = $app->getSiteData()->getCurrentWebsite();
                    if (!$currentUser || !$currentUser->getId()) {
                        return null; // or throw an exception if needed
                    }

                    $cart = CartResolver::getCart($currentUser, $currentWesite);
                    $cartDiscountId = $args['cartDiscountId'] ?? null;

                    $cartDiscount = CartDiscount::load($cartDiscountId);

                    if (!$cartDiscount) {
                        throw new RuntimeException($app->getUtils()->translate('Cart discount not found.'));
                    }
                
                    if ($cartDiscount->getCartId() !== $cart->getId()) {
                        throw new RuntimeException($app->getUtils()->translate('Cart discount does not belong to this cart.'));
                    }

                    $cartDiscount->delete();
                    $cart->calculate()->persist();

                    return CartResolver::getCartReturnArray($cart);
                }
            ];
        }
    }
}