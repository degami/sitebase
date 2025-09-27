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

namespace App\Base\GraphQl;

use App\App;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Routing\RouteInfo;
use App\Base\Models\RequestLog;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Type\Definition\ResolveInfo;
use GraphQL\Type\SchemaConfig;
use GraphQL\Type\Definition\InterfaceType;
use GraphQL\Type\Definition\InputObjectType;
use GraphQL\Type\Definition\EnumType;
use GraphQL\Type\Definition\ObjectType;
use GraphQL\Type\Definition\Type;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Response;
use HaydenPierce\ClassFinder\ClassFinder;
use ReflectionClass;
use ReflectionMethod;
use ReflectionUnionType;

class Entrypoint extends BasePage
{
    protected array $typesByName = [];
    protected array $typesByClass = [];
    protected array $queryFields = [];
    protected array $mutationFields = [];

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response
    {
        if ($this->getRouteInfo()->getVar('lang') != null) {
            $this->getApp()->setCurrentLocale($this->getRouteInfo()->getVar('lang'));
        }

        $contents = '';
        $schemaDir = App::getDir(APP::GRAPHQL);
        $files = glob($schemaDir . DS . '*.graphql');
        foreach($files as $filePath) {
            if (!$this->getEnvironment()->getVariable('ENABLE_COMMERCE') && basename($filePath) == 'commerce.graphql') {
                continue; // skip commerce schema if not enabled
            }

            $contents .= file_get_contents($filePath);
        }

        if (!empty($contents)) {
            // schema is read from file(s)
            /** @var Schema $schema */
            $schema = BuildSchema::build($contents, function ($typeConfig, $typeDefinitionNode) {
                if ($typeConfig['name'] === 'ProductInterface') {
                    $typeConfig['resolveType'] = function ($value) {
                        // Recupera il nome classe dal valore
                        $className = null;
                        if (is_array($value)) {
                            $className = $value['class'] ?? null;
                        } elseif ($value instanceof ProductInterface) {
                            $className = get_class($value);
                        }

                        if ($className) {
                            return $this->getUtils()->getClassBasename($className);
                        }

                        throw new \Exception('Unknown product type: ' . print_r($value, true));
                    };
                }
                return $typeConfig;
            });
        } else {
            // schema can be built automatically
            $schema = $this->buildGraphQLSchema();
        }

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        if (!is_array($input)) {
            throw new \App\Base\Exceptions\InvalidValueException("Missing request body");
        }

        $query = $input['query'];
        $variableValues = $input['variables'] ?? null;
        $operationName = $input['operationName'] ?? null;
        $rootValue = null;
        $context = null;

        $fieldResolver = function(...$args) {
            return call_user_func_array([$this, 'defaultFieldResolver'], func_get_args());
        };

        $result = GraphQL::executeQuery(
            $schema,
            $query,        // this was grabbed from the HTTP post data
            $rootValue,
            $context,   // custom context
            $variableValues,    // this was grabbed from the HTTP post data
            $operationName,
            $fieldResolver // HERE, custom field resolver
        );

        $output = $result->toArray();

        if ($this->getSiteData()->getConfigValue('app/frontend/log_requests') == true) {
            if (!isset($route_data['_noLog'])) {
                try {
                    /** @var RequestLog $log */
                    $log = $this->containerMake(RequestLog::class);
                    $log->fillWithRequest($this->getRequest(), $this);
                    $log->setUrl(substr($log->getUrl() . ' ' . $rawInput, 0, 1024));
                    $log->setResponseCode(200);
                    $log->persist();
                } catch (Exception $e) {
                    $this->getUtils()->logException($e, "Can't write RequestLog", $this->getRequest());
                    if ($this->getEnvironment()->getVariable('DEBUG')) {
                        return $this->getUtils()->exceptionPage($e, $this->getRouteInfo());
                    }
                }
            }
        }

        return $this->containerMake(JsonResponse::class)->setData($output);
    }

    private function defaultFieldResolver(mixed $source, array $args, mixed $context, ResolveInfo $info) : mixed 
    {
        $fieldName = $info->fieldName;
        $mandatory = str_ends_with($info->returnType->toString(), '!');
        $returnType = rtrim($info->returnType->toString(), '!');

        if ($source instanceof BaseModel) {
            if (method_exists($source, $fieldName)) {
                return $this->containerCall([$source, $fieldName]);
            }

            if (preg_match_all("/(.*?)(\_\_.+)+/", $fieldName, $matches, PREG_PATTERN_ORDER) && method_exists($source, 'get'.ucfirst($this->getUtils()->snakeCaseToPascalCase(reset($matches[1]))))) {
                $method = 'get'.ucfirst($this->getUtils()->snakeCaseToPascalCase(reset($matches[1])));
                return $this->containerCall([$source, $method], array_map(fn ($el) => ltrim($el, "_"), $matches[2]));
            }

            if (($foundMethod = $this->classHasMethodReturningType($source, $returnType)) !== false) {
                return $this->containerCall([$source, $foundMethod]);
            }

            if (preg_match("/^\[(.*?)\]$/", $returnType, $matches)) {
                if (($foundMethod = $this->classHasPropertyNameMethod($source, $fieldName)) !== false) {
                    return $this->containerCall([$source, $foundMethod]);
                }
            }

            if (method_exists($source, "get".ucfirst($this->getUtils()->snakeCaseToPascalCase($fieldName)))) {
                return $this->containerCall([$source, "get".ucfirst($this->getUtils()->snakeCaseToPascalCase($fieldName))]);
            }

            return $source->getData($fieldName);
        }

        if (class_exists("App\\Site\\GraphQL\\Resolvers\\".ucfirst($fieldName)) && is_callable(["App\\Site\\GraphQL\\Resolvers\\".ucfirst($fieldName), 'resolve'])) {
            return $this->containerCall(["App\\Site\\GraphQL\\Resolvers\\".ucfirst($fieldName), 'resolve'], ['source' => $this->inferSourceValue($source, $fieldName), 'args' => $args + ['locale' => $this->getApp()->getCurrentLocale()]]);
        }

        if ( (is_object($source) && property_exists($source, $fieldName)) || (is_array($source) && array_key_exists($fieldName, $source))) {
            return $this->inferSourceValue($source, $fieldName);
        }

        if (preg_match("/^\[(.*?)\]$/", $returnType, $matches) || preg_match("/(.*?)Collection$/", $returnType, $matches)) {
            if (class_exists("\\App\\Base\\Models\\".$matches[1]) || class_exists("\\App\\Site\\Models\\".$matches[1])) {
                /** @var BaseCollection $collection */
                if (class_exists("\\App\\Base\\Models\\".$matches[1])) {
                    $modelClass = "\\App\\Base\\Models\\".$matches[1];
                } else {
                    $modelClass = "\\App\\Site\\Models\\".$matches[1];
                }

                $collection = $this->containerCall([$modelClass, "getCollection"]);
                if (isset($args['input'])) {
                    $searchCriteriaInput = $args['input'];

                    if (isset($searchCriteriaInput['criteria'])) {
                        $collection->addCondition(
                            array_combine(
                                array_column($searchCriteriaInput['criteria'], 'key'), 
                                array_column($searchCriteriaInput['criteria'], 'value')
                            )
                        );
                    }

                    if (isset($searchCriteriaInput['limit'])) {
                        $pageSize = $searchCriteriaInput['limit'];
                        $startOffset = 0;
                        if (isset($searchCriteriaInput['offset'])) {
                            $startOffset = $searchCriteriaInput['offset'];
                        }
                        $collection->limit($pageSize, $startOffset);
                    }

                    if (isset($searchCriteriaInput['orderBy'])) {
                        $collection->addOrder(array_combine(
                            array_column($searchCriteriaInput['orderBy'], 'field'), 
                            array_column($searchCriteriaInput['orderBy'], 'direction')
                        ));
                    }
                }

                if (preg_match("/Collection$/", $returnType)) {
                    return [
                        'items' => $collection->getItems(),
                        'count' => $collection->count(),
                    ];
                }
                return $collection->getItems();
            }
        }

        if ($mandatory) {
            throw new Exception("Field \"{$fieldName}\" not found!");
        }
        
        return null;
    }

    private function inferSourceValue(mixed $source, string $fieldName) : mixed
    {
        if (is_object($source) && method_exists($source, $fieldName)) {
            return $this->containerCall([$source, $fieldName]);
        }

        if (preg_match_all("/(.*?)(\_\_.+)+/", $fieldName, $matches, PREG_PATTERN_ORDER) && method_exists($source, 'get'.ucfirst($this->getUtils()->snakeCaseToPascalCase(reset($matches[1]))))) {
            $method = 'get'.ucfirst($this->getUtils()->snakeCaseToPascalCase(reset($matches[1])));
            return call_user_func_array([$source, $method], array_map(fn ($el) => ltrim($el, "_"), $matches[2]));
            //return $this->containerCall([$source, $method], array_map(fn ($el) => ltrim($el, "_"), $matches[2]));
        }

        $callable = [$source, "get".ucfirst($this->getUtils()->snakeCaseToPascalCase($fieldName))];
        if (is_object($source) && is_callable($callable)) {
            return call_user_func($callable);
            //return $this->containerCall($callable);
        }

        if ((is_object($source) || is_string($source)) && ($foundMethod = $this->classHasPropertyNameMethod($source, $fieldName)) !== false) {
            return $this->containerCall([$source, $foundMethod]);
        }

        if (is_object($source) && property_exists($source, $fieldName)) {
            return $source->$fieldName;
        }

        if (is_array($source) && array_key_exists($fieldName, $source)) {
            return $source[$fieldName];
        }

        return null;
    }

    private function classHasMethodReturningType($class, $returnType) : string|bool
    {
        if (class_exists("App\\Site\\Models\\".$returnType) || class_exists("App\\Base\\Models\\".$returnType)) {
            $returnType = class_exists("App\\Base\\Models\\".$returnType) ? "App\\Base\\Models\\".$returnType : "App\\Site\\Models\\".$returnType;
            try {
                $reflection = new \ReflectionClass($class);
                foreach ($reflection->getMethods() as $classMethod) {
                    if (str_starts_with($classMethod->getName(), 'get')) {
                        if ($classMethod->getReturnType() instanceof ReflectionNamedType) {
                            if ($classMethod->getReturnType()?->getName() == $returnType) {
                                return $classMethod->getName();
                            }
                        }
                    }
                }
            } catch (Exception $e) {}    
        }

        return false;
    }

    private function classHasPropertyNameMethod($class, $propertyName) : string|bool
    {
        try {
            $reflection = new \ReflectionClass($class);
            foreach ($reflection->getProperties() as $classProperty) {
                if ($classProperty->getName() == $propertyName) {
                    if (method_exists($class, 'get'.ucfirst($propertyName))) {
                        return 'get'.ucfirst($propertyName);
                    }
                }
            }
        } catch (Exception $e) {}    

        return false;
    }

    protected function maskedMethods(): array
    {
        return [
            'getData', 'getRealInstance', 'getChangedData', 'getTableColumns', 'getKeyField',
            'getTableName', 'getDbRow', 'getClassBasename', 'getInstance', 
            'getPassword', 'getJWT', 'getExportHeader', 'getExportRowData',
        ];
    }

    protected function generateGraphQLFieldsFromModel(string $modelClass, ?ObjectType &$objectType = null): array
    {
        $reflection = new \ReflectionClass($modelClass);
        $docComment = $reflection->getDocComment();
        $fields = [];

        if ($docComment) {
            preg_match_all('/@method\s+([^\s]+)\s+(get.*?)\(\)/', $docComment, $matches, PREG_SET_ORDER);

            foreach ($matches as $match) {
                $returnType = $match[1];
                $getterName = $match[2];

                // getters with __ are considered as special getters with parameters
                if (preg_match_all("/(.*?)(\_\_.+)+/", $getterName, $subMatches, PREG_PATTERN_ORDER)) {
                    $fieldName = $this->getUtils()->pascalCaseToSnakeCase(substr(reset($subMatches[1]), 3)) . implode("", $subMatches[2]);
                } else {
                    $fieldName  = $this->getUtils()->pascalCaseToSnakeCase(substr($getterName, 3));
                }
        
                if (in_array($getterName, $this->maskedMethods())) {
                    continue; // skip masked getters
                }

                $graphqlType = $this->phpDocTypeToGraphQL($returnType, $objectType);

                $fields[$fieldName] = [
                    'type'    => $graphqlType,
                    'resolve' => fn($root) => $this->inferSourceValue($root, $fieldName),
                ];
            }
        }

        foreach ($reflection->getMethods(\ReflectionMethod::IS_PUBLIC) as $methodRef) {
            $methodName = $methodRef->getName();
            $docComment = $methodRef->getDocComment();

            if (!preg_match('/^get([A-Z]\w*)$/', $methodName, $m)) {
                continue; // only consider getters
            }

            if (in_array($methodName, $this->maskedMethods())) {
                continue; // skip masked getters
            }

            if (!$this->isGrahqlExportable($methodRef)) {
                continue; // skip non-exportable methods
            }

            $propName  = $m[1];
            $fieldName = $this->getUtils()->pascalCaseToSnakeCase($propName);

            $returnType = $methodRef->getReturnType();
            if ($returnType instanceof \ReflectionUnionType) {
                $returnType = $returnType->getTypes()[0] ?? null;
            }

            $phpType = $returnType instanceof \ReflectionNamedType
                ? $returnType->getName()
                : 'string';


            // only base types or models types should be added
            if (!in_array($phpType, ['int', 'string', 'bool', 'float', 'double', 'array']) && 
                !str_starts_with($phpType, App::MODELS_NAMESPACE) &&
                !str_starts_with($phpType, App::BASE_MODELS_NAMESPACE)
            ) {
                // continue;
            }

            if (!isset($fields[$fieldName])) {
                if ($docComment !== false) {
                    if ($phpType == 'array' && preg_match('/@return\s+([\\\\\w]+)\[\]/', $docComment, $matches)) {
                        $phpType = $matches[1].'[]';
                    }
                }

                // set to null to avoid duplicate fields in case types referring to same taype
                $fields[$fieldName] = null;

                $fields[$fieldName] = [
                    'type'    => $this->phpDocTypeToGraphQL($phpType),
                    'resolve' => fn($root) => $this->inferSourceValue($root, $fieldName),
                ];
            }
        }

        return $fields;
    }

    protected function phpDocTypeToGraphQL(string $phpDocType, ?ObjectType &$objectType = null): Type
    {
        $phpDocType = trim($phpDocType);
        $lowerType  = strtolower($phpDocType);

        // --- scalari ---
        switch ($lowerType) {
            case 'int': case 'integer': return Type::int();
            case 'string': return Type::string();
            case 'bool': case 'boolean': return Type::boolean();
            case 'float': case 'double': return Type::float();
            case 'array': return Type::listOf(Type::string());
            case 'id': return Type::id();
        }

        // --- array ---
        if (str_ends_with($phpDocType, '[]')) {
            $inner = substr($phpDocType, 0, -2);
            return Type::listOf($this->phpDocTypeToGraphQL($inner, $objectType));
        }

        // --- classi modello ---
        if (class_exists($phpDocType)) {
            $shortName = (new \ReflectionClass($phpDocType))->getShortName();

            if ($shortName == 'DateTime' || $shortName == 'DateTimeImmutable') {
                return Type::string(); // DateTime is represented as string in GraphQL
            }

            if ($objectType !== null && $objectType->name == $shortName) {
                return $objectType; // avoid recursion
            }

            // se già esiste, riutilizza
            if (isset($this->typesByName[$shortName])) {
                return $this->typesByName[$shortName];
            }

            // placeholder (per cicli)
            $this->typesByName[$shortName] = null;

            // crea il tipo
            $objectType = new ObjectType([
                'name'   => $shortName,
                'fields' => function() use ($phpDocType, &$objectType) {
                    return $this->generateGraphQLFieldsFromModel($phpDocType, $objectType);
                },
            ]);

            $this->typesByName[$shortName]   = $objectType;
            $this->typesByClass[$phpDocType] = $objectType;

            return $objectType;
        }

        // fallback
        return Type::string();
    }

    protected function buildInterfaceType(ReflectionClass $reflection): InterfaceType
    {
        if (!$reflection->isInterface()) {
            throw new \InvalidArgumentException("Class " . $reflection->getName() . " is not an interface.");
        }

        $shortName = $reflection->getShortName();

        if (isset($this->typesByName[$shortName])) {
            return $this->typesByName[$shortName];
        }

        $fields = [];

        foreach ($reflection->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            if (str_starts_with($method->getName(), 'get')) {
                $fieldName = $this->getUtils()->pascalCaseToSnakeCase(substr($method->getName(), 3));
                $returnType = $method->getReturnType();
                if ($returnType instanceof ReflectionUnionType) {
                    $returnType = $returnType->getTypes()[0] ?? null;
                }
                $fields[$fieldName] = [
                    'type' => $this->phpDocTypeToGraphQL($returnType ? $returnType->getName() : 'string'),
                ];
            }
        }

        $interfaceType = new InterfaceType([
            'name' => $shortName,
            'fields' => $fields,
            'resolveType' => function ($value) {
                $className = is_object($value) ? get_class($value) : ($value['class'] ?? null);
                return $className && isset($this->typesByClass[$className])
                    ? $this->typesByClass[$className]
                    : null;
            },
        ]);

        $this->typesByName[$shortName] = $interfaceType;
        $this->typesByClass[$reflection->getName()] = $interfaceType;
        return $interfaceType;
    }

    protected function buildBaseTypes(): void
    {
        if (!isset($this->typesByName['OrderDirection'])) {
            $this->typesByName['OrderDirection'] = new EnumType([
                'name' => 'OrderDirection',
                'values' => [
                    'ASC'  => ['value' => 'ASC'],
                    'DESC' => ['value' => 'DESC'],
                ]
            ]);
        }

        if (!isset($this->typesByName['OrderByInput'])) {
            $this->typesByName['OrderByInput'] = new InputObjectType([
                'name' => 'OrderByInput',
                'fields' => [
                    'field' => [
                        'type' => Type::nonNull(Type::string()),
                    ],
                    'direction' => [
                        'type' => Type::nonNull($this->typesByName['OrderDirection']),
                    ],
                ]
            ]);
        }

        if (!isset($this->typesByName['SearchCriterionInput'])) {
            $this->typesByName['SearchCriterionInput'] = new InputObjectType([
                'name' => 'SearchCriterionInput',
                'fields' => [
                    'key' => ['type' => Type::nonNull(Type::string())],
                    'value' => ['type' => Type::nonNull(Type::string())],
                ]
            ]);
        }

        if (!isset($this->typesByName['SearchCriteriaInput'])) {
            $this->typesByName['SearchCriteriaInput'] = new InputObjectType([
                'name' => 'SearchCriteriaInput',
                'fields' => [
                    'criteria' => ['type' => Type::listOf($this->typesByName['SearchCriterionInput'])],
                    'limit'    => ['type' => Type::int()],
                    'offset'   => ['type' => Type::int()],
                    'orderBy'  => ['type' => Type::listOf($this->typesByName['OrderByInput'])],
                ]
            ]);
        }

        if (!isset($typesByName['SubmitActionResponse'])) {
            $this->typesByName['SubmitActionResponse'] = new ObjectType([
                'name' => 'SubmitActionResponse',
                'fields' => function() {
                    return [
                        'success' => Type::nonNull(Type::boolean()),
                        'message' => Type::nonNull(Type::string()),
                    ];
                },
            ]);
        }
    }

    protected function buildGraphQLSchema(): Schema
    {
        $modelClasses = array_filter(array_merge(
            ClassFinder::getClassesInNamespace('App\\Base\\Models', ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace('App\\Site\\Models', ClassFinder::RECURSIVE_MODE)
        ), function ($class) {
            return is_subclass_of($class, BaseModel::class) && !is_subclass_of($class, BaseCollection::class);
        });

        // base types
        $this->buildBaseTypes();

        // --- tipi per i modelli ---
        foreach ($modelClasses as $modelClass) {
            $this->registerModelClass($modelClass, true);
        }

        // complete types , queries and mutations by event hooks        
        App::getInstance()->event('register_graphql_query_fields', ['object' => (object) [
            'queryFields' => &$this->queryFields,
            'typesByName' => &$this->typesByName,
            'typesByClass' => &$this->typesByClass,
            'entrypoint' => $this,
        ]]);

        App::getInstance()->event('register_graphql_mutation_fields', ['object' => (object) [
            'mutationFields' => &$this->mutationFields,
            'typesByName' => &$this->typesByName,
            'typesByClass' => &$this->typesByClass,
            'entrypoint' => $this,
        ]]);

        // --- root Query & Mutation ---
        $queryType = new ObjectType([
            'name' => 'Query',
            'fields' => $this->queryFields,
        ]);

        $mutationType = new ObjectType([
            'name' => 'Mutation',
            'fields' => $this->mutationFields,
        ]);

        $config = SchemaConfig::create()
            ->setTypes($this->typesByName)
            ->setQuery($queryType)
            ->setMutation($mutationType)
            ->setTypeLoader(fn(string $name) => $this->typesByName[$name] ?? null);

        return new Schema($config);
    }

    protected function isGrahqlExportable(ReflectionClass|ReflectionMethod $ref) : bool
    {
        $attrs = $ref->getAttributes(GraphQLExport::class);
        if ($ref instanceOf ReflectionClass) {
            $attrs = array_merge($attrs, $this->getAllAttributes($ref, GraphQLExport::class));
        }
        if (!empty($attrs)) {
            return true;
        }

        return false;
    }

    protected function getAllAttributes(ReflectionClass $refClass, string $attributeName): array {
        $attrs = [];

        do {
            $attrs = array_merge($attrs, $refClass->getAttributes($attributeName));
            $refClass = $refClass->getParentClass();
        } while ($refClass);

        return $attrs;
    }

    protected function pluralize(string $word): string 
    {
        $lower = strtolower($word);

        // Words ending in y preceded by a consonant → replace y with ies
        if (preg_match('/([^aeiou])y$/i', $word)) {
            return preg_replace('/y$/i', 'ies', $word);
        }

        // Common irregulars (optional, you can expand)
        $irregulars = [
            'person' => 'people',
            'man' => 'men',
            'woman' => 'women',
            'child' => 'children',
            'mouse' => 'mice',
            'goose' => 'geese',
        ];

        if (isset($irregulars[$lower])) {
            // preserve original casing
            $firstUpper = ctype_upper($word[0]);
            $plural = $irregulars[$lower];
            return $firstUpper ? ucfirst($plural) : $plural;
        }

        // Default: just add 's'
        return $word . (!str_ends_with($word, 's') ? 's' : '');
    }

    public function getTypesByName(): array
    {
        return $this->typesByName;
    }

    public function getTypesByClass(): array
    {
        return $this->typesByClass;
    }

    public function registerModelClass(string $modelClass, bool $withCollection = true, bool $forceRegistration = false) : void
    {
        $reflection = new ReflectionClass($modelClass);
        if (!$this->isGrahqlExportable($reflection) && !$forceRegistration) {
            return; // skip non-exportable models
        }

        $typeName = $reflection->getShortName();

        // riuso se già creato
        if (isset($this->typesByName[$typeName])) {
            return;
        }

        // placeholder
        $this->typesByName[$typeName] = null;

        // build interfaces types if needed
        $interfaces = [];
        foreach ($reflection->getInterfaces() as $interface) {
            $this->buildInterfaceType($interface);
            if (isset($this->typesByName[$interface->getShortName()])) {
                $interfaces[] = $this->typesByName[$interface->getShortName()];
            }
        }

        // create class type
        $objectType = new ObjectType([
            'name'   => $typeName,
            'fields' => function() use ($modelClass, &$objectType) {
                return $this->generateGraphQLFieldsFromModel($modelClass, $objectType);
            },
            'interfaces' => $interfaces,
        ]);

        $this->typesByName[$typeName] = $objectType;
        $this->typesByClass[$modelClass] = $this->typesByName[$typeName];

        // create collection type
        $collName = $typeName . 'Collection';
        if (!isset($this->typesByName[$collName])) {
            $this->typesByName[$collName] = new ObjectType([
                'name' => $collName,
                'fields' => [
                    'items' => ['type' => Type::listOf($this->typesByName[$typeName])],
                    'count' => ['type' => Type::nonNull(Type::int())],
                ]
            ]);
        }

        if (!$withCollection) {
            return;
        }

        // register collections query
        $this->queryFields[strtolower($this->pluralize($typeName))] = [
            'type' => $this->typesByName[$collName],
            'args' => ['input' => ['type' => $this->typesByName['SearchCriteriaInput']]],
            'resolve' => function ($root, $args) use ($modelClass) {

                $collection = $this->containerCall([$modelClass, "getCollection"]);
                if (isset($args['input'])) {
                    $searchCriteriaInput = $args['input'];

                    if (isset($searchCriteriaInput['criteria'])) {
                        $collection->addCondition(
                            array_combine(
                                array_column($searchCriteriaInput['criteria'], 'key'), 
                                array_column($searchCriteriaInput['criteria'], 'value')
                            )
                        );
                    }

                    if (isset($searchCriteriaInput['limit'])) {
                        $pageSize = $searchCriteriaInput['limit'];
                        $startOffset = 0;
                        if (isset($searchCriteriaInput['offset'])) {
                            $startOffset = $searchCriteriaInput['offset'];
                        }
                        $collection->limit($pageSize, $startOffset);
                    }

                    if (isset($searchCriteriaInput['orderBy'])) {
                        $collection->addOrder(array_combine(
                            array_column($searchCriteriaInput['orderBy'], 'field'), 
                            array_column($searchCriteriaInput['orderBy'], 'direction')
                        ));
                    }
                }

                return [
                    'items' => $collection->getItems(),
                    'count' => $collection->count(),
                ];
            }
        ];
    }
}