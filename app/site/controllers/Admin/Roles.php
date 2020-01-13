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
namespace App\Site\Controllers\Admin;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\AdminManageModelsPage;
use \App\Site\Models\Role;
use \Degami\PHPFormsApi as FAPI;

/**
 * "Roles" Admin Page
 */
class Roles extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'roles';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_permissions';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return Role::class;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $role = null;
        if ($this->getRequest()->get('role_id')) {
            $role = $this->loadObject($this->getRequest()->get('role_id'));
        }

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $role_name = '';
                if ($role instanceof Role) {
                    $role_name = $role->name;
                }
                $form->addField(
                    'name',
                    [
                    'type' => 'textfield',
                    'title' => 'Role Name',
                    'default_value' => $role_name,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'button',
                    [
                    'type' => 'submit',
                    'value' => 'ok',
                    'container_class' => 'form-item mt-3',
                    'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                    ]
                );
                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /**
         * @var Role $role
         */
        $role = $this->newEmptyObject();
        if ($this->getRequest()->get('role_id')) {
            $role = $this->loadObject($this->getRequest()->get('role_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $role->name = $values['name'];
                $role->persist();
                break;
            case 'delete':
                $role->delete();
                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader()
    {
        return [
            'ID' => 'id',
            'Name' => ['order' => 'name', 'search' => 'name'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param  array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(
            function ($role) {
                return [
                'ID' => $role->id,
                'Name' => $role->name,
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&role_id='. $role->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&role_id='. $role->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
