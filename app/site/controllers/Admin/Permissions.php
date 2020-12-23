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

use App\Base\Exceptions\InvalidValueException;
use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\AdminFormPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Role;
use \App\Site\Models\Permission;
use \App\Site\Models\RolePermission;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * "Permissions" Admin Page
 */
class Permissions extends AdminFormPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'permissions';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_permissions';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     */
    protected function getTemplateData(): array
    {
        $this->template_data += [
            'roles' => $this->getDb()->role()->fetchAll(),
            'permissions' => $this->getDb()->permission()->fetchAll(),
        ];
        return $this->template_data;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
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

        $table->setTableHeader(
            array_merge(
                ["&nbsp;"],
                array_map(
                    function ($el) {
                        return $el->name;
                    },
                    $this->getDb()->role()->fetchAll()
                )
            )
        );

        $permnum = -1;

        $rolesArray = array_map(
            function ($el) {
                return $this->getContainer()->make(Role::class, ['db_row' => $el]);
            },
            $this->getDb()->role()->fetchAll()
        );

        foreach ($this->getDb()->permission()->fetchAll() as $permission) {
            $permnum++;
            $table
                ->addRow()
                ->addField(
                    $permission->name . '_desc',
                    [
                        'type' => 'markup',
                        'value' => $permission->name,
                    ],
                    $permnum
                );

            foreach ($rolesArray as $role_model) {
                $table->addField(
                    $permission->name . '|' . $role_model->name . '|enabled',
                    [
                        'type' => 'checkbox',
                        'default_value' => 1,
                        'value' => $role_model->checkPermission($permission->name),
                    ],
                    $permnum
                );
            }
        }

        $this->addSubmitButton($form);

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        //$values = $form->values();
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws InvalidValueException
     * @throws NotFoundException
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
                $permission_model = $this->getContainer()->call([Permission::class, 'loadBy'], ['field' => 'name', 'value' => $permission_name]);
                $role_model = $this->getContainer()->call([Role::class, 'loadBy'], ['field' => 'name', 'value' => $role_name]);
                $role_permission_model = $this->loadRolePermission($role_model, $permission_model);

                if ($value == true && $role_permission_model == null) {
                    $this->addPermission($role_model, $permission_model);
                } elseif ($value == false && $role_permission_model instanceof RolePermission) {
                    $role_permission_model->delete();
                }
            }
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * load role permission
     *
     * @param Role $role_model
     * @param Permission $permission_model
     * @return RolePermission|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    private function loadRolePermission(Role $role_model, Permission $permission_model): ?RolePermission
    {
        $role_permission_dbrow = $this->getDb()->table('role_permission')->where(['role_id' => $role_model->getId(), 'permission_id' => $permission_model->getId()])->fetch();
        if ($role_permission_dbrow) {
            return $this->getContainer()->make(RolePermission::class, ['db_row' => $role_permission_dbrow]);
        }

        return null;
    }

    /**
     * adds permission to role
     *
     * @param Role $role_model
     * @param Permission $permission_model
     * @throws BasicException
     * @throws InvalidValueException
     */
    private function addPermission(Role $role_model, Permission $permission_model)
    {
        $pivot_model = RolePermission::new($this->getContainer());
        $pivot_model->setPermissionId($permission_model->getId());
        $pivot_model->setRoleId($role_model->getId());
        $pivot_model->persist();
    }
}
