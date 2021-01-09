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

namespace App\Base\Traits;

use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\HttpFoundation\Response;

/**
 * Form Page Trait
 */
trait FormPageTrait
{
    /**
     * @var array template data
     */
    protected $template_data = [];

    /**
     * gets form id
     *
     * @return string
     */
    protected function getFormId(): string
    {
        $arr = explode("\\", strtolower(get_class($this)));
        return array_pop($arr);
    }

    /**
     * get form object
     *
     * @return FAPI\Form
     */
    public function getForm(): ?FAPI\Form
    {
        if (!is_null($this->getTemplate())) {
            return $this->getTemplate()->data()['form'] ?? null;
        }

        return $this->template_data['form'] ?? null;
    }

    /**
     * check if form is submitted
     */
    protected function isSubmitted(): bool
    {
        return ($this->getForm() && $this->getForm()->isSubmitted());
    }

    /**
     * adds submit button to form
     *
     * @param FAPI\Form $form
     * @param bool $inline_button
     * @return FAPI\Form
     */
    protected function addSubmitButton(FAPI\Form $form, $inline_button = false): FAPI\Form
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
                        'weight' => 100,
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
                        'weight' => 110,
                    ]
                );
        }

        return $form;
    }

    /**
     * gets a form for confirmation
     *
     * @param string $confirm_message
     * @param FAPI\Form $form
     * @param string|null $cancel_url
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function fillConfirmationForm($confirm_message, $form, $cancel_url = null): FAPI\Form
    {
        $form->addField(
            'confirm',
            [
                'type' => 'markup',
                'value' => $this->getUtils()->translate($confirm_message, $this->getCurrentLocale()),
                'suffix' => '<br /><br />',
                'weight' => -100,
            ]
        )
            ->addMarkup('<a class="btn btn-danger btn-sm" href="' . ($cancel_url ?: $this->getControllerUrl()) . '">' . $this->getUtils()->translate('Cancel', $this->getCurrentLocale()) . '</a>');
        $this->addSubmitButton($form, true);
        return $form;
    }

    /**
     * {@intheritdocs}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender()
    {
        if ($this->getForm() && $this->getForm()->isSubmitted()) {
            $this->getApp()->event('form_submitted', ['form' => $this->getForm()]);
            return $this->getForm()->getSubmitResults(get_class($this) . '::formSubmitted');
        }
        return parent::beforeRender();
    }

    /**
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    abstract public function getFormDefinition(FAPI\Form $form, &$form_state);

    /**
     * validates form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    abstract public function formValidate(FAPI\Form $form, &$form_state);

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed|Response
     */
    abstract public function formSubmitted(FAPI\Form $form, &$form_state);
}
