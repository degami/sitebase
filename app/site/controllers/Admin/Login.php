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
namespace App\Site\Controllers\Admin;

use \Psr\Container\ContainerInterface;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Abstracts\Controllers\FormPage;
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
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, Request $request = null)
    {
        parent::__construct($container, $request);
        if (!$this->getTemplates()->getFolders()->exists('admin')) {
            $this->getTemplates()->addFolder('admin', App::getDir(App::TEMPLATES).DS.'admin');
        }
    }

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
     * {@inheritfocs}
     *
     * @return \League\Plates\Template\Template
     */
    protected function prepareTemplate()
    {
        $template = $this->getTemplates()->make('admin::'.$this->getTemplateName());
        $template->data($this->getTemplateData()+$this->getBaseTemplateData());

        $template->data($this->getTemplateData()+$this->getBaseTemplateData());
        $locale = $template->data()['locale'] ?? $this->getCurrentLocale();

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
     * @return self
     */
    protected function beforeRender()
    {
        if ($this->isSubmitted()) {
            $result = $this->templateData['form']->getSubmitResults(static::class.'::formSubmitted');
            $token = $this->getContainer()->get('jwt:parser')->parse($result);

            $goto_url = $this->getUrl("admin.dashboard");

            if ($this->getRequest()->get('dest')) {
                $tmp = explode(':', base64_decode($this->getRequest()->get('dest')));

                if (count($tmp) >= 2 && end($tmp) == sha1($this->getEnv('SALT'))) {
                    $goto_url = implode(':', array_slice($tmp, 0, count($tmp)-1));
                }
            }

            return RedirectResponse::create(
                $goto_url,
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

            if (!$form_state['logged_user']->checkPermission('administer_site')) {
                return $this->getUtils()->translate("Your account is not allowed to access", $this->getCurrentLocale());
            }

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
        $logged_user = $form_state['logged_user'];
        return "".$logged_user->getJWT();
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
