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
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\Response;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;
use \App\App;
use \App\Base\Traits\FormPageTrait;

/**
 * Base frontend page for displaying a form
 */
abstract class FormPage extends FrontendPage
{
    use FormPageTrait;

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container, Request $request = null)
    {
        parent::__construct($container, $request);

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
}
