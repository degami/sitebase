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

namespace App\Base\Abstracts\Controllers;

use App\Base\Routing\RouteInfo;
use App\Base\Abstracts\Controllers\BasePage;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Traits\FormPageTrait;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Degami\PHPFormsApi as FAPI;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * Base for admin form page
 */
abstract class AdminFormPage extends AdminPage
{
    use FormPageTrait;

    /**
     * {@inheritdoc}
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws FAPI\Exceptions\FormException
     * @throws NotFoundException
     * @throws PermissionDeniedException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
        $this->template_data = [
            'action' => $this->getRequest()->get('action') ?? 'list',
            'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'], $this->getFormId())
                ->setValidate([[$this, 'formValidate']])
                ->setSubmit([[$this, 'formSubmitted']]),
        ];

        $this->processFormSubmit();
    }

    /**
     * process form submission
     *
     * @return void
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    private function processFormSubmit() : void
    {
        if (!$this->checkCredentials()) {
            throw new PermissionDeniedException();
        } else {
            $this->getApp()->event('before_form_process', ['form' => $this->template_data['form']]);
            $this->template_data['form']->process();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender(): BasePage|Response
    {
        if ($this->getForm() && $this->getRequest()->get('asJson') == 1) {
            if ($this->getForm()->isSubmitted()) {
                $this->getApp()->event('form_submitted', ['form' => $this->getForm()]);
                $formResults = $this->getForm()->getSubmitResults(get_class($this) . '::formSubmitted');

                if (isJson($formResults)) {
                    return JsonResponse::fromJsonString($formResults);
                }
                if (is_string($formResults)) {
                    return $this->containerMake(JsonResponse::class, ['data' => [
                        'html' => $formResults,
                    ]]);
                }
                if ($formResults instanceof Response) {
                    return $formResults;
                }

                return $this->containerMake(JsonResponse::class, ['data' => $formResults]);
            }

            if ($this->getForm()->getAction() == null) {
                $queryParams = $this->getRequest()->query->all();
                $this->getForm()->setAction($this->getControllerUrl() . ($queryParams ? '?' . http_build_query($queryParams) : ''));
            }

            $formHTML = $this->getForm()->render();
            if (isJson($formHTML)) {
                return JsonResponse::fromJsonString($formHTML);
            }

            return $this->containerMake(JsonResponse::class, ['data' => [
                'html' => $formHTML,
            ]]);
        }

        // as in FormPageTrait::beforeRender()
        if ($this->getForm() && $this->getForm()->isSubmitted()) {
            $this->getApp()->event('form_submitted', ['form' => $this->getForm()]);
            return $this->getForm()->getSubmitResults(get_class($this) . '::formSubmitted');
        }
        return parent::beforeRender();
    }
}
