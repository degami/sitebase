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

namespace App\Base\Abstracts\Controllers;

use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Degami\PHPFormsApi as FAPI;
use App\Base\Traits\FormPageTrait;
use App\Base\Exceptions\PermissionDeniedException;

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
}
