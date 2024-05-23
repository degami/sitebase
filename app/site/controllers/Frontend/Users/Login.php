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

use App\Base\Abstracts\Controllers\BasePage;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FormPage;
use App\Base\Traits\FrontendTrait;
use App\Site\Models\User;
use App\Base\Exceptions\PermissionDeniedException;
use DI\DependencyException;
use DI\NotFoundException;
use Lcobucci\JWT\Parser;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login Page
 */
class Login extends FormPage
{
    use FrontendTrait;

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
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
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        return $this->template_data;
    }

    /**
     * {@inheritdocs}
     *
     * @return bool
     */
    public function showMenu(): bool
    {
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @return bool
     */
    public function showBlocks(): bool
    {
        return true;
    }

    /**
     * {@inheritdocs}
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
     * {@inheritdocs}
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
        return $form
            ->setFormId('login')
            ->addField('username', [
                'title' => $this->getUtils()->translate('Username', $this->getCurrentLocale()),
                'type' => 'textfield',
                'validate' => ['required'],
                'attributes' => ['placeholder' => $this->getUtils()->translate('Username', $this->getCurrentLocale())],
            ])->addField('password', [
                'title' => $this->getUtils()->translate('Password', $this->getCurrentLocale()),
                'type' => 'password',
                'validate' => ['required'],
                'attributes' => ['placeholder' => $this->getUtils()->translate('Password', $this->getCurrentLocale())],
            ])->addField('button', [
                'type' => 'submit',
                'value' => $this->getUtils()->translate('Login', $this->getCurrentLocale()),
                'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
            ]);
    }

    /**
     * {@inheritdocs}
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
            $user = $this->containerCall([User::class, 'loadByCondition'], ['condition' => [
                'username' => $values['username'],
                'password' => $this->getUtils()->getEncodedPass($values['password']),
            ]]);

            $form_state['logged_user'] = $user;

            // dispatch "user_logged_in" event
            $this->getApp()->event('user_logged_in', [
                'logged_user' => $form_state['logged_user']
            ]);
        } catch (\Exception $e) {
            return $this->getUtils()->translate("Invalid username / password", $this->getCurrentLocale());
        }

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
        return $this->getUtils()->translate('Login');
    }
}
