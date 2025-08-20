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

class CommerceEventListener implements EventListenerInterface
{
	public function getEventHandlers() : array
	{
		// Return an array of event handlers as required by the interface
		return [
            'register_graphql_query_fields' => [$this, 'RegisterGraphQLQueryFields']
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
}