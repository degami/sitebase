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
use App\Base\Routing\RouteInfo;
use App\Base\Models\RequestLog;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use GraphQL\Type\Definition\ResolveInfo;
use ReflectionNamedType;
use Symfony\Component\HttpFoundation\Response;

class Entrypoint extends BasePage
{
    public function renderPage(?RouteInfo $route_info = null, array $route_data = []) : JsonResponse
    {
        return $this->process($route_info, $route_data);
    }

    /**
     * {@inheritdoc}
     * this is only for compatibility
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
        $contents = file_get_contents(App::getDir(APP::GRAPHQL).DS.'schema.graphql');

        /** @var Schema $schema */
        $schema = BuildSchema::build($contents);

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

        $fieldResolver = function() {
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
                    if ($this->getEnv('DEBUG')) {
                        return $this->getUtils()->exceptionPage($e, $this->getRequest(), $this->getRouteInfo());
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

            if (preg_match_all("/(.*?)(\_.+)+/", $fieldName, $matches, PREG_PATTERN_ORDER) && method_exists($source, reset($matches[1]))) {
                return $this->containerCall([$source, reset($matches[1])], array_map(fn ($el) => ltrim($el, "_"), $matches[2]));
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
            return $this->containerCall(["App\\Site\\GraphQL\\Resolvers\\".ucfirst($fieldName), 'resolve'], ['args' => $args + ['locale' => $this->getApp()->getCurrentLocale()]]);
        }

        if (is_object($source) && property_exists($source, $fieldName)) {
            return $source->$fieldName;
        }

        if (is_array($source) && isset($source[$fieldName])) {
            return $source[$fieldName];
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
}