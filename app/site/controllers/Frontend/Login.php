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
namespace App\Site\Controllers\Frontend;

use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\FormPage;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \App\Base\Traits\AdminTrait;
use \Gplanchat\EventManager\Event;
use \App\App;
use \App\Site\Models\User;

/**
 * Login Page
 */
class Login extends FormPage
{
    use AdminTrait;

    /**
     * @var array template data
     */
    protected $templateData = [];

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
    public function getRouteVerbs()
    {
        return ['GET','POST'];
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
        return false;
    }

    /**
     * {@inheritdocs}
     *
     * @return boolean
     */
    public function showBlocks()
    {
        return false;
    }


    /**
     * {@inheritdocs}
     *
     * @return self
     */
    protected function beforeRender()
    {
        if ($this->isSubmitted()) {
            $result = $this->templateData['form']->getSubmitResults(static::class.'::formSubmitted');
            $token = $this->getContainer()->get('jwt:parser')->parse($result);

            return RedirectResponse::create(
                $this->getUrl("admin.dashboard"),
                302,
                [
                "Authorization" => $token,
                "Set-Cookie" => "Authorization=".$token
                ]
            );
        } else {
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
        }
        return parent::beforeRender();
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        return $form->setFormId('login')
            ->addField(
                'username',
                [
                'title' => $this->getUtils()->translate('Username', $this->getCurrentLocale()),
                'type' => 'textfield',
                'validate' => ['required'],
                'attributes' => ['placeholder' => $this->getUtils()->translate('Username', $this->getCurrentLocale())],
                ]
            )
            ->addField(
                'password',
                [
                'title' => $this->getUtils()->translate('Password', $this->getCurrentLocale()),
                'type' => 'password',
                'validate' => ['required'],
                'attributes' => ['placeholder' => $this->getUtils()->translate('Password', $this->getCurrentLocale())],
                ]
            )
            ->addField(
                'button',
                [
                'type' => 'submit',
                'value' => $this->getUtils()->translate('Login', $this->getCurrentLocale()),
                'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                ]
            );
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
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
            $this->getApp()->event(
                'user_logged_in',
                [
                'logged_user' => $form_state['logged_user']
                ]
            );
        }

        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $container = $this->getContainer();
        $logged_user = $form_state['logged_user'];
        $user_permissions = $logged_user->getRole()->getPermissionsArray();

        $token = $container->get('jwt:builder')
            ->setIssuer($container->get('jwt_issuer'))
            ->setAudience($container->get('jwt_audience'))
            ->setId($this->calcTokenId($logged_user->id, $logged_user->username), true)
                // Configures the id (jti claim), replicating as a header item
            ->setIssuedAt(time())
                // Configures the time that the token was issue (iat claim)
            ->setNotBefore(time())
                // Configures the time that the token can be used (nbf claim)
            ->setExpiration(time() + 3600)
                // Configures the expiration time of the token (exp claim)
            ->set('uid', $logged_user->id)
                // Configures a new claim, called "uid"
            ->set('username', $logged_user->username)
            ->set(
                'userdata',
                (object)[
                'id' => $logged_user->id,
                'username'=>$logged_user->username,
                'email'=>$logged_user->email,
                'nickname'=>$logged_user->nickname,
                'permissions'=>array_map(
                    function ($el) {
                        return $el->name;
                    },
                    $user_permissions
                )
                ]
            )
            ->getToken(); // Retrieves the generated token
        return "".$token;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getCurrentLocale()
    {
        return $this->getSiteData()->getDefaultLocale();
    }
}
