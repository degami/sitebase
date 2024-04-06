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

/**
 * "2Fa" Page
 */
class TwoFa extends LoggedUserFormPage
{
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
        return 'view_logged_site';
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
        $qrCodeUrl = $this->getGoogleAuthenticator()->getQRCodeGoogleUrl($this->getEnv('APPNAME'), $secret);

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
        $user2Fa = User2Fa::getCollection()->where(['user_id' => $user->getId(), 'website_id' => $this->getCurrentWebsiteId()])->getFirst();

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
        }

        return $secret;
    }
}
