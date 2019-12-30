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
use \App\Site\Models\User;
use \Degami\PHPFormsApi as FAPI;

/**
 * "Users" Admin Page
 */
class Users extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getTemplateName()
    {
        return 'users';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_users';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    public function getObjectClass()
    {
        return User::class;
    }

    /**
     * {@inheritdocs}
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $user = $role = null;
        if ($this->getRequest()->get('user_id')) {
            $user = $this->loadObject($this->getRequest()->get('user_id'));
            $role = $user->getRole();
        }

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $roles = [];
                foreach ($this->getDb()->role()->fetchAll() as $rolerow) {
                    $roles[$rolerow->id] = $rolerow->name;
                }
                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();

                $user_username = $user_roleid = $user_email = $user_nickname = $user_locale = '';
                if ($user instanceof User) {
                    $user_username = $user->getUsername();
                    $user_roleid = $user->getUserId();
                    $user_email = $user->getEmail();
                    $user_nickname = $user->getNickname();
                    $user_locale = $user->getLocale();
                }

                $form->addField('username', [
                    'type' => 'textfield',
                    'title' => 'Username',
                    'default_value' => $user_username,
                    'validate' => ['required'],
                ])
                ->addField('password', [
                    'type' => 'password',
                    'with_confirm' => true,
                    'with_strength_check' => true,
                    'title' => 'Change Password',
                    'default_value' => '',
                    'validate' => [],
                ])
                ->addField('role_id', [
                    'type' => 'select',
                    'title' => 'Role',
                    'options' => $roles,
                    'default_value' => $user_roleid,
                    'validate' => ['required'],
                ])
                ->addField('email', [
                    'type' => 'email',
                    'title' => 'Email',
                    'default_value' => $user_email,
                    'validate' => ['required'],
                ])
                ->addField('nickname', [
                    'type' => 'textfield',
                    'title' => 'Nickname',
                    'default_value' => $user_nickname,
                    'validate' => ['required'],
                ])
                ->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $user_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                ])
                ->addField('button', [
                    'type' => 'submit',
                    'value' => 'ok',
                    'container_class' => 'form-item mt-3',
                    'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                ]);
                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

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
        /** @var User $user */
        $user = $this->newEmptyObject();
        if ($this->getRequest()->get('user_id')) {
            $user = $this->loadObject($this->getRequest()->get('user_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $user->username = $values['username'];
                $user->password = $this->getUtils()->getEncodedPass($values['password']);
                $user->role_id = $values['role_id'];
                $user->email = $values['email'];
                $user->nickname = $values['nickname'];
                $user->locale = $values['locale'];

                $user->persist();
                break;
            case 'delete':
                $user->delete();
                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getTableHeader()
    {
        return [
            'ID' => 'id',
            'Username' => 'username',
            'Email' => 'email',
            'Role' => 'role_id',
            'Nickname' => 'nickname',
            'Created at' => 'created_at',
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     * @param array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(function ($user) {
            return [
                'ID' => $user->id,
                'Username' => $user->username,
                'Email' => $user->email,
                'Role' => $user->getRole()->name,
                'Nickname' => $user->nickname,
                'Created at' => $user->created_at,
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&user_id='. $user->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&user_id='. $user->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
            ];
        }, $data);
    }
}