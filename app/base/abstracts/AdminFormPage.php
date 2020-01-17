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
use \App\Base\Abstracts\AdminPage;
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
     * {@inheritdocs}
     *
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
     * loads object by id
     *
     * @param  integer $id
     * @return \App\Base\Abstracts\Model
     */
    protected function loadObject($id)
    {
        if (!is_subclass_of($this->getObjectClass(), \App\Base\Abstracts\Model::class)) {
            return null;
        }

        return $this->getContainer()->call([$this->getObjectClass(), 'load'], [ 'id' => $id]);
    }

    /**
     * gets new empty model
     *
     * @return \App\Base\Abstracts\Model
     */
    protected function newEmptyObject()
    {
        if (!is_subclass_of($this->getObjectClass(), \App\Base\Abstracts\Model::class)) {
            return null;
        }

        return $this->getContainer()->make($this->getObjectClass());
    }

    /**
     * adds a "new" button
     */
    public function addNewButton()
    {
        $this->addActionLink('new-btn', 'new-btn', $this->getUtils()->getIcon('plus').' '.$this->getUtils()->translate('New', $this->getCurrentLocale()), $this->getControllerUrl().'?action=new', 'btn btn-sm btn-success');
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
        ->addMarkup('<a class="btn btn-danger btn-sm" href="'.$this->getControllerUrl().'">'.$this->getUtils()->translate('Cancel', $this->getCurrentLocale()).'</a>')
        ->addField(
            'button',
            [
            'type' => 'submit',
            'container_tag' => null,
            'prefix' => '&nbsp;',
            'value' => 'Confirm',
            'attributes' => ['class' => 'btn btn-primary btn-sm'],
            ]
        );
        return $form;
    }

    /**
     * gets object to show class name for loading
     *
     * @return string
     */
    abstract public function getObjectClass();

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
