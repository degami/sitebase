<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Tools\Utils;


use HaydenPierce\ClassFinder\ClassFinder as HaydenClassFinder;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Interfaces\EventListenerInterface;
use App\Base\Interfaces\Model\ProductInterface;
use App\Base\Interfaces\Commerce\PaymentMethodInterface;
use App\Base\Interfaces\Model\PhysicalProductInterface;
use App\Base\Interfaces\Commerce\ShippingMethodInterface;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Interfaces\AI\AIModelInterface;

/**
 * ClassFinder utils functions Helper Class
 */
class ClassFinder
{

    public const BASE_COMMANDS_NAMESPACE = 'App\Base\Commands';
    public const COMMANDS_NAMESPACE = 'App\Site\Commands';

    public const BASE_CONTROLLERS_NAMESPACE = 'App\Base\Controllers';
    public const CONTROLLERS_NAMESPACE = 'App\Site\Controllers';

    public const BASE_CRON_TASKS_NAMESPACE = 'App\Base\Cron\Tasks';
    public const CRON_TASKS_NAMESPACE = 'App\Site\Cron\Tasks';

    public const BASE_ROUTERS_NAMESPACE = 'App\Base\Routers';
    public const ROUTERS_NAMESPACE = 'App\Site\Routers';

    public const BASE_BLOCKS_NAMESPACE = 'App\Base\Blocks';
    public const BLOCKS_NAMESPACE = 'App\Site\Blocks';

    public const BASE_MODELS_NAMESPACE = 'App\Base\Models';
    public const MODELS_NAMESPACE = 'App\Site\Models';

    public const BASE_CRUD_NAMESPACE = 'App\Base\Crud';
    public const CRUD_NAMESPACE = 'App\Site\Crud';

    public const BASE_COMMERCE_NAMESPACE = 'App\Base\Commerce';
    public const COMMERCE_NAMESPACE = 'App\Site\Commerce';

    public const BASE_MIGRATIONS_NAMESPACE = 'App\Base\Migrations';
    public const MIGRATIONS_NAMESPACE = 'App\Site\Migrations';

    public const BASE_EVENT_LISTENERS_NAMESPACE = 'App\Base\EventListeners';
    public const EVENT_LISTENERS_NAMESPACE = 'App\Site\EventListeners';

    public const WEBHOOKS_NAMESPACE = 'App\Site\Webhooks';
    public const QUEUES_NAMESPACE = 'App\Site\Queues';

    public const GRAPHQL_RESOLVERS_NAMESPACE = 'App\Site\GraphQL\Resolvers';

    public const BASE_AIMODELS_NAMESPACE = 'App\Base\AI\Models';
    public const AIMODELS_NAMESPACE = 'App\Site\AI\Models';

    protected array $classesInNamespaces = [];
    protected array $classesNamespaces = [];


    public function __construct()
    {
        $this->classesNamespaces = [
            'commands' => [
                static::BASE_COMMANDS_NAMESPACE,
                static::COMMANDS_NAMESPACE,
            ],
            'controllers' => [
                static::BASE_CONTROLLERS_NAMESPACE,
                static::CONTROLLERS_NAMESPACE,
            ],
            'admincontrollers' => [
                static::BASE_CONTROLLERS_NAMESPACE . '\Admin',
                static::CONTROLLERS_NAMESPACE . '\Admin',
            ],
            'frontendcontrollers' => [
                static::BASE_CONTROLLERS_NAMESPACE . '\Frontend',
                static::CONTROLLERS_NAMESPACE . '\Frontend',
            ],
            'cron_tasks' => [
                static::BASE_CRON_TASKS_NAMESPACE,
                static::CRON_TASKS_NAMESPACE,
            ],
            'routers' => [
                static::BASE_ROUTERS_NAMESPACE,
                static::ROUTERS_NAMESPACE,
            ],
            'blocks' => [
                static::BASE_BLOCKS_NAMESPACE,
                static::BLOCKS_NAMESPACE,
            ],
            'models' => [
                static::BASE_MODELS_NAMESPACE,
                static::MODELS_NAMESPACE,
            ],
            'crud' => [
                static::BASE_CRUD_NAMESPACE,
                static::CRUD_NAMESPACE,
            ],
            'commerce' => [
                static::BASE_COMMERCE_NAMESPACE,
                static::COMMERCE_NAMESPACE,
            ],
            'migrations' => [
                static::BASE_MIGRATIONS_NAMESPACE,
                static::MIGRATIONS_NAMESPACE,
            ],
            'event_listeners' => [
                static::BASE_EVENT_LISTENERS_NAMESPACE,
                static::EVENT_LISTENERS_NAMESPACE,
            ],
            'webhooks' => [
                static::WEBHOOKS_NAMESPACE,
            ],
            'queues' => [
                static::QUEUES_NAMESPACE,
            ],
            'graphql' => [
                static::GRAPHQL_RESOLVERS_NAMESPACE,
            ],
            'ai_models' => [
                static::BASE_AIMODELS_NAMESPACE,
                static::AIMODELS_NAMESPACE,
            ],
        ];
        $this->initClassesCache();
    }

    public function initClassesCache() {
        foreach ($this->classesNamespaces as $group => $namespaces) {
            if (!isset($this->classesInNamespaces[$group])) {
                $this->classesInNamespaces[$group] = [];
            }

            foreach ($namespaces as $namespace) {
                $this->classesInNamespaces[$group] = array_merge($this->classesInNamespaces[$group], HaydenClassFinder::getClassesInNamespace($namespace, HaydenClassFinder::RECURSIVE_MODE));
            }
        }
    }

    public function getAllAppClasses() {
        return array_merge(array_map(fn($group) => array_values($group), $this->classesInNamespaces));
    }

    public function getGroupClasses(string $group) {
        return $this->classesInNamespaces[$group] ?? [];
    }

    public function findAndFilterClasses(callable $filter) {
        return array_filter($this->getAllAppClasses(), $filter);
    }

    public function findAndFilterGroupClasses(string $group, callable $filter) {
        return array_filter($this->getGroupClasses($group), $filter);
    }

    public function findClassesOf(string $className, bool $alsoAbstracts = false) {
        if ($alsoAbstracts) {
            return $this->findAndFilterClasses(fn ($foundClassName) => is_subclass_of($foundClassName, $className));
        }

        return $this->findAndFilterClasses(fn ($foundClassName) => is_subclass_of($foundClassName, $className) && !(new \ReflectionClass($foundClassName))->isAbstract());
    }

    public function findGroupClassesOf(string $group, string $className, bool $alsoAbstracts = false) {
        if ($alsoAbstracts) {
            return $this->findAndFilterGroupClasses($group, fn ($foundClassName) => is_subclass_of($foundClassName, $className));
        }

        return $this->findAndFilterGroupClasses($group, fn ($foundClassName) => is_subclass_of($foundClassName, $className) && !(new \ReflectionClass($foundClassName))->isAbstract());
    }

    public function findModelClasses() {
        return $this->findAndFilterGroupClasses('models', fn($modelClass) => is_subclass_of($modelClass, BaseModel::class) && !is_subclass_of($modelClass, BaseCollection::class));
    }

    public function findIndexableClasses() {
        return $this->findAndFilterGroupClasses('models', fn($modelClass) => is_subclass_of($modelClass, FrontendModel::class) && is_callable([$modelClass, 'isIndexable']) &&  call_user_func([$modelClass, 'isIndexable']));
    }

    public function findEventListenerClasses() {
        return $this->findAndFilterGroupClasses('event_listeners', fn($className) => is_subclass_of($className, EventListenerInterface::class));
    }

    public function findFrontendModelClasses() {
        return $this->findGroupClassesOf('models', FrontendModel::class);
    }

    public function findBlockClasses() {
        return $this->getGroupClasses('blocks');
    }

    public function findProductClasses() {
        return $this->findAndFilterGroupClasses('models', fn($modelClass) => is_subclass_of($modelClass, ProductInterface::class));
    }

    public function findPhysicalProductClasses() {
        return $this->findAndFilterGroupClasses('models', fn($modelClass) => is_subclass_of($modelClass, PhysicalProductInterface::class));
    }

    public function findCronTaskClasses() {
        return $this->getGroupClasses('cron_tasks');
    }

    public function findAdminPages() {
        return $this->findAndFilterGroupClasses('controllers', fn($controllerClass) => is_subclass_of($controllerClass, AdminPage::class));
    }

    public function findExportableClasses() {
        return $this->findAndFilterGroupClasses('models', fn($modelClass) => is_callable([$modelClass, 'isExportable']) && call_user_func([$modelClass, 'isExportable']) == true);
    }

    public function findPaymentMethodClasses() {
        return $this->findAndFilterGroupClasses('commerce', fn($className) => is_subclass_of($className, PaymentMethodInterface::class));
    }

    public function findShippingMethodClasses() {
        return $this->findAndFilterGroupClasses('commerce', fn($className) => is_subclass_of($className, ShippingMethodInterface::class));
    }

    public function findAIModelClasses() {
        return $this->findGroupClassesOf('ai_models', AIModelInterface::class);
    }

    public function findCommandClasses() {
        return $this->getGroupClasses('commands');
    }

    public function findControllers() {
        return $this->getGroupClasses('controllers');
    }

    public function findAdminControllers() {
        return $this->getGroupClasses('admincontrollers');
    }

    public function findCrudControllers() {
        return $this->getGroupClasses('crud');        
    }

    public function findFrontendControllers() {
        return $this->getGroupClasses('frontendcontrollers');        
    }

    public function findWebhookControllers() {
        return $this->getGroupClasses('webhooks');        
    }

    public function findRouterClasses() {
        return $this->getGroupClasses('routers');        
    }

}