<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Controllers\Admin\Json;

use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Abstracts\Models\AbstractLLMAdapter;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Interfaces\AI\AIModelInterface;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Models\User;
use App\Base\Routing\RouteInfo;
use Degami\PHPFormsApi as FAPI;
use App\Base\Traits\FormTrait;
use Psr\Container\ContainerInterface;
use Override;

/**
 * AISettings Admin
 */
class AISettings extends AdminJsonPage
{
    use FormTrait;

    protected FAPI\Form $form;

    public function __construct(
            protected ContainerInterface $container, 
            protected ?Request $request = null, 
            protected ?RouteInfo $route_info = null    
    ) {
        parent::__construct($container, $request, $route_info);

        $this->form = FAPI\FormBuilder::getForm([$this, 'getFormDefinition'], $this->getFormId())
            ->setValidate([[$this, 'formValidate']])
            ->setSubmit([[$this, 'formSubmitted']]);
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    public function getForm() : ?FAPI\Form
    {
        return $this->form;
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $this->processFormSubmit($this->form);

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'html' => $this->form->render(),
            'js' => "",
        ];
    }

    private function processFormSubmit(FAPI\Form $form) : void
    {
        if (!$this->checkCredentials()) {
            throw new PermissionDeniedException();
        } else {
            $this->getApp()->event('before_form_process', ['form' => $form]);
            $form->process();
        }
    }

    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $aiLLMAdapter = $this->getLLMAdapter();
        $models = $aiLLMAdapter->getAvailableModels();
        $configured_model = $aiLLMAdapter->getModel();

        $form->addField('model', [
            'type' => 'select',
            'title' => 'Select Model',
            'options' => array_combine($models, $models),
            'default_value' => $configured_model,
        ]);

        $this->addSubmitButton($form);
        $form->setAction($this->getControllerUrl());
        return $form;
    }

    public function formValidate(FAPI\Form $form, array &$form_state): bool|string
    {
        return true;
    }


    public function formSubmitted(FAPI\Form $form, array &$form_state): mixed
    {
        $values = $form->getValues();
        $aiLLMAdapter = $this->getLLMAdapter();
        $aiLLMAdapter->setModel($values['model']);

        return null;
    }

    protected function getLLMAdapter() : ?AIModelInterface
    {
        /** @var User $user */
        $user = $this->getCurrentUser();
        $uiSettings = $user->getUserSession()->getSessionKey('uiSettings') ?? [];
        $preferredAI = $uiSettings['preferredAI'] ?? current($this->getAI()->getEnabledAIs());
        return $this->getAI()->getLLMAdapter($preferredAI);
    }
}
