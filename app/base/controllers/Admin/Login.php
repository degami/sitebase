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

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Lcobucci\JWT\Parser;
use League\Plates\Template\Template;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use Degami\PHPFormsApi as FAPI;
use App\Base\Abstracts\Controllers\FormPage;
use App\Base\Traits\AdminTrait;
use App\App;
use App\Base\Exceptions\NotFoundException as ExceptionsNotFoundException;
use App\Base\Models\User;
use Exception;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login Page
 */
class Login extends FormPage
{
    use AdminTrait;

    /**
     * {@inheritdoc}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws FAPI\Exceptions\FormException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
        if (!$this->getTemplates()->getFolders()->exists('admin')) {
            $this->getTemplates()->addFolder('admin', App::getDir(App::TEMPLATES) . DS . 'admin');
        }
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
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public function showBlocks(): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getBaseTemplateData(): array
    {
        $out = parent::getBaseTemplateData();
        $out['body_class'] = $this->getHtmlRenderer()->getHtmlAdminClasses($this);

        $picsumKey = 'picsum.pictures.list';
        if ($this->getCache()->has($picsumKey)) {
            $picsumIds = $this->getCache()->get($picsumKey);
        } else {
            try {
                $picsumIds = json_decode($this->getUtils()->requestUrl('https://picsum.photos/v2/list?page'.rand(0, 10).'&limit=100'), true);
                $this->getCache()->set($picsumKey, $picsumIds, 3600);
            } catch (Exception $e) {
                $picsumIds = [];
            }
        }

        $image = current(array_slice($picsumIds, rand(0, count($picsumIds)), 1));
        if (!$image) {
            $image = $this->getDefaultSplashImage();
            $out['bgUrl'] = $image['url'];
            $out['bgAuthor'] = $image['author'];
            return $out;
        }

        $out['bgUrl'] = 'https://picsum.photos/id/' . $image['id'] . '/1920/1080';
        $out['bgAuthor'] = $image['author'];
        return $out;
    }

    /**
     * {@inheritfocs}
     *
     * @return Template
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function prepareTemplate(): Template
    {
        $template = $this->getTemplates()->make('admin::' . $this->getTemplateName());
        $template->data($this->getTemplateData() + $this->getBaseTemplateData());

        $template->data($this->getTemplateData() + $this->getBaseTemplateData());
        //$locale = $template->data()['locale'] ?? $this->getCurrentLocale();

        $this->getAssets()->addCss('html,body {height: 100%;}');
        $this->getAssets()->addCss('body {display: -ms-flexbox;display: flex;-ms-flex-align: center;align-items: center;padding-top: 40px;padding-bottom: 40px;background-color: #f5f5f5;}');
        $this->getAssets()->addCss('#login {width: 100%;max-width: 330px;padding: 15px;margin: auto;}');
        $this->getAssets()->addCss('#login label {display: none;}');
        $this->getAssets()->addCss('#login .checkbox {font-weight: 400;}');
        $this->getAssets()->addCss('#login .form-control {position: relative;box-sizing: border-box;height: auto;padding: 10px;font-size: 16px;}');
        $this->getAssets()->addCss('#login .form-control:focus {z-index: 2;}');
        $this->getAssets()->addCss('#login input[type="email"] {margin-bottom: -1px;border-bottom-right-radius: 0;border-bottom-left-radius: 0;}');
        $this->getAssets()->addCss('#login input[type="password"] {margin-bottom: 10px;border-top-left-radius: 0;border-top-right-radius: 0;}');
        $this->getAssets()->addCss('.content {background: transparent; border: 0;}');
        $this->getAssets()->addCss('.footer .copy {text-align: center;}');

        $template->start('head_styles');
        echo $this->getAssets()->renderHeadCSS();
        $template->stop();
        
        $template->start('head_scripts');
        echo $this->getAssets()->renderHeadJsScripts();
        echo $this->getAssets()->renderHeadInlineJS();
        $template->stop();

        $template->start('scripts');
        echo $this->getAssets()->renderPageInlineJS();
        $template->stop();

        $template->start('styles');
        echo $this->getAssets()->renderPageInlineCSS();
        $template->stop();

        return $template;
    }

    /**
     * {@inheritdoc}
     *
     * @return Login|RedirectResponse|Response
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        if ($this->isSubmitted()) {
            $result = $this->template_data['form']->getSubmitResults(static::class . '::formSubmitted');
            /** @var Parser $parser */
            $parser = $this->getContainer()->get('jwt:configuration')->parser();
            $token = $parser->parse($result)->toString();

            if ($this->getEnvironment()->getVariable('USE2FA_ADMIN') && $this->getAuth()->currentUserHasPassed2FA(true) != true) {
                $goto_url = $this->getAdminRouter()->getUrl('admin.twofa');

                if ($this->getRequest()->query->get('dest')) {
                    $goto_url .= '?dest'.$this->getRequest()->query->get('dest');
                }
            } else {
                $goto_url = $this->getAdminRouter()->getUrl("admin.dashboard");

                if ($this->getRequest()->query->get('dest')) {
                    $tmp = explode(':', base64_decode($this->getRequest()->query->get('dest')));
    
                    if (count($tmp) >= 2 && end($tmp) == sha1($this->getEnvironment()->getVariable('SALT'))) {
                        $goto_url = implode(':', array_slice($tmp, 0, count($tmp) - 1));
                    }
                }    
            }

            return $this->doRedirect($goto_url, [
                "Authorization" => $token,
                "Set-Cookie" => "Authorization=" . $token .";path=/;"
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
            ])
            ->addField('password', [
                'title' => $this->getUtils()->translate('Password', locale: $this->getCurrentLocale()),
                'type' => 'password',
                'validate' => ['required'],
                'attributes' => ['placeholder' => $this->getUtils()->translate('Password', locale: $this->getCurrentLocale())],
            ])
            ->addField('button', [
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

            if (!$form_state['logged_user']->checkPermission('administer_site')) {
                return $this->getUtils()->translate("Your account is not allowed to access", locale: $this->getCurrentLocale());
            }

            $user->unlock()->persist();

            // dispatch "user_logged_in" event
            $this->getApp()->event('user_logged_in', [
                'logged_user' => $form_state['logged_user']
            ]);
        } catch (\Exception $e) {

            try {
                /** @var User $user */
                $user = User::getCollection()->where(['username' => $values['username']])->getFirst();

                $user->incrementLoginTries()->persist();

                if ($user->getLocked() == true) {
                    return $this->getUtils()->translate("Account locked. try again lated.", locale: $this->getCurrentLocale());
                }

            } catch (Exception $e) {}

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
    public function getCurrentLocale(): string
    {
        return $this->getSiteData()->getDefaultLocale();
    }

    /**
     * specifies if this controller is eligible for full page cache
     *
     * @return bool
     */
    public function canBeFPC(): bool
    {
        return false;
    }

    protected function getDefaultSplashImage(): array
    {
        return [
            'url' => $this->getAssets()->assetUrl('/images/default_splash.jpg'),
            'author' => 'Picsum Photos',
        ];
    }
}
