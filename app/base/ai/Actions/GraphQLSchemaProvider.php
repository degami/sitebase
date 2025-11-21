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

use App\App;
use GraphQL\Utils\BuildClientSchema;
use GraphQL\Utils\SchemaPrinter;
use GraphQL\Type\Schema;
use GraphQL\Type\SchemaConfig;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\UnionType;
use GraphQL\Type\Definition\NonNull;
use GraphQL\Type\Definition\ListOfType;
use GraphQL\Type\Definition\NamedType;

class GraphQLSchemaProvider
{

  const INTROSPECTION_CACHE_KEY = 'graphql.introspection.full_schema';

  const INTROSPECTION_QUERY = '
query IntrospectionQuery {
  __schema {
    types {
      kind
      name
      description
      fields(includeDeprecated: true) {
        name
        description
        args {
          name
          description
          type { ...TypeRef }
          defaultValue
        }
        type { ...TypeRef }
        isDeprecated
        deprecationReason
      }
      inputFields {
        name
        description
        type { ...TypeRef }
        defaultValue
      }
      interfaces {
        kind
        name
        ofType { kind name }
      }
      enumValues(includeDeprecated: true) {
        name
        description
        isDeprecated
        deprecationReason
      }
      possibleTypes {
        kind
        name
      }
    }
    queryType { name }
    mutationType { name }
    subscriptionType { name }
  }
}
  
fragment TypeRef on __Type {
  kind
  name
  ofType {
    kind
    name
    ofType {
      kind
      name
      ofType {
        kind
        name
      }
    }
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
    if (App::getInstance()->getCache()->has(self::INTROSPECTION_CACHE_KEY)) {
      $result = App::getInstance()->getCache()->get(self::INTROSPECTION_CACHE_KEY);
    } else {
      $result = $this->executor->execute(self::INTROSPECTION_QUERY);
      App::getInstance()->getCache()->set(self::INTROSPECTION_CACHE_KEY, $result);
    }
    
    if (!isset($result['data']['__schema'])) {
      return '';
    }
    
    $schema = BuildClientSchema::build($result['data']);
    return SchemaPrinter::doPrint($schema);
  }
  
  /**
  * --------------------------------------------------------------
  * Filter schema by a list of root types
  * --------------------------------------------------------------
  */
  public function getSchemaFilteredByTypes(array $rootTypes): string
  {
    if (App::getInstance()->getCache()->has(self::INTROSPECTION_CACHE_KEY)) {
      $result = App::getInstance()->getCache()->get(self::INTROSPECTION_CACHE_KEY);
    } else {
      $result = $this->executor->execute(self::INTROSPECTION_QUERY);
      App::getInstance()->getCache()->set(self::INTROSPECTION_CACHE_KEY, $result);
    }
    
    if (!isset($result['data']['__schema'])) {
      return '';
    }
    
    $fullSchema = BuildClientSchema::build($result['data']);
    
    // Extract needed types recursively
    $allowedTypes = $this->extractTypes($fullSchema, $rootTypes);
    
    // Build a reduced schema
    $reduced = $this->buildReducedSchema($fullSchema, $allowedTypes);
    
    return SchemaPrinter::doPrint($reduced);
  }
  
  /**
  * Collect all dependent types recursively
  */
  protected function extractTypes(Schema $schema, array $rootTypes): array
  {
      $typeMap = $schema->getTypeMap();
      $collected = [];

      foreach ($rootTypes as $root) {
          if (!isset($typeMap[$root])) continue;

          $this->collectTypeDependencies($typeMap[$root], $collected, $schema);

          // Include <Type>Collection
          $collection = $root . 'Collection';
          if (isset($typeMap[$collection])) {
              $this->collectTypeDependencies($typeMap[$collection], $collected, $schema);
          }
      }

      // 👉 INCLUDE AUTOMATICAMENTE LE COLLECTION USATE DALLE QUERY
      $queryType = $schema->getQueryType();
      if ($queryType) {
          foreach ($queryType->getFields() as $field) {
              $ret = $this->unwrapFinalType($field->getType());
              if (! $ret instanceof \GraphQL\Type\Definition\NamedType) continue;

              $retName = $ret->name;

              foreach ($rootTypes as $root) {
                  if ($retName === $root . 'Collection') {
                      $this->collectTypeDependencies($typeMap[$retName], $collected, $schema);
                  }
              }
          }
      }

      return $collected;
  }

  
  /**
  * Recursive dependency resolver
  */
  protected function collectTypeDependencies(Type $type, array &$collected, Schema $schema): void
  {
    // unwrap LIST / NON_NULL
    $type = $this->unwrapFinalType($type);
    
    $name = $type->name ?? null;
    if (!$name || isset($collected[$name])) {
      return;
    }
    
    $collected[$name] = $type;
    
    //
    // OBJECT / INTERFACE fields
    //
    if ($type instanceof ObjectType || $type instanceof InterfaceType) {
      foreach ($type->getFields() as $field) {
        $this->collectTypeDependencies($field->getType(), $collected, $schema);
        
        foreach ($field->args as $arg) {
          $this->collectTypeDependencies($arg->getType(), $collected, $schema);
        }
      }
    }
    
    //
    // INPUT_OBJECT
    //
    if ($type instanceof InputObjectType) {
      foreach ($type->getFields() as $field) {
        $this->collectTypeDependencies($field->getType(), $collected, $schema);
      }
    }
    
    //
    // INTERFACE → possible types
    //
    if ($type instanceof InterfaceType) {
      foreach ($schema->getPossibleTypes($type) as $impl) {
        $this->collectTypeDependencies($impl, $collected, $schema);
      }
    }
    
    //
    // UNION → its member types
    //
    if ($type instanceof UnionType) {
      foreach ($type->getTypes() as $member) {
        $this->collectTypeDependencies($member, $collected, $schema);
      }
    }
  }
  
  /**
  * Build reduced schema (filtered Query & Mutation)
  */
  protected function buildReducedSchema(Schema $schema, array $allowedTypes): Schema
  {
    $allowedNames = array_keys($allowedTypes);
    $config = SchemaConfig::create();
    
    // ---- Query Root ----
    $queryType = $schema->getQueryType();
    if ($queryType) {
      $queryFields = [];
      
      foreach ($queryType->getFields() as $name => $field) {
        
        $returnType = $this->unwrapFinalType($field->getType());
        
        // Il tipo finale deve essere NamedType
        if ($returnType instanceof NamedType) {
          $returnName = $returnType->name;
          
          // Query inclusa se ritorna un tipo Allowed
          // oppure un tipo <Allowed>Collection
          foreach ($allowedNames as $base) {
            if ($returnName === $base || $returnName === $base . 'Collection') {
              $queryFields[$name] = $field;
            }
          }
        }
        
        if ($queryFields) {
          $config->setQuery(new ObjectType([
            'name'   => 'Query',
            'fields' => $queryFields,
          ]));
        }
      }
      
      // ---- Mutation Root ----
      $mutationType = $schema->getMutationType();
      if ($mutationType) {
        $mutationFields = [];
        
        foreach ($mutationType->getFields() as $name => $field) {
          
          $usesAllowedType = false;
          
          // Return type
          $returnType = $this->unwrapFinalType($field->getType());
          
          if ($returnType instanceof NamedType) {
            $returnName = $returnType->name;
            
            foreach ($allowedNames as $base) {
              if ($returnName === $base || $returnName === $base . 'Collection') {
                $usesAllowedType = true;
              }
            }
          }
          
          // Args
          foreach ($field->args as $arg) {
            $argFinal = $this->unwrapFinalType($arg->getType());
            if ($argFinal instanceof NamedType) {
              $argName = $argFinal->name;
              
              foreach ($allowedNames as $base) {
                if ($argName === $base || $argName === $base . 'Collection') {
                  $usesAllowedType = true;
                }
              }
            }
          }
          
          if ($usesAllowedType) {
            $mutationFields[$name] = $field;
          }
        }
        
        if ($mutationFields) {
          $config->setMutation(new ObjectType([
            'name'   => 'Mutation',
            'fields' => $mutationFields,
          ]));
        }
      }
    }
    
    // ---- Final allowed types ----
    $config->setTypes(array_values($allowedTypes));
    
    return new Schema($config);
  }
  
  /**
  * Helper to unwrap LIST / NON_NULL
  */
  protected function unwrapFinalType(Type $type): Type
  {
    while ($type instanceof NonNull || $type instanceof ListOfType) {
      
      if ($type instanceof NonNull) {
        // STATICO, NON di istanza!
        $type = Type::getNullableType($type);
      }
      
      if ($type instanceof ListOfType) {
        $type = $type->getWrappedType();
      }
    }
    
    return $type;
  }
}

