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

namespace App\Base\AI\Flows;

use App\App;
use App\Base\AI\Actions\GraphQLSchemaProvider;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Interfaces\Model\ProductInterface;

class EcommerceFlow extends BaseFlow
{
    protected string $schema;

    public function __construct(GraphQLSchemaProvider $schemaProvider)
    {
        // Get all models related to ecommerce
        // Base Types
        $types = [
            'Cart',
            'CartItem',
            'CartDiscount',
            'ProductInterface',
            'PhysicalProductInterface',
            'ProductStock',
        ];

        // Product Types
        $types = array_merge($types, array_map(function($el) {
            return App::getInstance()->getClassBasename($el);
        }, array_filter(
            array_merge(
                ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), 
                ClassFinder::getClassesInNamespace(App::BASE_MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE)
            ), 
            fn ($className) => is_subclass_of($className, ProductInterface::class)
        )));

        $this->schema = $schemaProvider->getSchemaFilteredByTypes($types);
    }


    public function schema(): string
    {
        return $this->schema;
    }    

    public function systemPrompt(): string
    {
        return <<<TXT
Sei un agente che usa esclusivamente i tools per consultare un backend GraphQL.
Se devi interrogare il backend, devi chiamare la funzione `graphqlQuery`. 
Rispondi solo a domande relative a processi di ecommerce, altrimenti rispondi che non puoi aiutare, dato che sei un assistente per ecommerce.
TXT;
    }

    public function tools(): array
    {
        return [
            'graphqlQuery' => [
                'description' => 'Esegue una query o mutation GraphQL',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'query' => ['type' => 'string'],
                        'variables' => ['type' => 'object']
                    ],
                    'required' => ['query']
                ]
            ]
        ];
    }
}
