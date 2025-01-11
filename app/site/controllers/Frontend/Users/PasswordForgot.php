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

namespace App\Site\Controllers\Frontend\Users;

use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FormPage;
use App\Base\Traits\FrontendPageTrait;
use App\Site\Models\User;
use App\Base\Exceptions\PermissionDeniedException;
use Symfony\Component\HttpFoundation\Response;
use Throwable;

/**
 * PasswordForgot Page
 */
class PasswordForgot extends FormPage
{
    use FrontendPageTrait;

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getEnv('ENABLE_LOGGEDPAGES'));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'password_forgot';
    }

    /**
     * gets route group
     *
     * @return string|null
     */
    public static function getRouteGroup(): ?string
    {
        return (trim(getenv('LOGGEDPAGES_GROUP')) != null) ? '/' . getenv('LOGGEDPAGES_GROUP') : null;
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'password_forgot';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs(): array
    {
        return ['GET', 'POST'];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getTemplateData(): array
    {
        return $this->template_data;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function showMenu(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function showBlocks(): bool
    {
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @return BasePage|Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        if (!$this->getEnv('ENABLE_LOGGEDPAGES')) {
            throw new PermissionDeniedException();
        }

        if ($this->isSubmitted()) {
            if ($this->getForm()->getFormId() == 'changepass') {
                $user_model = $this->template_data['form']->getSubmitResults(static::class . '::formSubmitted');
                $token = $user_model->getJWT();

                return $this->doRedirect(
                    $this->getUrl("frontend.users.profile"),
                    [
                        "Authorization" => $token,
                        "Set-Cookie" => "Authorization=" . $token
                    ]
                );
            } else {
                $this->template_data['result'] = '<h2>' . $this->getUtils()->translate('Confirmation email sent', locale: $this->getCurrentLocale()) . '</h2>';
            }
        }

        return parent::beforeRender();
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
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        if ($this->getRequest()->get('confirmation_code') && ($user = $this->containerCall([User::class, 'loadBy'], ['field' => 'confirmation_code', 'value' => $this->getRequest()->get('confirmation_code')])) && $user->getId()) {
            $form
                ->setFormId('changepass')
                ->addField('user_id', [
                    'type' => 'hidden',
                    'default_value' => $user->getId(),
                ])
                ->addMarkup($this->getUtils()->translate('Change your password', locale: $this->getCurrentLocale()) . ' <strong>' . $user->getNickname() . '</strong>')
                ->addField('password', [
                    'title' => $this->getUtils()->translate('Password', locale: $this->getCurrentLocale()),
                    'type' => 'password',
                    'with_confirm' => true,
                    'validate' => ['required'],
                    'attributes' => ['placeholder' => $this->getUtils()->translate('Password', locale: $this->getCurrentLocale())],
                ]);
        } else {
            $form
                ->setFormId('confirmemail')
                ->addField('email', [
                    'title' => $this->getUtils()->translate('Email', locale: $this->getCurrentLocale()),
                    'type' => 'textfield',
                    'validate' => ['required', 'email'],
                    'attributes' => ['placeholder' => $this->getUtils()->translate('Email', locale: $this->getCurrentLocale())],
                ]);
        }

        $form->addField('button', [
            'type' => 'submit',
            'value' => $this->getUtils()->translate('Submit', locale: $this->getCurrentLocale()),
            'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
        ]);

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        $values = $form->values();


        if ($form->getFormId() == 'confirmemail') {
            try {
                /** @var User $user */
                $user = $this->containerCall([User::class, 'loadBy'], ['field' => 'email', 'value' => $values['email']]);
                $form_state['found_user'] = $user;
            } catch (\Exception $e) {
                return $this->getUtils()->translate("Invalid email", locale: $this->getCurrentLocale());
            }
        }

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
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        $values = $form->values();

        $user_model = null;

        if ($form->getFormId() == 'confirmemail') {
            if ($form_state['found_user']) {
                $user_model = $form_state['found_user'];
                $user_model->confirmation_code = $this->getUtils()->randString(20);

                $url = $this->getControllerUrl() . '?confirmation_code=' . $user_model->confirmation_code;

                $this->getUtils()->queueInternalMail(
                    $this->getSiteData()->getSiteEmail(),
                    $user_model->getEmail(),
                    'Password Forgot',
                    'Click this link to change your password. <br /><a href="' . $url . '">' . $url . '</a>'
                );
            }
        } else {
            $user_model = $this->containerCall([User::class, 'load'], ['id' => $values['user_id']]);
            $user_model->password = $this->getUtils()->getEncodedPass($values['password']);
            $user_model->confirmation_code = null;
        }

        if ($user_model instanceof User) {
            $user_model->persist();
        }

        return $user_model;
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
        return $this->getUtils()->translate('Forgot Password?', locale: $this->getCurrentLocale());
    }
}
