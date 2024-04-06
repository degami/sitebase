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

namespace App\Site\Controllers\Admin;

use App\Site\Models\User;
use App\Site\Models\User2Fa;
use League\Plates\Template\Template;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Request;
use Psr\Container\ContainerInterface;
use Degami\PHPFormsApi as FAPI;
use App\App;

/**
 * "2Fa" Page
 */
class TwoFa extends AdminFormPage
{
    /**
     * {@inheritdocs}
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
        if (!$this->getTemplates()->getFolders()->exists('frontend')) {
            $this->getTemplates()->addFolder('frontend', App::getDir(App::TEMPLATES) . DS . 'frontend');
        }
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'twoFa';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return '2fa';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        $this->template_data += [
            'pageIntro' => $this->getUtils()->translate('2 Factor Authentication needed'),
        ];

        return $this->template_data;
    }


    /**
     * {@inheritdocs}
     *
     * @return bool
     */
    public function showMenu(): bool
    {
        return false;
    }

    /**
     * {@inheritdocs}
     *
     * @return bool
     */
    public function showBlocks(): bool
    {
        return false;
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


        $template->start('head_scripts');
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
        $secret = $this->getSecret($this->getCurrentUser());
        $qrCodeUrl = $this->getGoogleAuthenticator()->getQRCodeGoogleUrl($this->getEnv('APPNAME'). ' '.$this->getEnv('ADMINPAGES_GROUP'), $secret);

        $table = new FAPI\Containers\TableContainer([], 'otp_table');

        $table->addRow()
        ->addMarkup('<div>'.$this->getUtils()->translate('Add to your Google Authenticator App with QR-Code:').'</div><br /><img src="'.$qrCodeUrl.'" />')
        ->addField('otp', [
            'type' => 'textfield',
            'title' => 'Enter your OTP',
            'default_value' => '',
            'validate' => ['required'],
        ]);

        $form->addField($table->getName(), $table);

        $this->addSubmitButton($form);

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
        $values = $form->values();
        $secret = $this->getSecret($this->getCurrentUser());
        $isValid = $this->getGoogleAuthenticator()->verifyCode($secret, $values['otp_table']['otp'], 2);   // 2 = 2*30sec clock tolerance
        if ($isValid) {
            //@todo inject passed2fa = true into userdata jwt claim
            //@see \App\Site\Models\User::getJWT
            //@see \App\Base\Traits\PageTrait::getToken
        }
        return $isValid ?: $this->getUtils()->translate('Otp Code is invalid', $this->getCurrentLocale()); 
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
        $goto_url = $this->getUrl("admin.dashboard");

        if ($this->getRequest()->get('dest')) {
            $tmp = explode(':', base64_decode($this->getRequest()->get('dest')));

            if (count($tmp) >= 2 && end($tmp) == sha1($this->getEnv('SALT'))) {
                $goto_url = implode(':', array_slice($tmp, 0, count($tmp) - 1));
            }
        }

        $token = $this->getCurrentUser()->getJWT(['passed2fa' => true]);

        return $this->doRedirect($goto_url, [
            "Authorization" => $token,
            "Set-Cookie" => "Authorization=" . $token
        ]);
    }

    protected function getSecret(User $user) : string
    {
            /** @var User2Fa $user2Fa */
        $user2Fa = User2Fa::getCollection()->where(['user_id' => $user->getId(), 'website_id' => null])->getFirst();

        if (!$user2Fa) {
            $user2Fa = User2Fa::new();
            $user2Fa->setUserId($user->getId());
            $user2Fa->setWebsiteId(null);
        }

        if ($user2Fa->getSecret()) {
            $secret = $user2Fa->getSecret();
        } else {
            $secret = $this->getGoogleAuthenticator()->createSecret();
            $user2Fa->setSecret($secret);
            $user2Fa->persist();
        }

        return $secret;
    }
}
