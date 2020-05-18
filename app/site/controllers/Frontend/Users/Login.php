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

use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\Controllers\FormPage;
use \Symfony\Component\HttpFoundation\RedirectResponse;
use \App\Base\Traits\FrontendTrait;
use \Gplanchat\EventManager\Event;
use \App\App;
use \App\Site\Models\User;
use \App\Base\Exceptions\NotFoundException;
use \App\Base\Exceptions\PermissionDeniedException;

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
        return (trim(getenv('LOGGEDPAGES_GROUP')) != null) ? '/'.getenv('LOGGEDPAGES_GROUP') : null;
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
     * @return self
     */
    protected function beforeRender()
    {
        if (!$this->getEnv('ENABLE_LOGGEDPAGES')) {
            throw new PermissionDeniedException();
        }

        if ($this->isSubmitted()) {
            $result = $this->templateData['form']->getSubmitResults(static::class.'::formSubmitted');
            $token = $this->getContainer()->get('jwt:parser')->parse($result);

            return RedirectResponse::create(
                $this->getUrl("frontend.users.profile"),
                302,
                [
                "Authorization" => $token,
                "Set-Cookie" => "Authorization=".$token
                ]
            );
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
}
