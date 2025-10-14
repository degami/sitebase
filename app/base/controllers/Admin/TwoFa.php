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

use App\Base\Models\User;
use App\Base\Models\User2Fa;
use League\Plates\Template\Template;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Base\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Request;
use Psr\Container\ContainerInterface;
use Degami\PHPFormsApi as FAPI;
use App\App;
use App\Base\Abstracts\Models\AccountModel;

/**
 * "2Fa" Page
 */
class TwoFa extends AdminFormPage
{
    public const ADMIN_WEBSITE_ID = 0;

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
        session_start(); // session is needed
        parent::__construct($container, $request, $route_info);
        if (!$this->getTemplates()->getFolders()->exists('frontend')) {
            $this->getTemplates()->addFolder('frontend', App::getDir(App::TEMPLATES) . DS . 'frontend');
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return bool
     */
    public static function isEnabled(): bool
    {
        return boolval(\App\App::getInstance()->getEnvironment()->getVariable('USE2FA_ADMIN'));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
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
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getTemplateData(): array
    {
        $this->template_data += [
            'pageIntro' => $this->getUtils()->translate('2 Factor Authentication needed', locale: $this->getCurrentLocale()),
        ];

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
     * @param FAPI\Form $form
     * @param array $form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $userHasPassed2Fa = ($this->getCurrentUser()->getUser2Fa(self::ADMIN_WEBSITE_ID) != null);
        $secret = $form->getSessionBag()?->googleAuthenticatorSecret ?? null;
        if (!$secret) {
            $secret = $this->getSecret($this->getCurrentUser());
        }
        $form->getSessionBag()->googleAuthenticatorSecret = $secret;

        $qrCodeIdentifier = $this->getEnvironment()->getVariable('APPNAME') . ' ' . $this->getEnvironment()->getVariable('ADMINPAGES_GROUP') . ': ' . $this->getCurrentUser()?->getUsername();

        $qrCodeUrl = $this->getGoogleAuthenticator()->getQRCodeGoogleUrl($qrCodeIdentifier, $secret);

        $table = new FAPI\Containers\TableContainer([], 'otp_table');
        $form->addCss("#twofa table tbody, #twofa table tr {display: flex; width: 100%;}");
        $form->addCss("#twofa table td {width: 100%;}");

        $tableRow = $table->addRow();

        if (!$userHasPassed2Fa) {
            // show qrcode only if user has not a secret code in db table
            $tableRow->addMarkup('<div>'.$this->getUtils()->translate('Add to your Google Authenticator App with QR-Code:', locale: $this->getCurrentLocale()).'</div><br /><img src="'.$qrCodeUrl.'" />');
        }

        $tableRow
        ->addField('otp', [
            'type' => 'otp',
            'title' => 'Enter your OTP',
            'default_value' => '',
            'otp_length' => 6,
            'show_characters' => true,
            'validate' => ['required', 'numeric'],
        ] + (!$userHasPassed2Fa ? [] : ['description' => $this->getUtils()->translate('2 Factor authentication is already configured - enter the "%s" OTP code', [$qrCodeIdentifier])]));

        $form->addField($table->getName(), $table);

        $form->addField('secret', [
            'type' => 'value',
            'value' => $secret,
        ]);

        $this->addSubmitButton($form, isConfirmation: true);

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array $form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        $values = $form->values();
        //$secret = $this->getSecret($this->getCurrentUser());

        $secret = $this->getCurrentUser()->getUser2Fa(self::ADMIN_WEBSITE_ID)?->getSecret() ?? $values['secret'];

        $isValid = $this->getGoogleAuthenticator()->verifyCode($secret, $values['otp_table']['otp'], 2);   // 2 = 2*30sec clock tolerance
        if ($isValid) {
            //@todo inject passed2fa = true into userdata jwt claim
            //@see \App\Base\Models\User::getJWT
            //@see \App\Base\Traits\PageTrait::getToken

            if ($this->getCurrentUser()->getUser2Fa(self::ADMIN_WEBSITE_ID) == null) {
                //store new User2Fa to database
                $user2Fa = User2Fa::new();
                $user2Fa->setUserId($this->getCurrentUser()->getId());
                $user2Fa->setWebsiteId(null); // as created in admin, can't tell which is the website_id
                $user2Fa->setSecret($secret);
                $user2Fa->persist();
            }

            unset($form->getSessionBag()->googleAuthenticatorSecret);
        }

        return $isValid ?: $this->getUtils()->translate('Otp Code is invalid', locale: $this->getCurrentLocale()); 
    }

    /**
     * {@inheritdoc}
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

        if ($this->getRequest()->query->get('dest')) {
            $tmp = explode(':', base64_decode($this->getRequest()->query->get('dest')));

            if (count($tmp) >= 2 && end($tmp) == sha1($this->getEnvironment()->getVariable('SALT'))) {
                $goto_url = implode(':', array_slice($tmp, 0, count($tmp) - 1));
            }
        }

        $token = $this->getCurrentUser()->getJWT(['passed2fa' => true]);

        return $this->doRedirect($goto_url, [
            "Authorization" => $token,
            "Set-Cookie" => "Authorization=" . $token
        ]);
    }

    protected function getSecret(AccountModel $user) : string
    {
        /** @var User2Fa $user2Fa */
        /*$user2Fa = User2Fa::getCollection()->where(['user_id' => $user->getId(), 'website_id' => null])->getFirst();

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
        }*/

        $secret = null;
        if ($user->getUser2Fa(self::ADMIN_WEBSITE_ID)?->getSecret()) {
            $secret = $user->getUser2Fa(0)->getSecret();
        } else {
            $secret = $this->getGoogleAuthenticator()->createSecret();
        }

        return $secret;
    }
}
