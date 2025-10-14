<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Traits;

use App\Base\Abstracts\Controllers\BasePage;
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
    use TemplatePageTrait;

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
     * @return FAPI\Form|null
     */
    public function getForm(): ?FAPI\Form
    {
        return $this->getTemplate()?->data()['form'] ?? ($this->template_data['form'] ?? null);
    }

    /**
     * check if form is submitted
     */
    protected function isSubmitted(): bool
    {
        return ($this->getForm() && $this->getForm()->isSubmitted());
    }


    /**
     * Adds a generic button to the form
     *
     * @param FAPI\Form $form
     * @param string $name
     * @param string $text
     * @param string $icon
     * @param array $options
     * @return FAPI\Form
     * @throws FAPI\Exceptions\FormException
     */
    protected function addButton(
        FAPI\Form $form,
        string $name,
        string $text,
        string $icon,
        array $options = []
    ): FAPI\Form {
        $defaults = [
            'type' => 'button',
            'container_tag' => null,
            'prefix' => '&nbsp;',
            'value' => $text,
            'attributes' => ['class' => 'btn btn-primary btn-sm'],
            'weight' => 100,
            'label' => $this->getHtmlRenderer()->getIcon($icon, ['style' => 'zoom: 1.5']) . '&nbsp;' . __($text),
        ];

        $form->addField($name, array_merge($defaults, $options));

        return $form;
    }

    /**
     * adds submit button to form
     *
     * @param FAPI\Form $form
     * @param bool $inline_button
     * @param bool $isConfirmation
     * @return FAPI\Form
     * @throws FAPI\Exceptions\FormException
     */
    protected function addSubmitButton(
        FAPI\Form $form,
        bool $inline_button = false,
        bool $isConfirmation = false,
        ?string $buttonText = null
    ): FAPI\Form {
        $options = $inline_button
            ? ['attributes' => ['class' => 'btn btn-primary btn-sm'], 'weight' => 100]
            : ['attributes' => ['class' => 'btn btn-primary btn-lg btn-block'], 'container_class' => 'form-item mt-3', 'weight' => 110];

        $this->addButton(
            $form,
            'button',
            $buttonText ?? ($isConfirmation ? 'Ok' : 'Save') ,
            $isConfirmation ? 'check' : 'save',
            $options
        );

        return $form;
    }

    /**
     * Shortcut for cancel button
     */
    protected function addCancelButton(FAPI\Form $form, ?string $buttonText = null): FAPI\Form
    {
        $this->addButton(
            $form,
            'cancel',
            $buttonText ?? 'Cancel',
            'times',
            [
                'attributes' => ['class' => 'btn btn-secondary'],
                'weight' => 120,
            ]
        );

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
    protected function fillConfirmationForm(string $confirm_message, FAPI\Form $form, ?string $cancel_url = null): FAPI\Form
    {
        $form->addField(
            'confirm',
            [
                'type' => 'markup',
                'value' => $this->getUtils()->translate($confirm_message, locale: $this->getCurrentLocale()),
                'suffix' => '<br /><br />',
                'weight' => -100,
            ]
        )
            ->addMarkup('<a class="btn btn-danger btn-sm cancel-btn" href="' . ($cancel_url ?: $this->getControllerUrl()) . '">' . $this->getHtmlRenderer()->getIcon('x', ['style' => 'zoom: 1.5']) . '&nbsp' . $this->getUtils()->translate('Cancel', locale: $this->getCurrentLocale()) . '</a>');
        $this->addSubmitButton($form, true, true);
        return $form;
    }

    /**
     * {@intheritdocs}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender() : BasePage|Response
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
    abstract public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form;

    /**
     * validates form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    abstract public function formValidate(FAPI\Form $form, array &$form_state): bool|string;

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     */
    abstract public function formSubmitted(FAPI\Form $form, array &$form_state): mixed;
}
