<?php

/**
 * SiteBase
 * PHP Version 8.0
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
use App\Base\Abstracts\Controllers\AdminFormPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Role;
use App\Site\Models\Permission;
use App\Site\Models\RolePermission;

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
        return 'form_admin_page';
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
     */
    protected function getTemplateData(): array
    {
        $this->template_data += [
            'roles' => Role::getCollection()->getItems(),
            'permissions' => Permission::getCollection()->getItems(),
        ];
        return $this->template_data;
    }

    /**
     * {@inheritdocs}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => $this->getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'key',
            'text' => 'Permissions',
            'section' => 'system',
            'order' => 4,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws \Exception
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
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

        $rolesArray = Role::getCollection()->getItems();

        $table->setTableHeader(
            array_merge(
                ["&nbsp;"],
                array_map(
                    function ($el) {
                        /** @var Role $el */
                        return $el->getName();
                    },
                    $rolesArray
                )
            )
        );

        $permission_num = -1;

        foreach (Permission::getCollection() as $permission_model) {
            /** @var Permission $permission_model */
            $permission_num++;
            $table
                ->addRow()
                ->addField(
                    $permission_model->getName() . '_desc',
                    [
                        'type' => 'markup',
                        'value' => $permission_model->getName(),
                    ],
                    $permission_num
                );

            foreach ($rolesArray as $role_model) {
                /** @var Role $role_model */
                $table->addField(
                    $permission_model->getName() . '|' . $role_model->getName() . '|enabled',
                    [
                        'title' => '<span class="slider"></span>',
                        'type' => 'checkbox',
                        'default_value' => 1,
                        'value' => $role_model->checkPermission($permission_model->getName()),
                        'attributes' => [                            
                        ],
                        'label_class' => 'switch',
                    ],
                    $permission_num
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
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
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
     * @throws InvalidValueException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        $values = $form->values();

        foreach ($values->table->getData() as $key => $value) {
            $tmp = explode("|", $key);
            $value = boolval($value);
            if (count($tmp) == 3) {
                $permission_name = $tmp[0];
                $role_name = $tmp[1];
                $permission_model = $this->containerCall([Permission::class, 'loadBy'], ['field' => 'name', 'value' => $permission_name]);
                $role_model = $this->containerCall([Role::class, 'loadBy'], ['field' => 'name', 'value' => $role_name]);
                $role_permission_model = $this->loadRolePermission($role_model, $permission_model);

                if ($value == true && $role_permission_model == null) {
                    $this->addPermission($role_model, $permission_model);
                } elseif ($value == false && $role_permission_model instanceof RolePermission) {
                    $role_permission_model->delete();
                }
            }
        }

        $this->addInfoFlashMessage("Permissions Updated.");

        return $this->refreshPage();
    }

    /**
     * load role permission
     *
     * @param Role $role_model
     * @param Permission $permission_model
     * @return RolePermission|null
     */
    private function loadRolePermission(Role $role_model, Permission $permission_model): ?RolePermission
    {
        try {
            return $this->containerCall([RolePermission::class, 'loadByCondition'], ['condition' => ['role_id' => $role_model->getId(), 'permission_id' => $permission_model->getId()]]);
        } catch (\Exception $e) {
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
    private function addPermission(Role $role_model, Permission $permission_model) : void
    {
        $pivot_model = RolePermission::new();
        $pivot_model->setPermissionId($permission_model->getId());
        $pivot_model->setRoleId($role_model->getId());
        $pivot_model->persist();
    }
}
