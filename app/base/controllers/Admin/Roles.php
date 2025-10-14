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

namespace App\Base\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use App\Base\Models\Role;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * "Roles" Admin Page
 */
class Roles extends AdminManageModelsPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_permissions';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return Role::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'role_id';
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';
        $role = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':

                $role_name = '';
                if ($role->isLoaded()) {
                    $role_name = $role->name;
                }
                $form->addField('name', [
                    'type' => 'textfield',
                    'title' => 'Role Name',
                    'default_value' => $role_name,
                    'validate' => ['required'],
                ]);

                $this->addSubmitButton($form);
                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var Role $role
         */
        $role = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $role->setName($values['name']);

                $this->setAdminActionLogData($role->getChangedData());

                $role->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Role Saved."));
                break;
            case 'delete':
                $role->delete();

                $this->setAdminActionLogData('Deleted role ' . $role->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Role Deleted."));

                break;
        }

        return $this->refreshPage();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Name' => ['order' => 'name', 'search' => 'name'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($role) {
                return [
                    'ID' => $role->id,
                    'Name' => $role->name,
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($role->id),
                        static::DELETE_BTN => $this->getDeleteButton($role->id),
                    ],
                ];
            },
            $data
        );
    }
}
