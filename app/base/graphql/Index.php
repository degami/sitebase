<?php

namespace App\Base\GraphQl;

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Abstracts\Models\BaseModel;
use App\Site\Models\Page;
use Exception;
use Symfony\Component\HttpFoundation\JsonResponse;
use GraphQL\GraphQL;
use GraphQL\Type\Schema;
use GraphQL\Utils\BuildSchema;
use HaydenPierce\ClassFinder\ClassFinder;
use ReflectionNamedType;

class Index extends ContainerAwareObject
{

    public function renderPage() : JsonResponse
    {
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
                return $this->containerCall(["\\App\\Site\\Models\\".$matches[1], "getCollection"])->getItems();
            }
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
}