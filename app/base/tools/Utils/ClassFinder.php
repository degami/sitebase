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

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
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
use Psr\Container\ContainerInterface;

/**
 * ClassFinder utils functions Helper Class
 */
class ClassFinder extends ContainerAwareObject
{

    protected array $classesInNamespaces = [];
    protected array $classesNamespaces = [
        'commands' => [
            App::BASE_COMMANDS_NAMESPACE,
            App::COMMANDS_NAMESPACE,
        ],
        'controllers' => [
            App::BASE_CONTROLLERS_NAMESPACE,
            App::CONTROLLERS_NAMESPACE,
        ],
        'admincontrollers' => [
            App::BASE_CONTROLLERS_NAMESPACE . '\Admin',
            App::CONTROLLERS_NAMESPACE . '\Admin',
        ],
        'frontendcontrollers' => [
            App::BASE_CONTROLLERS_NAMESPACE . '\Frontend',
            App::CONTROLLERS_NAMESPACE . '\Frontend',
        ],
        'cron_tasks' => [
            App::BASE_CRON_TASKS_NAMESPACE,
            App::CRON_TASKS_NAMESPACE,
        ],
        'routers' => [
            App::BASE_ROUTERS_NAMESPACE,
            App::ROUTERS_NAMESPACE,
        ],
        'blocks' => [
            App::BASE_BLOCKS_NAMESPACE,
            App::BLOCKS_NAMESPACE,
        ],
        'models' => [
            App::BASE_MODELS_NAMESPACE,
            App::MODELS_NAMESPACE,
        ],
        'crud' => [
            App::BASE_CRUD_NAMESPACE,
            App::CRUD_NAMESPACE,
        ],
        'commerce' => [
            App::BASE_COMMERCE_NAMESPACE,
            App::COMMERCE_NAMESPACE,
        ],
        'migrations' => [
            App::BASE_MIGRATIONS_NAMESPACE,
            App::MIGRATIONS_NAMESPACE,
        ],
        'event_listeners' => [
            App::BASE_EVENT_LISTENERS_NAMESPACE,
            App::EVENT_LISTENERS_NAMESPACE,
        ],
        'webhooks' => [
            App::WEBHOOKS_NAMESPACE,
        ],
        'queues' => [
            App::QUEUES_NAMESPACE,
        ],
        'graphql' => [
            App::GRAPHQL_RESOLVERS_NAMESPACE,
        ],
        'ai_models' => [
            App::BASE_AIMODELS_NAMESPACE,
            App::AIMODELS_NAMESPACE,
        ],
    ];


    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
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
        return $this->getClassFinder()->findAndFilterGroupClasses('models', fn($modelClass) => is_subclass_of($modelClass, BaseModel::class) && !is_subclass_of($modelClass, BaseCollection::class));
    }

    public function findIndexableClasses() {
        return $this->findAndFilterGroupClasses('models', fn($modelClass) => is_subclass_of($modelClass, FrontendModel::class) && $this->containerCall([$modelClass, 'isIndexable']));
    }

    public function findEventListenerClasses() {
        return $this->findAndFilterGroupClasses('event_listeners', fn($className) => is_subclass_of($className, EventListenerInterface::class));
    }

    public function findFrontendModelClasses() {
        return $this->findGroupClassesOf('models', FrontendModel::class);
    }

    public function findBlockClasses() {
        return $this->getGroupClasses('block');
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
        return $this->findAndFilterGroupClasses('models', fn($modelClass) => is_callable([$modelClass, 'isExportable']) && $this->containerCall([$modelClass, 'isExportable']) == true);
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