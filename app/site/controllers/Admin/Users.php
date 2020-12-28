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

use App\Site\Models\Role;
use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\AdminManageModelsPage;
use \App\Site\Models\User;
use \Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Users" Admin Page
 */
class Users extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_users';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return User::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'user_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        /** @var User $user */
        $user = $this->getObject();
        /** @var Role $role */
        $role = null;
        if ($user->isLoaded()) {
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
                foreach ($this->getContainer()->call([Role::class, 'all']) as $item) {
                    /** @var Role $item */
                    $roles[$item->getId()] = $item->getName();
                }
                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();

                $user_username = $user_roleid = $user_email = $user_nickname = $user_locale = '';
                if ($user->isLoaded()) {
                    $user_username = $user->getUsername();

                    if ($role instanceof Role) {
                        $user_roleid = $role->getId();
                    }

                    $user_email = $user->getEmail();
                    $user_nickname = $user->getNickname();
                    $user_locale = $user->getLocale();
                }

                $form->addField('username', [
                    'type' => 'textfield',
                    'title' => 'Username',
                    'default_value' => $user_username,
                    'validate' => ['required'],
                ])->addField('password', [
                    'type' => 'password',
                    'with_confirm' => true,
                    'with_strength_check' => true,
                    'title' => 'Change Password',
                    'default_value' => '',
                    'validate' => [],
                ])->addField('role_id', [
                    'type' => 'select',
                    'title' => 'Role',
                    'options' => $roles,
                    'default_value' => $user_roleid,
                    'validate' => ['required'],
                ])->addField('email', [
                    'type' => 'email',
                    'title' => 'Email',
                    'default_value' => $user_email,
                    'validate' => ['required'],
                ])->addField('nickname', [
                    'type' => 'textfield',
                    'title' => 'Nickname',
                    'default_value' => $user_nickname,
                    'validate' => ['required'],
                ])->addField('locale', [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $user_locale,
                    'options' => $languages,
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
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /**
         * @var User $user
         */
        $user = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $user->setUsername($values['username']);
                $user->setPassword($this->getUtils()->getEncodedPass($values['password']));
                $user->setRoleId($values['role_id']);
                $user->setEmail($values['email']);
                $user->setNickname($values['nickname']);
                $user->setLocale($values['locale']);

                $this->setAdminActionLogData($user->getChangedData());

                $user->persist();
                break;
            case 'delete':
                $user->delete();

                $this->setAdminActionLogData('Deleted user ' . $user->getId());

                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Username' => ['order' => 'username', 'search' => 'username'],
            'Email' => ['order' => 'email', 'search' => 'email'],
            'Role' => 'role_id',
            'Nickname' => ['order' => 'nickname', 'search' => 'nickname'],
            'Created at' => 'created_at',
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($user) {
                return [
                    'ID' => $user->id,
                    'Username' => $user->username,
                    'Email' => $user->email,
                    'Role' => $user->getRole()->name,
                    'Nickname' => $user->nickname,
                    'Created at' => $user->created_at,
                    'actions' => implode(
                        " ",
                        [
                            $this->getEditButton($user->id),
                            $this->getDeleteButton($user->id),
                        ]
                    ),
                ];
            },
            $data
        );
    }
}
