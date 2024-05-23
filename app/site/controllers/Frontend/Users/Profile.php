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

namespace App\Site\Controllers\Frontend\Users;

use App\Site\Models\Role;
use App\Site\Models\User;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Models\AccountModel;
use App\Base\Abstracts\Controllers\LoggedUserFormPage;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "Profile" Logged Page
 */
class Profile extends LoggedUserFormPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'users/profile';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'profile';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTemplateData(): array
    {
        $this->template_data += [
            'current_user' => $this->getCurrentUser(),
        ];
        return $this->template_data;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array $form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $user = $this->getCurrentUser();

        $roles = [];
        foreach (Role::getCollection() as $item) {
            /** @var Role $item */
            $roles[$item->getId()] = $item->getName();
        }
        $languages = $this->getUtils()->getSiteLanguagesSelectOptions();

        $user_username = $user_roleid = $user_email = $user_nickname = $user_locale = '';
        if ($user instanceof AccountModel && $user->isLoaded()) {
            $user_username = $user->getUsername();
            $role = $user->getRole();

            if ($role instanceof Role) {
                $user_roleid = $role->getId();
            }

            $user_email = $user->getEmail();
            $user_nickname = $user->getNickname();
            $user_locale = $user->getLocale();
        }

        $form->addField('action', [
            'type' => 'value',
            'value' => $this->getRequest()->get('action'),
        ]);

        switch ($this->getRequest()->get('action')) {
            case 'edit':
                $form->addField('email', [
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
            case 'change_pass':
                $form->addField('password', [
                    'type' => 'password',
                    'with_confirm' => true,
                    'with_strength_check' => true,
                    'title' => 'Change Password',
                    'default_value' => '',
                    'validate' => [],
                ]);

                $this->addSubmitButton($form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array $form_state
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
     * @param array $form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var User $user
         */
        $user = $this->getCurrentUser();

        $values = $form->values();
        switch ($values['action']) {
            case 'edit':
                $user->email = $values['email'];
                $user->nickname = $values['nickname'];
                $user->locale = $values['locale'];

                $user->persist();
                break;
            case 'change_pass':
                $user->password = $this->getUtils()->getEncodedPass($values['password']);

                $user->persist();
                break;
        }

        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('User profile');
    }
}
