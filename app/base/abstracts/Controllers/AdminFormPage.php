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

use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Degami\PHPFormsApi as FAPI;
use \App\Base\Traits\FormPageTrait;
use \App\Base\Exceptions\PermissionDeniedException;

/**
 * Base for admin form page
 */
abstract class AdminFormPage extends AdminPage
{
    use FormPageTrait;

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PermissionDeniedException
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function __construct(ContainerInterface $container, Request $request, RouteInfo $route_info)
    {
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
    private function processFormSubmit()
    {
        if (!$this->checkCredentials()) {
            throw new PermissionDeniedException();
        } else {
            $this->getApp()->event('before_form_process', ['form' => $this->template_data['form']]);
            $this->template_data['form']->process();
        }
    }
}
