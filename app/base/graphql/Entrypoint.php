<?php

namespace App\Base\GraphQl;

use App\App;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Abstracts\Models\BaseModel;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use ReflectionNamedType;
use App\Base\Abstracts\Models\BaseCollection;
use App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Response;

class Entrypoint extends BasePage
{
    public function renderPage(RouteInfo $route_info = null, $route_data = []) : JsonResponse
    {
        if ($this->getRouteInfo()->getVar('lang') != null) {
            $this->getApp()->setCurrentLocale($this->getRouteInfo()->getVar('lang'));
        }
        $contents = file_get_contents(App::getDir(APP::GRAPHQL).DS.'schema.graphql');

        /** @var Schema $schema */
        $schema = BuildSchema::build($contents);

        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true);
        $query = $input['query'];
        $variableValues = isset($input['variables']) ? $input['variables'] : null;
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
            null,
            $fieldResolver // HERE, custom field resolver
        );

        $output = $result->toArray();

        return $this->containerMake(JsonResponse::class)->setData($output);
    }

    private function defaultFieldResolver(
        $source,
        $args,
        $context,
        \GraphQL\Type\Definition\ResolveInfo $info
    ) : mixed 
    {
        $fieldName = $info->fieldName;
        $mandatory = str_ends_with($info->returnType->toString(), '!');
        $returnType = rtrim($info->returnType->toString(), '!');

        if ($source instanceof BaseModel) {
            if (method_exists($source, $fieldName)) {
                return $this->containerCall([$source, $fieldName]);
            }

            if (($foundMethod = $this->classHasMethodReturningType($source, $returnType)) !== false) {
                return $this->containerCall([$source, $foundMethod]);
            }

            if (preg_match("/^\[(.*?)\]$/", $returnType, $matches)) {
                if (($foundMethod = $this->classHasPropertyNameMethod($source, $fieldName)) !== false) {
                    return $this->containerCall([$source, $foundMethod]);
                }
            }

            return $source->getData($fieldName);
        }

        if (preg_match("/^\[(.*?)\]$/", $returnType, $matches)) {
            if (class_exists("\\App\\Site\\Models\\".$matches[1])) {
                /** @var BaseCollection $collection */
                $collection = $this->containerCall(["\\App\\Site\\Models\\".$matches[1], "getCollection"]);
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

                return $collection->getItems();
            }
        }

        if (class_exists("App\\Site\\GraphQL\\Resolvers\\".ucfirst($fieldName)) && is_callable(["App\\Site\\GraphQL\\Resolvers\\".ucfirst($fieldName), 'resolve'])) {
            return $this->containerCall(["App\\Site\\GraphQL\\Resolvers\\".$fieldName, 'resolve'], ['args' => $args]);
        }

        if ($mandatory) {
            throw new Exception("Field \"{$fieldName}\" not found!");
        }
        
        return null;
    }

    private function classHasMethodReturningType($class, $returnType) : string|bool
    {
        if (class_exists("App\\Site\\Models\\".$returnType)) {
            $returnType = "App\\Site\\Models\\".$returnType;
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


    /**
     * {@inheritdocs}
     * this is only for compatibility
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     */
    public function process(?RouteInfo $route_info = null, $route_data = []): Response
    {
        return $this->renderPage($route_info, $route_data);
    }
}