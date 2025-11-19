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
use App\Base\Interfaces\Model\ProductInterface;
use Gplanchat\EventManager\Event;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\ObjectType;
use RuntimeException;
use App\Base\Models\User;
use App\Base\Models\Cart as CartModel;
use App\Base\Models\CartItem;
use App\Base\Models\CartDiscount;
use App\Base\Models\Website;
use App\Base\Models\Discount as DiscountModel;
use GraphQL\Type\Definition\ResolveInfo;
use HaydenPierce\ClassFinder\ClassFinder;

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
        if (!$app->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) {
            return; // Exit if commerce is not enabled
        }

        $object = $e->getData('object');
        $queryFields = &$object->queryFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;
        $entrypoint = $object->entrypoint;

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
            $queryFields['cart'] = $this->getCartQueryDefinition($typesByName, $typesByClass);
        }
    }

    public function RegisterGraphQLMutationFields(Event $e) 
    {
        $app = App::getInstance();
        if (!$app->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) {
            return; // Exit if commerce is not enabled
        }

        $object = $e->getData('object');
        $mutationFields = &$object->mutationFields;
        $typesByName = &$object->typesByName;
        $typesByClass = &$object->typesByClass;

        if (!isset($mutationFields['addToCart'])) {
            //  cart: Cart
            $mutationFields['addToCart'] = $this->getAddToCartMutationDefinition($typesByName, $typesByClass);
        }

        if (!isset($mutationFields['updateCartItem'])) {
            //  updateCartItem: CartItem
            $mutationFields['updateCartItem'] = $this->getUpdateCartItemMutationDefinition($typesByName, $typesByClass);
        }

        if (!isset($mutationFields['removeFromCart'])) {
            //  removeCartItem: CartItem
            $mutationFields['removeFromCart'] = $this->getRemoveFromCartMutationDefinition($typesByName, $typesByClass);
        }

        if (!isset($mutationFields['applyCartDiscount'])) {
            //  cartDiscount: CartDiscount
            $mutationFields['applyCartDiscount'] = $this->getApplyCartDiscountMutationDefinition($typesByName, $typesByClass);
        }

        if (!isset($mutationFields['removeCartDiscount'])) {
            //  cartDiscount: CartDiscount
            $mutationFields['removeCartDiscount'] = $this->getRemoveCartDiscountMutationDefinition($typesByName, $typesByClass);
        }
    }

    protected function getCartQueryDefinition(array &$typesByName, array &$typesByClass): array
    {
        $app = App::getInstance();
        return [
            'type' => $typesByName['Cart'],
            'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                if (!$app->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) {
                    return null;
                }

                $currentUser = $app->getAuth()->getCurrentUser();
                if (!$currentUser || !$currentUser->getId()) {
                    return null; // or throw an exception if needed
                }

                $currentWesite = $app->getSiteData()->getCurrentWebsite();

                return $this->getCartReturnArray($this->getCart($currentUser, $currentWesite));
            }
        ];
    }

    protected function getRemoveCartDiscountMutationDefinition(array &$typesByName, array &$typesByClass): array
    {
        $app = App::getInstance();
        return [
            'type' => $typesByName['Cart'],
            'args' => [
                'cartDiscountId' => ['type' => Type::nonNull(Type::int())],
            ],
            'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                $currentUser = $app->getAuth()->getCurrentUser();
                $currentWesite = $app->getSiteData()->getCurrentWebsite();
                if (!$currentUser || !$currentUser->getId()) {
                    return null; // or throw an exception if needed
                }

                $cart = $this->getCart($currentUser, $currentWesite);
                $cartDiscountId = $args['cartDiscountId'] ?? null;

                $cartDiscount = CartDiscount::load($cartDiscountId);

                if (!$cartDiscount) {
                    throw new RuntimeException($app->getUtils()->translate('Cart discount not found.'));
                }
            
                if ($cartDiscount->getCartId() !== $cart->getId()) {
                    throw new RuntimeException($app->getUtils()->translate('Cart discount does not belong to this cart.'));
                }

                $cartDiscount->delete();

                $cart->fullLoad()->calculate()->persist();

                return $this->getCartReturnArray($cart);
            }
        ];
    }

    protected function getRemoveFromCartMutationDefinition(array &$typesByName, array &$typesByClass): array
    {
        $app = App::getInstance();
        return [
            'type' => $typesByName['Cart'],
            'args' => [
                'cartItemId' => ['type' => Type::nonNull(Type::int())],
            ],
            'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                $currentUser = $app->getAuth()->getCurrentUser();
                $currentWesite = $app->getSiteData()->getCurrentWebsite();
                if (!$currentUser || !$currentUser->getId()) {
                    return null; // or throw an exception if needed
                }

                $cart = $this->getCart($currentUser, $currentWesite);
                $cartItemId = $args['cartItemId'] ?? null;

                $cart->fullLoad();

                $cartItem = $cart->getCartItem($cartItemId);
                if (!$cartItem) {
                    throw new RuntimeException($app->getUtils()->translate('Cart item not found.'));
                }

                $cart->removeItem($cartItem);

                $cart->fullLoad()->calculate()->persist();

                return $this->getCartReturnArray($cart);
            }
        ];
    }

    protected function getApplyCartDiscountMutationDefinition(array &$typesByName, array &$typesByClass): array
    {
        $app = App::getInstance();
        return [
            'type' => $typesByName['Cart'],
            'args' => [
                'code' => ['type' => Type::nonNull(Type::string())],
            ],
            'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                $currentUser = $app->getAuth()->getCurrentUser();
                $currentWesite = $app->getSiteData()->getCurrentWebsite();
                if (!$currentUser || !$currentUser->getId()) {
                    return null; // or throw an exception if needed
                }

                $cart = $this->getCart($currentUser, $currentWesite);
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

                $cart->fullLoad()->calculate()->persist();

                return $this->getCartReturnArray($cart);
            }
        ];
    }


    protected function getAddToCartMutationDefinition(array &$typesByName, array &$typesByClass): array
    {
        $app = App::getInstance();
        return [
            'type' => $typesByName['Cart'],
            'args' => [
                'productClass' => ['type' => Type::nonNull(Type::string())],
                'productId' => ['type' => Type::nonNull(Type::int())],
                'quantity' => ['type' => Type::nonNull(Type::int())],
            ],
            'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                $currentUser = $app->getAuth()->getCurrentUser();
                $currentWesite = $app->getSiteData()->getCurrentWebsite();
                if (!$currentUser || !$currentUser->getId()) {
                    return null; // or throw an exception if needed
                }

                $cart = $this->getCart($currentUser, $currentWesite);
                $productClass = $args['productClass'] ?? null;

                // if product class is not a full namespaced class, try to resolve it
                if (!class_exists($productClass)) {
                    $classes = array_filter(array_merge(
                        ClassFinder::getClassesInNamespace(App::BASE_MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
                        ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE)
                    ), fn ($class) => is_subclass_of($class, ProductInterface::class));
                    $classes = array_combine(array_map(fn ($c) => App::getInstance()->getClassBasename($c), $classes), $classes);

                    if (isset($classes[$productClass])) {
                        $productClass = $classes[$productClass];
                    } else {
                        throw new RuntimeException($app->getUtils()->translate('Invalid product class.'));
                    }
                }

                $productId = $args['productId'] ?? null;
                $quantity = $args['quantity'] ?? 1;

                $cart->fullLoad();

                $product = $app->containerCall([$productClass, 'load'], [$productId]);
                $cart->addProduct($product, $quantity);

                $cart->calculate()->persist();

                return $this->getCartReturnArray($cart);
            }
        ];
    }

    protected function getUpdateCartItemMutationDefinition(array &$typesByName, array &$typesByClass): array
    {
        $app = App::getInstance();
        return [
            'type' => $typesByName['Cart'],
            'args' => [
                'cartItemId' => ['type' => Type::nonNull(Type::int())],
                'quantity' => ['type' => Type::nonNull(Type::int())],
            ],
            'resolve' => function ($rootValue, $args, $context, ResolveInfo $info) use ($app) {
                $currentUser = $app->getAuth()->getCurrentUser();
                $currentWesite = $app->getSiteData()->getCurrentWebsite();
                if (!$currentUser || !$currentUser->getId()) {
                    return null; // or throw an exception if needed
                }

                $cart = $this->getCart($currentUser, $currentWesite);
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

                return $this->getCartReturnArray($cart);
            }
        ];
    }

    protected function getCartReturnArray(CartModel $cart): array
    {
        $cart->fullLoad();
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

    protected function getCart(User $user, Website $website) : ?CartModel
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