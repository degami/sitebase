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
namespace App\Base\Abstracts;

use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use \App\Base\Abstracts\AdminPage;
use \App\Base\Abstracts\FrontendModel;
use \Degami\PHPFormsApi as FAPI;
use \Degami\Basics\Html\TagElement;
use \App\App;

/**
 * Base for admin page that manages a Model
 */
abstract class AdminManageModelsPage extends AdminFormPage
{
    protected $objectInstance = null;

    /**
     * {@inheriydocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        if ($this->templateData['action'] == 'list') {
            $this->addNewButton();

            $paginate_params = [
                'order' => $this->getRequest()->query->get('order'),
                'condition' => $this->getRequest()->query->get('search')
            ];
            if (is_array($paginate_params['condition'])) {
                foreach ($paginate_params['condition'] as $col => $search) {
                    if (trim($search) == '') {
                        continue;
                    }
                    $paginate_params['condition']['`'.$col . '` LIKE ?'] = ['%'.$search.'%'];
                    unset($paginate_params['condition'][$col]);
                }

                $paginate_params['condition'] = array_filter($paginate_params['condition']);
            }

            $data = $this->getContainer()->call([$this->getObjectClass(), 'paginate'], $paginate_params);
            $this->templateData += [
                'table' => $this->getHtmlRenderer()->renderAdminTable($this->getTableElements($data['items']), $this->getTableHeader(), $this),
                'total' => $data['total'],
                'current_page' => $data['page'],
                'paginator' => $this->getHtmlRenderer()->renderPaginator($data['page'], $data['total'], $this),
            ];
        }
    }


    /**
     * gets model object (loaded or new)
     *
     * @return mixed
     */
    public function getObject()
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
    public function getTable()
    {
        return $this->getTemplateData()['table'];
    }

    /**
     * gets paginator html
     *
     * @return string
     */
    public function getPaginator()
    {
        return $this->getTemplateData()['paginator'];
    }

    /**
     * defines table header
     *
     * @return array|null
     */
    protected function getTableHeader()
    {
        return null;
    }

    /**
     * defines table rows
     *
     * @param  array $data
     * @return array
     */
    abstract protected function getTableElements($data);

    /**
     * loads object by id
     *
     * @param  integer $id
     * @return \App\Base\Abstracts\Model
     */
    protected function loadObject($id)
    {
        if (!is_subclass_of($this->getObjectClass(), \App\Base\Abstracts\Model::class)) {
            return null;
        }

        return $this->getContainer()->call([$this->getObjectClass(), 'load'], [ 'id' => $id]);
    }

    /**
     * gets new empty model
     *
     * @return \App\Base\Abstracts\Model
     */
    protected function newEmptyObject()
    {
        if (!is_subclass_of($this->getObjectClass(), \App\Base\Abstracts\Model::class)) {
            return null;
        }

        return $this->getContainer()->make($this->getObjectClass());
    }

    /**
     * adds a "new" button
     */
    public function addNewButton()
    {
        $this->addActionLink('new-btn', 'new-btn', $this->getUtils()->getIcon('plus').' '.$this->getUtils()->translate('New', $this->getCurrentLocale()), $this->getControllerUrl().'?action=new', 'btn btn-sm btn-success');
    }


    /**
     * gets action button html
     *
     * @param string $action
     * @param integer $object_id
     * @param string $class
     * @param string $icon
     * @param string $title
     * @return string
     */
    public function getActionButton($action, $object_id, $class, $icon, $title = '')
    {
        $button = (string)(new TagElement(
            [
            'tag' => 'a',
            'attributes' => [
                'class' => 'btn btn-sm btn-'.$class,
                'href' => $this->getControllerUrl() .'?action='.$action.'&'.$this->getObjectIdQueryParam().'='.$object_id,
                'title' => (trim($title) != '') ? $this->getUtils()->translate($title, $this->getCurrentLocale()) : '',
            ],
            'text' => $this->getUtils()->getIcon($icon),
            ]
        ));

        return (string) $button;
    }

    /**
     * gets delete button html
     *
     * @param integer $object_id
     * @return string
     */
    public function getDeleteButton($object_id)
    {
        return $this->getActionButton('delete', $object_id, 'danger', 'trash', 'Delete');
    }

    /**
     * gets edit button html
     *
     * @param integer $object_id
     * @return string
     */
    public function getEditButton($object_id)
    {
        return $this->getActionButton('edit', $object_id, 'primary', 'edit', 'Edit');
    }

    /**
     * gets "to frontend" button html
     *
     * @param FrontendModel $object
     * @return string
     */
    public function getFrontendModelButton(FrontendModel $object, $class = 'light', $icon = 'zoom-in')
    {
        $button = (string)(new TagElement(
            [
            'tag' => 'a',
            'attributes' => [
                'class' => 'btn btn-sm btn-'.$class,
                'href' => $object->getFrontendUrl(),
                'target' => '_blank',
                'title' => $this->getUtils()->translate('View', $this->getCurrentLocale()),
            ],
            'text' => $this->getUtils()->getIcon($icon),
            ]
        ));

        return (string) $button;
    }

    /**
     * gets object to show class name for loading
     *
     * @return string
     */
    abstract public function getObjectClass();

    /**
     * defines object id query param name
     *
     * @return string
     */
    abstract protected function getObjectIdQueryParam();
}
