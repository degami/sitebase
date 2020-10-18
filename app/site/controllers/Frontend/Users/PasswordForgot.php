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

use Degami\Basics\Exceptions\BasicException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\Controllers\FormPage;
use \App\Base\Traits\FrontendTrait;
use \App\Site\Models\User;
use \App\Base\Exceptions\PermissionDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * PasswordForgot Page
 */
class PasswordForgot extends FormPage
{
    use FrontendTrait;

    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * @var string locale
     */
    protected $locale = null;

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'password_forgot';
    }

    /**
     * gets route group
     *
     * @return string
     */
    public static function getRouteGroup()
    {
        return (trim(getenv('LOGGEDPAGES_GROUP')) != null) ? '/' . getenv('LOGGEDPAGES_GROUP') : null;
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'password_forgot';
    }

    /**
     * returns valid route HTTP verbs
     *
     * @return array
     */
    public static function getRouteVerbs()
    {
        return ['GET', 'POST'];
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData()
    {
        return $this->templateData;
    }

    /**
     * {@inheritdocs}
     *
     * @return boolean
     */
    public function showMenu()
    {
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @return boolean
     */
    public function showBlocks()
    {
        return true;
    }


    /**
     * {@inheritdocs}
     *
     * @return PasswordForgot|RedirectResponse|Response
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender()
    {
        if (!$this->getEnv('ENABLE_LOGGEDPAGES')) {
            throw new PermissionDeniedException();
        }

        if ($this->isSubmitted()) {
            if ($this->getForm()->getFormId() == 'changepass') {
                $user_model = $this->templateData['form']->getSubmitResults(static::class . '::formSubmitted');
                $token = $user_model->getJWT();

                return $this->doRedirect(
                    $this->getUrl("frontend.users.profile"),
                    [
                        "Authorization" => $token,
                        "Set-Cookie" => "Authorization=" . $token
                    ]
                );
            } else {
                $this->templateData['result'] = '<h2>' . $this->getUtils()->translate('Confirmation email sent', $this->getCurrentLocale()) . '</h2>';
            }
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
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        if ($this->getRequest()->get('confirmation_code') && ($user = $this->getContainer()->call([User::class, 'loadBy'], ['field' => 'confirmation_code', 'value' => $this->getRequest()->get('confirmation_code')])) && $user->getId()) {
            $form
                ->setFormId('changepass')
                ->addField('user_id', [
                    'type' => 'hidden',
                    'default_value' => $user->getId(),
                ])
                ->addMarkup($this->getUtils()->translate('Change your password', $this->getCurrentLocale()) . ' <strong>' . $user->getNickname() . '</strong>')
                ->addField('password', [
                    'title' => $this->getUtils()->translate('Password', $this->getCurrentLocale()),
                    'type' => 'password',
                    'with_confirm' => true,
                    'validate' => ['required'],
                    'attributes' => ['placeholder' => $this->getUtils()->translate('Password', $this->getCurrentLocale())],
                ]);
        } else {
            $form
                ->setFormId('confirmemail')
                ->addField('email', [
                    'title' => $this->getUtils()->translate('Email', $this->getCurrentLocale()),
                    'type' => 'textfield',
                    'validate' => ['required', 'email'],
                    'attributes' => ['placeholder' => $this->getUtils()->translate('Email', $this->getCurrentLocale())],
                ]);
        }

        $form->addField('button', [
            'type' => 'submit',
            'value' => $this->getUtils()->translate('Submit', $this->getCurrentLocale()),
            'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
        ]);

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return boolean|string
     * @throws BasicException
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();


        if ($form->getFormId() == 'confirmemail') {
            $user = $this->getDb()->user()
                ->where('email', $values['email'])
                ->fetch();

            if (!$user) {
                return $this->getUtils()->translate("Invalid email", $this->getCurrentLocale());
            } else {
                $form_state['found_user'] = $this->getContainer()->make(User::class, ['dbrow' => $user]);
            }
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
     * @throws PhpfastcacheSimpleCacheException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        $user_model = null;

        if ($form->getFormId() == 'confirmemail') {
            if ($form_state['found_user']) {
                $user_model = $form_state['found_user'];
                $user_model->confirmation_code = $this->getUtils()->randString(20);

                $url = $this->getControllerUrl() . '?confirmation_code=' . $user_model->confirmation_code;

                $this->getUtils()->addQueueMessage(
                    'internal_mail',
                    [
                        'from' => $this->getSiteData()->getSiteEmail(),
                        'to' => $user_model->getEmail(),
                        'subject' => 'Password Forgot',
                        'body' => 'Click this link to change your password. <br /><a href="' . $url . '">' . $url . '</a>',
                    ]
                );
            }
        } else {
            $user_model = $this->getContainer()->call([User::class, 'load'], ['id' => $values['user_id']]);
            $user_model->password = $this->getUtils()->getEncodedPass($values['password']);
            $user_model->confirmation_code = null;
        }

        if ($user_model instanceof User) {
            $user_model->persist();
        }

        return $user_model;
    }
}
