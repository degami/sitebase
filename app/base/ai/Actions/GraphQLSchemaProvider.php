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

namespace App\Base\AI\Actions;

class GraphQLSchemaProvider
{
    const INTROSPECTION_QUERY = '
query IntrospectionQuery {
  __schema {
    types {
      kind
      name
      description
      fields {
        name
        description
        args {
          name
          type {
            kind
            name
            ofType {
              kind
              name
            }
          }
        }
        type {
          kind
          name
          ofType {
            kind
            name
          }
        }
      }
      inputFields {
        name
        type {
          kind
          name
          ofType {
            kind
            name
          }
        }
      }
      enumValues {
        name
        description
      }
    }
    queryType { name }
    mutationType { name }
    subscriptionType { name }
  }
}
';
    protected GraphQLExecutor $executor;

    public function __construct(GraphQLExecutor $executor)
    {
        $this->executor = $executor;
    }

    public function getFullSchema(): string
    {
        $result = $this->executor->execute(self::INTROSPECTION_QUERY);

        if (!isset($result['data']['__schema'])) {
            return '';
        }

        return "```json\n" . json_encode($result['data'], JSON_PRETTY_PRINT) . "\n```";
    }

    public function getReducedSchema(): string
    {
        $result = $this->executor->execute(self::INTROSPECTION_QUERY);

        if (!isset($result['data']['__schema'])) {
            return '';
        }

        $schema = $result['data']['__schema'];

        // 1. filtra solo tipi utili
        $types = array_filter($schema['types'], function ($type) {
            return !str_starts_with($type['name'], "__"); // skip system types
        });

        // 2. ricomponi un testo leggibile
        $schemaText = "";

        foreach ($types as $type) {
            $schemaText .= "type {$type['name']} {\n";
            if (!empty($type['fields'])) {
                foreach ($type['fields'] as $field) {
                    $schemaText .= "  {$field['name']} ";
                    if (isset($field['type']['name'])) {
                        $schemaText .= ": {$field['type']['name']}";
                    }
                    $schemaText .= "\n";
                }
            }
            $schemaText .= "}\n\n";
        }

        return $schemaText;
    }
}
