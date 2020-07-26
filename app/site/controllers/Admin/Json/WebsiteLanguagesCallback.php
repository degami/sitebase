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
namespace App\Site\Controllers\Admin\Json;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\Controllers\AdminJsonPage;
use \App\Base\Traits\AdminFormTrait;
use \App\Site\Models\Contact;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Controllers\Admin\ContactForms as ContactFormsController;

/**
 * Contact Form AJAX callback
 */
class WebsiteLanguagesCallback extends AdminJsonPage
{
    use AdminFormTrait;

    /**
     * @var FAPI\Form form object
     */
    protected $form;


    public function getObjectClass()
    {
        return $this->getRequest()->get('object_class');
    }

    public function getObject()
    {
        return $this->getContainer()->make($this->getRequest()->get('object_class'));
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
     * returns an empty form
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function emptyForm(FAPI\Form $form, &$form_state)
    {
        $this->addFrontendFormElements($form, $form_state);

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        try {
            $this->form = FAPI\FormBuilder::getForm([$this, 'emptyForm'], 'emptyForm', json_decode($this->getRequest()->request->get('jsondata'), true));

            $out = json_decode($this->form->render());

            if ($out == null) {
                $out = ['html'=>'', 'js'=>'', 'is_submitted'=> false];
            }

            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setData($out);
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e);
        }
    }

    //not used on this class
    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getJsonData()
    {
        return [];
    }
}