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
use \App\Base\Abstracts\Controllers\AdminManageModelsPage;
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
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'role_id';
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
        $role = $this->getObject();

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
                if ($role->isLoaded()) {
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
                );

                $this->addSubmitButton($form);
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
        $role = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $role->name = $values['name'];

                $this->setAdminActionLogData($role->getChangedData());

                $role->persist();
                break;
            case 'delete':
                $role->delete();

                $this->setAdminActionLogData('Deleted role '.$role->getId());

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
                'actions' => implode(
                    " ",
                    [
                    $this->getEditButton($role->id),
                    $this->getDeleteButton($role->id),
                    ]
                ),
                ];
            },
            $data
        );
    }
}
