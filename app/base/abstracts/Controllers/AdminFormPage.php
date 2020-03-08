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
use \Degami\PHPFormsApi as FAPI;
use \App\App;

/**
 * Base for admin form page
 */
abstract class AdminFormPage extends AdminPage
{
    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->templateData = [
            'action' => $this->getRequest()->get('action') ?? 'list',
            'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'], $this->getFormId())
            ->setValidate([ [$this, 'formValidate'] ])
            ->setSubmit([ [$this, 'formSubmitted'] ]),
        ];

        $this->processFormSubmit();
    }

    /**
     * gets form id
     *
     * @return string
     */
    protected function getFormId()
    {
        $arr = explode("\\", strtolower(get_class($this)));
        return array_pop($arr);
    }

    /**
     * process form submission
     *
     * @return void
     */
    private function processFormSubmit()
    {
        if (!$this->checkCredentials()) {
            $this->templateData['form']->setSubmitResults(get_class($this).'::formSubmitted', $this->getUtils()->errorPage(403));
        } else {
            $this->getApp()->event('before_form_process', ['form' => $this->templateData['form']]);
            $this->templateData['form']->process();
        }
    }

    /**
     * check if form is submitted
     */
    protected function isSubmitted()
    {
        return ($this->templateData['form'] && $this->templateData['form']->isSubmitted());
    }

    /**
     * {@inheritdocs}
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if ($this->isSubmitted()) {
            $this->getApp()->event('form_submitted', ['form' => $this->templateData['form']]);
            return $this->templateData['form']->getSubmitResults(get_class($this).'::formSubmitted');
        }
        return parent::beforeRender();
    }

    /**
     * get form object
     *
     * @return FAPI\Form
     */
    public function getForm()
    {
        return $this->getTemplateData()['form'];
    }

    /**
     * gets a form for confirmation
     *
     * @param  string    $confirm_message
     * @param  FAPI\Form $form
     * @return FAPI\Form
     */
    protected function fillConfirmationForm($confirm_message, $form)
    {
        $form->addField(
            'confirm',
            [
            'type' => 'markup',
            'value' => $this->getUtils()->translate($confirm_message, $this->getCurrentLocale()),
            'suffix' => '<br /><br />',
            ]
        )
        ->addMarkup('<a class="btn btn-danger btn-sm" href="'.$this->getControllerUrl().'">'.$this->getUtils()->translate('Cancel', $this->getCurrentLocale()).'</a>');
        $this->addSubmitButton($form, true);
        return $form;
    }

    /**
     * adds submit button to form
     *
     * @param  FAPI\Form $form
     * @param  boolean    $inline_button
     * @return FAPI\Form
     */
    protected function addSubmitButton(FAPI\Form $form, $inline_button = false)
    {
        if ($inline_button) {
            $form
            ->addField(
                'button',
                [
                'type' => 'submit',
                'container_tag' => null,
                'prefix' => '&nbsp;',
                'value' => 'Ok',
                'attributes' => ['class' => 'btn btn-primary btn-sm'],
                ]
            );
        } else {
            $form
            ->addField(
                'button',
                [
                'type' => 'submit',
                'value' => 'Ok',
                'container_class' => 'form-item mt-3',
                'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                ]
            );
        }

        return $form;
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
