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

use App\Site\Models\User;
use App\Site\Models\User2Fa;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\LoggedUserFormPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Routing\RouteInfo;
use Symfony\Component\HttpFoundation\Request;
use Psr\Container\ContainerInterface;

/**
 * "2Fa" Page
 */
class TwoFa extends LoggedUserFormPage
{
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
    }

    /**
     * {@inheritdoc}
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
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        $this->template_data += [
            'pageIntro' => $this->getUtils()->translate('2 Factor Authentication needed', locale: $this->getCurrentLocale()),
        ];

        return $this->template_data;
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
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $userHasPassed2Fa = ($this->getCurrentUser()->getUser2Fa() != null);
        $secret = $form->getSessionBag()?->googleAuthenticatorSecret ?? null;
        if (!$secret) {
            $secret = $this->getSecret($this->getCurrentUser());
        }
        $form->getSessionBag()->googleAuthenticatorSecret = $secret;

        $qrCodeIdentifier = $this->getEnv('APPNAME') . ': ' . $this->getCurrentUser()?->getUsername();

        $qrCodeUrl = $this->getGoogleAuthenticator()->getQRCodeGoogleUrl($qrCodeIdentifier, $secret);

        $table = new FAPI\Containers\TableContainer([], 'otp_table');

        $tableRow = $table->addRow();
        $form->addCss("#twofa table tbody, #twofa table tr {display: flex; width: 100%;}");
        $form->addCss("#twofa table td {width: 100%;}");

        if (!$userHasPassed2Fa) {
            // show qrcode only if user has not a secret code in db table
            $tableRow->addMarkup('<div>'.$this->getUtils()->translate('Add to your Google Authenticator App with QR-Code:', locale: $this->getCurrentLocale()).'</div><br /><img src="'.$qrCodeUrl.'" />');
        }

        $tableRow->addField('otp', [
            'type' => 'textfield',
            'title' => 'Enter your OTP',
            'default_value' => '',
            'validate' => ['required'],
        ] + (!$userHasPassed2Fa ? [] : ['description' => $this->getUtils()->translate('2 Factor authentication is already configured - enter the "%s" OTP code', [$qrCodeIdentifier])]));

        $form->addField($table->getName(), $table);
        $form->addField('secret', [
            'type' => 'value',
            'value' => $secret,
        ]);

        $this->addSubmitButton($form);

        

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

        $secret = $this->getCurrentUser()->getUser2Fa()?->getSecret() ?? $values['secret'];

        $isValid = $this->getGoogleAuthenticator()->verifyCode($secret, $values['otp_table']['otp'], 2);   // 2 = 2*30sec clock tolerance
        if ($isValid) {
            //@todo inject passed2fa = true into userdata jwt claim
            //@see \App\Site\Models\User::getJWT
            //@see \App\Base\Traits\PageTrait::getToken

            if ($this->getCurrentUser()->getUser2Fa() == null) {
                //store new User2Fa to database
                $user2Fa = User2Fa::new();
                $user2Fa->setUserId($this->getCurrentUser()->getId());
                $user2Fa->setWebsiteId($this->getCurrentWebsiteId());
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
        $goto_url = $this->getUrl("frontend.users.profile");

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
        /*$user2Fa = User2Fa::getCollection()->where(['user_id' => $user->getId(), 'website_id' => $this->getCurrentWebsiteId()])->getFirst();

        if (!$user2Fa) {
            $user2Fa = User2Fa::new();
            $user2Fa->setUserId($user->getId());
            $user2Fa->setWebsiteId($this->getCurrentWebsiteId());
        }

        if ($user2Fa->getSecret()) {
            $secret = $user2Fa->getSecret();
        } else {
            $secret = $this->getGoogleAuthenticator()->createSecret();
            $user2Fa->setSecret($secret);
            $user2Fa->persist();
        }*/

        $secret = null;
        if ($user->getUser2Fa()?->getSecret()) {
            $secret = $user->getUser2Fa()->getSecret();
        } else {
            $secret = $this->getGoogleAuthenticator()->createSecret();
        }

        return $secret;
    }
}
