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

use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Exceptions\FormException;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \App\Base\Abstracts\Controllers\AdminFormPage;
use \App\Site\Models\RequestLog;
use \Degami\PHPFormsApi as FAPI;
use \App\App;

/**
 * Class BanIP
 * @package App\Site\Controllers\Admin
 */
class BanIP extends AdminFormPage
{
    /**
     * @var array blocked ips list
     */
    protected $blocked_ips = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @throws FormException
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    public function __construct(ContainerInterface $container, Request $request)
    {
        parent::__construct($container, $request);
    }


    /**
     * {@intheritdocs}
     *
     * @return Response|self
     * @throws BasicException
     */
    protected function beforeRender()
    {
        if ($this->getRequest()->get('ip') == null) {
            $this->addFlashMessage('warning', 'Missing IP address');
            return $this->doRedirect($this->getUrl('admin.dashboard'));
        }

        return parent::beforeRender();
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'form_admin_page';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_site';
    }

    /**
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $form->addField('ip', [
            'type' => 'hidden',
            'default_value' => $this->getRequest()->get('ip'),
        ])
        ->addField('delete_fromdb', [
            'title' => 'Remove also request log entries',
            'type' => 'switchbox',
            'yes_value' => 1,
            'no_value' => 0,
            'default_value' => 1,
        ]);

        $this->fillConfirmationForm("Do you really want to ban IP: " . $this->getRequest()->get('ip'), $form, $this->getUrl('admin.dashboard'));

        return $form;
    }

    /**
     * validates form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        return true;
    }

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed|Response
     * @throws BasicException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        $values = $form->getValues();

        $file = $this->getBanFileName();
        if (is_file($file)) {
            $this->blocked_ips = include($file);
            if (!is_array($this->blocked_ips)) {
                $this->blocked_ips = [$this->blocked_ips];
            }
        }

        $this->blocked_ips[] = $values->ip;

        if ($values->delete_fromdb == 1) {
            $list = $this->getContainer()->call([RequestLog::class, 'where'], ['condition' => ['ip_address' => $values->ip]]);
            foreach ($list as $elem) {
                $elem->delete();
            }
        }

        $this->blocked_ips = array_unique($this->blocked_ips);
        file_put_contents($this->getBanFileName(), $this->getBanFileContents());

        $this->addFlashMessage('info', 'IP: ' . $values->ip . ' has been banned!');
        return $this->doRedirect($this->getUrl('admin.dashboard'));
    }

    /**
     * gets ban IPS file path
     *
     * @return string
     */
    protected function getBanFileName()
    {
        return App::getDir(App::CONFIG) . DS . 'blocked_ips.php';
    }

    /**
     * gets new ban IPS file contents
     *
     * @return string
     */
    protected function getBanFileContents()
    {
        return "<?php\n\nreturn \$blocked_ips = [\n" . implode("", array_map(function ($el) {
                return "  '$el',\n";
            }, $this->blocked_ips)) . "];\n";
    }
}
