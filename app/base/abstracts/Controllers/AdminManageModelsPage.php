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

namespace App\Base\Abstracts\Controllers;

use App\App;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Routing\RouteInfo;
use App\Base\Models\User;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Exceptions\FormException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Degami\Basics\Html\TagElement;
use ReflectionClass;
use App\Base\Abstracts\Controllers\BasePage;
use Symfony\Component\HttpFoundation\Response;

/**
 * Base for admin page that manages a Model
 */
abstract class AdminManageModelsPage extends AdminFormPage
{
    /**
     * @var BaseModel|null object instance
     */
    protected ?BaseModel $objectInstance = null;

    /**
     * @var array|null admin_log data
     */
    protected ?array $admin_action_log_data = null;

    /**
     * {@inheriydocs}
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws FormException
     * @throws NotFoundException
     * @throws OutOfRangeException
     * @throws PermissionDeniedException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);

        if (($this->template_data['action'] ?? 'list') == 'list') {
            $this->addPaginationSizeSelector();
            $this->addNewButton();

            $adminTableId = 'listing-table-' . strtolower($this->getUtils()->getClassBasename($this->getObjectClass()));

            $controllerClassName = str_replace("\\","\\\\", static::class);
            $modelClassName = str_replace("\\","\\\\", $this->getObjectClass());

            $itemsPerPage = $this->getItemsPerPage();
            $data = $this->getTableItems($itemsPerPage);

            if (static::hasMassActions() && !empty($data['items'])) {
                $this->addBatchDeleteButton($modelClassName, $adminTableId);
                $this->addBatchEditButton($controllerClassName, $modelClassName, $adminTableId);
            }

            $tableElements = $this->getTableElements($data['items']);

            if (static::hasMassActions()) {
                // copy "_admin_table_item_pk" from data['items'] into tableElements
                foreach($tableElements as $k => $v) {
                    $tableElements[$k]['_admin_table_item_pk'] = $data['items'][$k]->getData('_admin_table_item_pk');
                }
            }

            $this->template_data += [
                'table' => $this->getHtmlRenderer()->renderAdminTable($tableElements, $this->getTableHeader(), $this, $adminTableId),
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this, $itemsPerPage, 5),
            ];
        }

        if (($this->template_data['action'] ?? 'list') == 'edit') {
            if ($this->getEnvironment()->getVariable('ENABLE_VERSIONING') && $this->containerCall([$this->getObject(), 'canSaveVersions'])) {
                $this->addVersionsButton($this->getObject());
            }
            if ($this->containerCall([$this->getObject(), 'canBeDuplicated'])) {
                $this->addDuplicateButton();
            }
        }

        if (($this->template_data['action'] ?? 'list') != 'list') {
            $this->addBackButton();
        }
    }

    /**
     * Returns Listing Table Items
     * 
     * @return array
     */
    protected function getTableItems(int $itemsPerPage) : array
    {
        $collection = $this->getCollection();
        $data = $this->containerCall([$collection, 'paginate'], ['page_size' => $itemsPerPage]);

        /** @var BaseModel $firstElem */
        $primaryKey = App::getInstance()->containerCall([$collection->getClassName(), 'getKeyField']);

        $resolvePrimaryKey = function(BaseModel $elem, $primaryKey) {
            $out = [];
            foreach ((array)$primaryKey as $pk) {
                $out[$pk] = $elem->getData($pk);
            }

            return $out;
        };

        foreach ($data['items'] as &$datum) {
            $datum->setData(['_admin_table_item_pk' => json_encode($resolvePrimaryKey($datum, $primaryKey))]);
        }

        return $data;
    }

    /**
     * Returns default ordering for listing
     * 
     * @return array
     */
    protected function defaultOrder() : array
    {
        try {
            $reflection = new ReflectionClass(static::getObjectClass());
            $docComment = $reflection->getDocComment();
            if ($docComment && strpos($docComment, 'getId') !== false) {
                return ['id' => 'ASC'];
            }

            if ($docComment && strpos($docComment, 'getCreatedAt') !== false) {
                return ['created_at' => 'ASC'];
            }
        } catch (Exception $e) { }

        return [];
    }

    /**
     * Return Object Collection
     * 
     * @return BaseCollection
     */
    protected function getCollection() : BaseCollection
    {
        $paginate_params = [
            'order' => $this->getRequest()->query->all('order') ?: $this->defaultOrder(),
            'condition' => $this->getSearchParameters(),
        ];

        if (is_array($paginate_params['condition'])) {
            $conditions = [];
            if (isset($paginate_params['condition']['like'])) {
                foreach ($paginate_params['condition']['like'] as $col => $search) {
                    if (trim($search) == '') {
                        continue;
                    }
                    $conditions['`' . $col . '` LIKE ?'] = ['%' . $search . '%'];
                }
            }
            if (isset($paginate_params['condition']['eq'])) {
                foreach ($paginate_params['condition']['eq'] as $col => $search) {
                    if (trim($search) == '') {
                        continue;
                    }
                    $conditions['`' . $col . '` = ?'] = [$search];
                }
            }

            $paginate_params['condition'] = array_filter($conditions);
        }

        /** @var \App\Base\Abstracts\Models\BaseCollection $collection */
        $collection = $this->containerCall([static::getObjectClass(), 'getCollection']);
        $collection->addCondition($paginate_params['condition'])->addOrder($paginate_params['order']);

        return $collection;
    }

    /**
     * get items per page on listing
     * 
     * @return int
     */
    protected function getItemsPerPage() : int 
    {
        /** @var User $user */
        $user = $this->getCurrentUser();

        $uiSettings = $user->getUserSession()->getSessionKey('uiSettings');
        $currentRoute = $this->getRouteInfo()->getRouteName();

        if (is_array($uiSettings) && isset($uiSettings[$currentRoute])) {
            if (isset($uiSettings[$currentRoute]['itemsPerPage'])) {
                return intval($uiSettings[$currentRoute]['itemsPerPage']);
            }
        }

        return BaseCollection::ITEMS_PER_PAGE;
    }

    /**
     * gets search parameters
     *
     * @return array|null
     */
    protected function getSearchParameters(): ?array
    {
        $out = array_filter([
            'like' => $this->getRequest()->query->all('search'),
            'eq' => $this->getRequest()->query->all('foreign'),
        ]);
        return !empty($out) ? $out : null;
    }

    /**
     * gets model object (loaded or new)
     *
     * @return BaseModel|null
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getObject(): ?BaseModel
    {
        if (($this->objectInstance != null) && (is_subclass_of($this->objectInstance, static::getObjectClass()))) {
            return $this->objectInstance;
        }

        if ($this->getRequest()->query->has($this->getObjectIdQueryParam())) {
            $this->objectInstance = $this->loadObject($this->getRequest()->query->get($this->getObjectIdQueryParam()));
        } else {
            $this->objectInstance = $this->newEmptyObject();
        }

        return $this->objectInstance;
    }

    /**
     * gets model table html
     *
     * @return string|null
     */
    public function getTable(): ?string
    {
        return $this->getTemplate()?->data()['table'] ?? ($this->getTemplateData()['table'] ?? null);
    }

    /**
     * gets paginator html
     *
     * @return string|null
     */
    public function getPaginator(): ?string
    {
        return $this->getTemplate()?->data()['paginator'] ?? ($this->getTemplateData()['paginator'] ?? null);
    }

    /**
     * defines table header
     *
     * @return array|null
     */
    protected function getTableHeader(): ?array
    {
        return null;
    }

    /**
     * loads object by id
     *
     * @param int $id
     * 
     * @return BaseModel|null
     */
    protected function loadObject(int $id): ?BaseModel
    {
        if (!is_subclass_of(static::getObjectClass(), BaseModel::class)) {
            return null;
        }

        return $this->containerCall([static::getObjectClass(), 'load'], ['id' => $id]);
    }

    /**
     * gets new empty model
     *
     * @return BaseModel|null
     * @throws DependencyException
     * @throws NotFoundException
     * 
     * @return BaseModel|null
     */
    protected function newEmptyObject(): ?BaseModel
    {
        if (!is_subclass_of(static::getObjectClass(), BaseModel::class)) {
            return null;
        }

        return $this->containerMake(static::getObjectClass());
    }

    /**
     * adds a "new" button
     *
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * 
     * @return void
     */
    public function addNewButton()
    {
        $this->addActionLink('new-btn', 'new-btn', $this->getHtmlRenderer()->getIcon('plus') . ' ' . $this->getUtils()->translate('New', locale: $this->getCurrentLocale()), $this->getControllerUrl() . '?action=new', 'btn btn-sm btn-outline-success');
    }

    /**
     * adds a "duplicate" button
     *
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * 
     * @return void
     */
    public function addDuplicateButton()
    {
        $this->addActionLink('duplicate-btn', 'duplicate-btn', $this->getHtmlRenderer()->getIcon('copy') . ' ' . $this->getUtils()->translate('Duplicate', locale: $this->getCurrentLocale()), $this->getControllerUrl() . '?action=duplicate&' . $this->getObjectIdQueryParam() . '='.$this->getRequest()->get($this->getObjectIdQueryParam()), 'btn btn-sm btn-light');
    }

    /**
     * adds a "delete selected" button
     *
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * 
     * @return void
     */
    public function addBatchDeleteButton(string $className, string $tableId)
    {
        $this->addActionLink('delete-batch-btn', 'delete-batch-btn', $this->getHtmlRenderer()->getIcon('delete') . ' ' . $this->getUtils()->translate('Mass Delete', locale: $this->getCurrentLocale()), link_class: 'btn btn-sm btn-outline-danger', attributes: ['onClick' => '$("#admin").appAdmin(\'listingTableDeleteSelected\', \'#'.$tableId.'\', \''.$className.'\'); return false;']);
    }

    /**
     * adds a "edit selected" button
     *
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * 
     * @return void
     */
    public function addBatchEditButton(string $controllerClassName, string $modelClassName, string $tableId)
    {
        $this->addActionLink('edit-batch-btn', 'edit-batch-btn', $this->getHtmlRenderer()->getIcon('edit') . ' ' . $this->getUtils()->translate('Mass Edit', locale: $this->getCurrentLocale()), link_class: 'btn btn-sm btn-outline-dark', attributes: ['onClick' => '$("#admin").appAdmin(\'listingTableEditSelected\', \'#'.$tableId.'\', \''.$controllerClassName.'\', \''.$modelClassName.'\', this); return false;']);
    }

    /**
     * adds a "duplicate" button
     *
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * 
     * @return void
     */
    public function addVersionsButton(?BaseModel $object = null)
    {
        if ($object) {
            $primaryKey = $object->getKeyFieldValue();
        } else {
            $primaryKey = $this->getRequest()->get($this->getObjectIdQueryParam());
        }

        if (is_array($primaryKey)) {
            $primaryKey = json_encode($primaryKey);
        }

        $this->addActionLink('versions-btn', 'versions-btn', '&#9776; Versions', $this->getUrl('crud.app.base.controllers.admin.json.versions', ['class' => base64_encode(get_class($object)), 'key' => base64_encode($primaryKey) ]), 'btn btn-sm btn-light inToolSidePanel');
    }


    /**
     * adds a paginator selecton
     * 
     * @return void
     */
    public function addPaginationSizeSelector()
    {
        // calculate options values, including value used for pagination
        $options = array_unique(array_merge([10, 25, 50, 200, 500], [$this->getItemsPerPage()]));
        sort($options);

        $select = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'select',
            'id' => 'pagination-size-selector',
            'attributes' => [
                'class' => 'paginator-items-choice',
                'style' => 'width: 50px',
            ],
            'children' => array_map(function($val) {
                $selected = [];
                if ($val == $this->getItemsPerPage()) {
                    $selected = ['selected' => 'selected'];
                }
                return $this->containerMake(TagElement::class, ['options' => [
                    'tag' => 'option',
                    'value' => $val,
                    'attributes' => [
                        'class' => '',
                    ] + $selected,
                    'text' => $val,
                ]]);
            }, $options),
        ]]);
        $this->action_buttons[] = $this->getUtils()->translate('Items per page', locale: $this->getCurrentLocale()). ':' . $select;
    }

    /**
     * gets action button html
     *
     * @param string $action
     * @param int $object_id
     * @param string $class
     * @param string $icon
     * @param string $title
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getActionButton(string $action, int $object_id, string $class, string $icon, string $title = ''): string
    {
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-' . $class,
                    'href' => $this->getControllerUrl() . '?action=' . $action . '&' . $this->getObjectIdQueryParam() . '=' . $object_id,
                    'title' => (trim($title) != '') ? $this->getUtils()->translate($title, locale: $this->getCurrentLocale()) : '',
                ],
                'text' => $this->getHtmlRenderer()->getIcon($icon),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {
        }

        return '';
    }

    /**
     * gets delete button html
     *
     * @param int $object_id
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDeleteButton(int $object_id): string
    {
        return $this->getActionButton('delete', $object_id, 'danger', 'trash', 'Delete');
    }

    /**
     * gets edit button html
     *
     * @param int $object_id
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getEditButton(int $object_id): string
    {
        return $this->getActionButton('edit', $object_id, 'primary', 'edit', 'Edit');
    }

    /**
     * gets duplicate button html
     *
     * @param int $object_id
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDuplicateButton(int $object_id): string
    {
        return $this->getActionButton('duplicate', $object_id, 'primary', 'duplicate', 'Duplicate');
    }

    /**
     * gets "to frontend" button html
     *
     * @param FrontendModel $object
     * @param string $class
     * @param string $icon
     * @return string
     * @throws BasicException
     * @throws Exception
     */
    public function getFrontendModelButton(FrontendModel $object, $class = 'light', $icon = 'zoom-in'): string
    {
        $button = $this->containerMake(TagElement::class, ['options' => [
            'tag' => 'a',
            'attributes' => [
                'class' => 'btn btn-sm btn-' . $class,
                'href' => $object->getFrontendUrl(),
                'target' => '_blank',
                'title' => $this->getUtils()->translate('View', locale: $this->getCurrentLocale()),
            ],
            'text' => $this->getHtmlRenderer()->getIcon($icon),
        ]]);

        return $button;
    }

    /**
     * sets admin log data
     *
     * @param $admin_action_log_data
     * @return $this
     */
    public function setAdminActionLogData($admin_action_log_data): AdminManageModelsPage
    {
        if (!is_array($admin_action_log_data)) {
            $admin_action_log_data = [$admin_action_log_data];
        }
        $this->admin_action_log_data = $admin_action_log_data;

        return $this;
    }

    /**
     * gets admin log data
     *
     * @return array|null
     */
    public function getAdminActionLogData(): ?array
    {
        return $this->admin_action_log_data;
    }

    /**
     * gets model class table name
     *
     * @return mixed
     */
    public function getModelTableName(): mixed
    {
        return $this->containerCall([static::getObjectClass(), 'defaultTableName']);
    }

    /**
     * {@inheritdoc}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender(): BasePage|Response
    {
        if ($this->getRequest()->get('action') == 'duplicate') {
            $object = $this->getObject();
            $copy = $object?->duplicate()->persist();
            if ($copy) {
                $this->addSuccessFlashMessage($this->getUtils()->translate('Object duplicated successfully', locale: $this->getCurrentLocale()));
                return $this->doRedirect($this->getControllerUrl() . '?action=edit&' . $this->getObjectIdQueryParam() . '=' . $copy->getId());
            } else {
                $this->addErrorFlashMessage($this->getUtils()->translate('Error duplicating object', locale: $this->getCurrentLocale()));
                return $this->doRedirect($this->getControllerUrl() . '?action=list');
            }
        }

        return parent::beforeRender();
    }

    public static function exposeDataToDashboard() : mixed
    {
        return App::getInstance()->containerCall([static::getObjectClass(), 'getCollection'])->count();        
    }

    protected function hasMassActions(): bool
    {
        return true;
    }

    /**
     * gets object to show class name for loading
     *
     * @return string
     */
    abstract public static function getObjectClass(): string;

    /**
     * defines object id query param name
     *
     * @return string
     */
    abstract protected function getObjectIdQueryParam(): string;

    /**
     * defines table rows
     *
     * @param array $data
     * @return array
     */
    abstract protected function getTableElements(array $data): array;    
}
