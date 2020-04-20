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
namespace App\Site\Controllers\Frontend\Users;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\Models\AccountModel;
use \App\Base\Abstracts\Controllers\LoggedUserFormPage;
use \Degami\PHPFormsApi as FAPI;

/**
 * "Profile" Logged Page
 */
class Profile extends LoggedUserFormPage
{
    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'users/profile';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'profile';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'view_logged_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        $this->templateData += [
            'current_user' => $this->getCurrentUser(),
        ];
        return $this->templateData;
    }

    /**
     * {@inheritdocs}
     *
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $user = $this->getCurrentUser();

        $roles = [];
        foreach ($this->getDb()->role()->fetchAll() as $rolerow) {
            $roles[$rolerow->id] = $rolerow->name;
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

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $this->getRequest()->get('action'),
            ]
        );

        switch ($this->getRequest()->get('action')) {
            case 'edit':
                $form->addField(
                    'email',
                    [
                    'type' => 'email',
                    'title' => 'Email',
                    'default_value' => $user_email,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'nickname',
                    [
                    'type' => 'textfield',
                    'title' => 'Nickname',
                    'default_value' => $user_nickname,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'locale',
                    [
                    'type' => 'select',
                    'title' => 'Locale',
                    'default_value' => $user_locale,
                    'options' => $languages,
                    'validate' => ['required'],
                    ]
                );

                $this->addSubmitButton($form);
                break;
            case 'change_pass':
                $form->addField(
                    'password',
                    [
                    'type' => 'password',
                    'with_confirm' => true,
                    'with_strength_check' => true,
                    'title' => 'Change Password',
                    'default_value' => '',
                    'validate' => [],
                    ]
                );

                $this->addSubmitButton($form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     *
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
     * @return mixed|Response
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
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
}
