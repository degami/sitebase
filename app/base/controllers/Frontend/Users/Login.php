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

namespace App\Base\Controllers\Frontend\Users;

use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FormPage;
use App\Base\Traits\FrontendPageTrait;
use App\Base\Models\User;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Exceptions\NotFoundException as ExceptionsNotFoundException;
use DI\DependencyException;
use DI\NotFoundException;
use Lcobucci\JWT\Parser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login Page
 */
class Login extends FormPage
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
        return 'login';
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
        return 'login';
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
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        if (!$this->getEnv('ENABLE_LOGGEDPAGES')) {
            throw new PermissionDeniedException();
        }

        if ($this->isSubmitted()) {
            $result = $this->template_data['form']->getSubmitResults(static::class . '::formSubmitted');
            /** @var Parser $parser */
            $parser = $this->getContainer()->get('jwt:configuration')->parser();
            $token = $parser->parse($result)->toString();


            if ($this->getEnv('USE2FA_USERS') && ($this->current_user?->passed2fa ?? false) != true){
                $goto_url = $this->getUrl('frontend.users.twofa');

                if ($this->getRequest()->get('dest')) {
                    $goto_url .= '?dest'.$this->getRequest()->get('dest');
                }
            } else {
                $goto_url = $this->getUrl("frontend.users.profile");

                if ($this->getRequest()->get('dest')) {
                    $tmp = explode(':', base64_decode($this->getRequest()->get('dest')));
    
                    if (count($tmp) >= 2 && end($tmp) == sha1($this->getEnv('SALT'))) {
                        $goto_url = implode(':', array_slice($tmp, 0, count($tmp) - 1));
                    }
                }
            }

            return $this->doRedirect($goto_url, [
                "Authorization" => $token,
                "Set-Cookie" => "Authorization=" . $token
            ]);
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
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        return $form
            ->setFormId('login')
            ->addField('username', [
                'title' => $this->getUtils()->translate('Username', locale: $this->getCurrentLocale()),
                'type' => 'textfield',
                'validate' => ['required'],
                'attributes' => ['placeholder' => $this->getUtils()->translate('Username', locale: $this->getCurrentLocale())],
            ])->addField('password', [
                'title' => $this->getUtils()->translate('Password', locale: $this->getCurrentLocale()),
                'type' => 'password',
                'validate' => ['required'],
                'attributes' => ['placeholder' => $this->getUtils()->translate('Password', locale: $this->getCurrentLocale())],
            ])->addField('button', [
                'type' => 'submit',
                'value' => $this->getUtils()->translate('Login', locale: $this->getCurrentLocale()),
                'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
            ]);
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

        try {
            /** @var User|null $user */
            $user = $this->getUtils()->getUserByCredentials($values['username'], $values['password']);


            if (!$user) {
                throw new ExceptionsNotFoundException('User not found');
            }

            $form_state['logged_user'] = $user;

            $user->unlock()->persist();

            // dispatch "user_logged_in" event
            $this->getApp()->event('user_logged_in', [
                'logged_user' => $form_state['logged_user']
            ]);
        } catch (\Exception $e) {

            try {
                /** @var User $user */
                $user = $this->containerCall([User::class, 'loadByCondition'], ['condition' => [
                    'username' => $values['username'],
                ]]);

                $user->incrementLoginTries()->persist();

                if ($user->getLocked() == true) {
                    return $this->getUtils()->translate("Account locked. try again lated.", locale: $this->getCurrentLocale());
                }

            } catch (\Exception $e) {}

            return $this->getUtils()->translate("Invalid username / password", locale: $this->getCurrentLocale());
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
     * @throws \Exception
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /** @var User $logged_user */
        $logged_user = $form_state['logged_user'];
        $logged_user->getUserSession()->addSessionData('last_login', new \DateTime())->persist();

        return "" . $logged_user->getJWT();
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
        return $this->getUtils()->translate('Login', locale: $this->getCurrentLocale());
    }
}
