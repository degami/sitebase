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
namespace App\Base\Abstracts;

use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use \App\Base\Abstracts\FrontendPage;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;
use \App\App;

/**
 * Base frontend page for displaying a form
 */
abstract class FormPage extends FrontendPage
{
    /** @var array template data */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     * @param  RouteInfo|null $route_info
     * @param  array          $route_data
     * @return Response
     */
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        $this->route_info = $route_info;

        $this->templateData += [
            'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'])
                        ->setValidate([ [$this, 'formValidate'] ])
                        ->setSubmit([ [$this, 'formSubmitted'] ]),
        ];
        $this->processFormSubmit();

        return parent::process($route_info, $route_data);
    }

    /**
     * process form submission
     * @return void
     */
    private function processFormSubmit()
    {
        $this->getApp()->event('before_form_process', ['form' => $this->templateData['form']]);
        $this->templateData['form']->process();
    }

    /**
     * {@intheritdocs}
     * @return Response|self
     */
    protected function beforeRender()
    {
        if ($this->templateData['form']->isSubmitted()) {
            $this->getApp()->event('form_submitted', ['form' => $this->templateData['form']]);
            return $this->templateData['form']->getSubmitResults(get_class($this).'::formSubmitted');
        }
        return parent::beforeRender();
    }

    /**
     * gets form definition object
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return FAPI\Form
     */
    abstract public function getFormDefinition(FAPI\Form $form, &$form_state);

    /**
     * validates form submission
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return boolean|string
     */
    abstract public function formValidate(FAPI\Form $form, &$form_state);

    /**
     * handles form submission
     * @param  FAPI\Form $form
     * @param  array    &$form_state
     * @return mixed|Response
     */
    abstract public function formSubmitted(FAPI\Form $form, &$form_state);
}
