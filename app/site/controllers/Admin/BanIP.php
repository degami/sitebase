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

use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Exceptions\FormException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Site\Models\RequestLog;
use Degami\PHPFormsApi as FAPI;
use App\App;

/**
 * Class BanIP
 * @package App\Site\Controllers\Admin
 */
class BanIP extends AdminFormPage
{
    /**
     * @var array blocked ips list
     */
    protected array $blocked_ips = [];

    /**
     * {@intheritdocs}
     *
     * @return Response|self
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PermissionDeniedException
     */
    protected function beforeRender() : BasePage|Response
    {
        if ($this->getRequest()->get('ip') == null) {
            $this->addWarningFlashMessage('Missing IP address');
            return $this->doRedirect($this->getUrl('admin.dashboard'));
        }

        return parent::beforeRender();
    }


    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'form_admin_page';
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
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws FormException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state) : FAPI\Form
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
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state) : bool|string
    {
        return true;
    }

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
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
            foreach (RequestLog::getCollection()->where(['ip_address' => $values->ip]) as $elem) {
                $elem->delete();
            }
        }

        $this->blocked_ips = array_unique($this->blocked_ips);
        file_put_contents($this->getBanFileName(), $this->getBanFileContents());

        $this->addInfoFlashMessage('IP: ' . $values->ip . ' has been banned!');
        return $this->doRedirect($this->getUrl('admin.dashboard'));
    }

    /**
     * gets ban IPS file path
     *
     * @return string
     */
    protected function getBanFileName(): string
    {
        return App::getDir(App::CONFIG) . DS . 'blocked_ips.php';
    }

    /**
     * gets new ban IPS file contents
     *
     * @return string
     */
    protected function getBanFileContents(): string
    {
        return "<?php\n\nreturn \$blocked_ips = [\n" . implode("", array_map(function ($el) {
            return "  '$el',\n";
        }, $this->blocked_ips)) . "];\n";
    }
}
