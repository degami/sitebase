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
use \App\Base\Abstracts\AdminFormPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Role;
use \App\Site\Models\Permission;
use \App\Site\Models\RolePermission;

/**
 * "Permissions" Admin Page
 */
class Permissions extends AdminFormPage
{
    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'permissions';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_permissions';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getObjectClass()
    {
        return null; // not needed here
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getTemplateData()
    {
        $this->templateData += [
            'roles' => $this->getDb()->role()->fetchAll(),
            'permissions' => $this->getDb()->permission()->fetchAll(),
        ];
        return $this->templateData;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $table = $form->addField('table', [
            'type' => 'table_container',
            'attributes' => [
                'class' => 'table table-striped',
                'style' => 'width: 100%; display: table;',
            ],
            'thead_attributes' => [
                'class' => 'thead-dark',
            ],
        ]);

        $table->setTableHeader(array_merge(["&nbsp;"], array_map(function ($el) {
            return $el->name;
        }, $this->getDb()->role()->fetchAll())));

        $permnum = -1;

        $rolesArray = array_map(
            function ($el) {
                return $this->getContainer()->make(Role::class, ['dbrow' => $el]);
            },
            $this->getDb()->role()->fetchAll()
        );

        foreach ($this->getDb()->permission()->fetchAll() as $permission) {
            $permnum++;
            $table
                ->addRow()
                ->addField($permission->name.'_desc', [
                    'type' => 'markup',
                    'value' => $permission->name,
                ], $permnum);

            foreach ($rolesArray as $role_model) {
                $table->addField($permission->name.'|'.$role_model->name.'|enabled', [
                    'type' => 'checkbox',
                    'default_value' => 1,
                    'value' => $role_model->checkPermission($permission->name),
                ], $permnum);
            }
        }

        $form->addField('button', [
            'type' => 'submit',
            'value' => 'ok',
            'container_class' => 'form-item mt-3',
            'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
        ]);

        return $form;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        return true;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        foreach ($values->table->getData() as $key => $value) {
            $tmp = explode("|", $key);
            $value = boolval($value);
            if (count($tmp) == 3) {
                $permission_name = $tmp[0];
                $role_name = $tmp[1];
                $permission_model = $this->getContainer()->call([Permission::class,'loadBy'], ['field' => 'name', 'value' => $permission_name]);
                $role_model = $this->getContainer()->call([Role::class,'loadBy'], ['field' => 'name', 'value' => $role_name]);
                $role_permission_model = $this->loadRolePermission($role_model, $permission_model);

                if ($value == true && $role_permission_model == null) {
                    $this->addPermission($role_model, $permission_model);
                } else if ($value == false && $role_permission_model instanceof RolePermission) {
                    $role_permission_model->delete();
                }
            }
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    private function loadRolePermission($role_model, $permission_model)
    {
        $role_permission_dbrow = $this->getDb()->table('role_permission')->where(['role_id' => $role_model->id, 'permission_id' => $permission_model->id])->fetch();
        if ($role_permission_dbrow) {
            return $this->getContainer()->make(RolePermission::class, ['dbrow' => $role_permission_dbrow]);
        }

        return null;
    }

    private function addPermission($role_model, $permission_model)
    {
        $pivot_model = RolePermission::new($this->getContainer());
        $pivot_model->permission_id = $permission_model->id;
        $pivot_model->role_id = $role_model->id;
        $pivot_model->persist();
    }
}