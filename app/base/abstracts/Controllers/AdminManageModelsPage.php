<?php

/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Abstracts\Controllers;

use App\Base\Abstracts\Models\BaseModel;
use App\Base\Exceptions\PermissionDeniedException;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Exceptions\FormException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Abstracts\Models\FrontendModel;
use Degami\Basics\Html\TagElement;

/**
 * Base for admin page that manages a Model
 */
abstract class AdminManageModelsPage extends AdminFormPage
{
    /**
     * @var BaseModel|null object instance
     */
    protected $objectInstance = null;

    /**
     * @var array|null admin_log data
     */
    protected $admin_action_log_data = null;

    /**
     * {@inheriydocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws FormException
     * @throws PermissionDeniedException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws OutOfRangeException
     */
    public function __construct(ContainerInterface $container, Request $request, RouteInfo $route_info)
    {
        parent::__construct($container, $request, $route_info);
        if ($this->template_data['action'] == 'list') {
            $this->addNewButton();

            $paginate_params = [
                'order' => $this->getRequest()->query->get('order'),
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

            $data = $this->getContainer()->call([$this->getObjectClass(), 'paginate'], $paginate_params);
            $this->template_data += [
                'table' => $this->getHtmlRenderer()->renderAdminTable($this->getTableElements($data['items']), $this->getTableHeader(), $this),
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
            ];
        }
    }

    /**
     * gets search parameters
     *
     * @return array|null
     */
    protected function getSearchParameters(): ?array
    {
        $out = array_filter([
            'like' => $this->getRequest()->query->get('search'),
            'eq' => $this->getRequest()->query->get('foreign'),
        ]);
        return !empty($out) ? $out : null;
    }


    /**
     * gets model object (loaded or new)
     *
     * @return mixed
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getObject(): ?BaseModel
    {
        if (($this->objectInstance != null) && (is_subclass_of($this->objectInstance, $this->getObjectClass()))) {
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
     * @return string
     */
    public function getTable(): ?string
    {
        if (!is_null($this->getTemplate())) {
            return $this->getTemplate()->data()['table'] ?? null;
        }

        return $this->getTemplateData()['table'] ?? null;
    }

    /**
     * gets paginator html
     *
     * @return string
     */
    public function getPaginator(): ?string
    {
        if (!is_null($this->getTemplate())) {
            return $this->getTemplate()->data()['paginator'] ?? null;
        }

        return $this->getTemplateData()['paginator'] ?? null;
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
     * defines table rows
     *
     * @param array $data
     * @return array
     */
    abstract protected function getTableElements(array $data): array;

    /**
     * loads object by id
     *
     * @param int $id
     * @return BaseModel
     */
    protected function loadObject($id): ?BaseModel
    {
        if (!is_subclass_of($this->getObjectClass(), BaseModel::class)) {
            return null;
        }

        return $this->getContainer()->call([$this->getObjectClass(), 'load'], ['id' => $id]);
    }

    /**
     * gets new empty model
     *
     * @return BaseModel
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function newEmptyObject(): ?BaseModel
    {
        if (!is_subclass_of($this->getObjectClass(), BaseModel::class)) {
            return null;
        }

        return $this->getContainer()->make($this->getObjectClass());
    }

    /**
     * adds a "new" button
     *
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addNewButton()
    {
        $this->addActionLink('new-btn', 'new-btn', $this->getHtmlRenderer()->getIcon('plus') . ' ' . $this->getUtils()->translate('New', $this->getCurrentLocale()), $this->getControllerUrl() . '?action=new', 'btn btn-sm btn-success');
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
            $button = new TagElement(
                [
                    'tag' => 'a',
                    'attributes' => [
                        'class' => 'btn btn-sm btn-' . $class,
                        'href' => $this->getControllerUrl() . '?action=' . $action . '&' . $this->getObjectIdQueryParam() . '=' . $object_id,
                        'title' => (trim($title) != '') ? $this->getUtils()->translate($title, $this->getCurrentLocale()) : '',
                    ],
                    'text' => $this->getHtmlRenderer()->getIcon($icon),
                ]
            );

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
        $button = (string)(new TagElement(
            [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-' . $class,
                    'href' => $object->getFrontendUrl(),
                    'target' => '_blank',
                    'title' => $this->getUtils()->translate('View', $this->getCurrentLocale()),
                ],
                'text' => $this->getHtmlRenderer()->getIcon($icon),
            ]
        ));

        return (string)$button;
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
    public function getModelTableName()
    {
        return $this->getContainer()->call([$this->getObjectClass(), 'defaultTableName']);
    }

    /**
     * gets object to show class name for loading
     *
     * @return string
     */
    abstract public function getObjectClass(): string;

    /**
     * defines object id query param name
     *
     * @return string
     */
    abstract protected function getObjectIdQueryParam(): string;
}
