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
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\Controllers\FormPage;
use \App\Base\Traits\FrontendTrait;
use \App\Site\Models\User;
use \App\Base\Exceptions\PermissionDeniedException;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

/**
 * Login Page
 */
class Login extends FormPage
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
        return 'login';
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
        return 'login';
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
     * @return Login|RedirectResponse|Response
     * @throws BasicException
     * @throws PermissionDeniedException
     */
    protected function beforeRender()
    {
        if (!$this->getEnv('ENABLE_LOGGEDPAGES')) {
            throw new PermissionDeniedException();
        }

        if ($this->isSubmitted()) {
            $result = $this->templateData['form']->getSubmitResults(static::class . '::formSubmitted');
            $token = $this->getContainer()->get('jwt:parser')->parse($result);

            $goto_url = $this->getUrl("frontend.users.profile");

            if ($this->getRequest()->get('dest')) {
                $tmp = explode(':', base64_decode($this->getRequest()->get('dest')));

                if (count($tmp) >= 2 && end($tmp) == sha1($this->getEnv('SALT'))) {
                    $goto_url = implode(':', array_slice($tmp, 0, count($tmp) - 1));
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
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
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
     * @return boolean|string
     * @throws BasicException
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();

        $user = $this->getDb()->user()
            ->where('username', $values['username'])
            ->where('password', $this->getUtils()->getEncodedPass($values['password']))
            ->fetch();

        if (!$user) {
            return $this->getUtils()->translate("Invalid username / password", $this->getCurrentLocale());
        } else {
            $form_state['logged_user'] = $this->getContainer()->make(User::class, ['dbrow' => $user]);

            // dispatch "user_logged_in" event
            $this->getApp()->event('user_logged_in', [
                'logged_user' => $form_state['logged_user']
            ]);
        }

        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $logged_user = $form_state['logged_user'];
        return "" . $logged_user->getJWT();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     */
    public function getRouteName()
    {
        return $this->getUtils()->translate('Login');
    }
}
