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
namespace App\Base\Abstracts\Controllers;

use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;
use \App\App;

/**
 * Base frontend page for displaying a form
 */
abstract class FormPage extends FrontendPage
{
    /**
     * @var array template data
     */
    protected $templateData = [];

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);

        $this->templateData += [
            'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'])
            ->setValidate([ [$this, 'formValidate'] ])
            ->setSubmit([ [$this, 'formSubmitted'] ]),
        ];

        $this->processFormSubmit();
    }

    /**
     * process form submission
     *
     * @return void
     */
    protected function processFormSubmit()
    {
        $this->getApp()->event('before_form_process', ['form' => $this->getForm()]);
        $this->getForm()->process();
    }

    /**
     * gets form object
     *
     * @return FAPI\Form|null
     */
    protected function getForm()
    {
        return $this->templateData['form'] ?? null;
    }

    /**
     * {@intheritdocs}
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if ($this->getForm() && $this->getForm()->isSubmitted()) {
            $this->getApp()->event('form_submitted', ['form' => $this->getForm()]);
            return $this->getForm()->getSubmitResults(get_class($this).'::formSubmitted');
        }
        return parent::beforeRender();
    }

    /**
     * check if form is submitted
     */
    protected function isSubmitted()
    {
        return ($this->getForm() && $this->getForm()->isSubmitted());
    }

    /**
     * gets form definition object
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    abstract public function getFormDefinition(FAPI\Form $form, &$form_state);

    /**
     * validates form submission
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    abstract public function formValidate(FAPI\Form $form, &$form_state);

    /**
     * handles form submission
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed|Response
     */
    abstract public function formSubmitted(FAPI\Form $form, &$form_state);
}
