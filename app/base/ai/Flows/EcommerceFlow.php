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

use App\Base\AI\Actions\GraphQLSchemaProvider;

class EcommerceFlow extends BaseFlow
{
    protected string $schema;

    public function __construct(GraphQLSchemaProvider $schemaProvider)
    {
        $this->schema = $schemaProvider->getSchemaFilteredByTypes([
            'Cart',
            'CartItem',
            'CartDiscount',
            'ProductInterface',
            'PhysicalProductInterface',
            'GiftCard',
            'Book',
            'DownloadableProduct',
            'ProductStock',
        ]);
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
