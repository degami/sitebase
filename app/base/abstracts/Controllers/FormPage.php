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
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Degami\PHPFormsApi as FAPI;
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
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function __construct(ContainerInterface $container, Request $request, RouteInfo $route_info)
    {
        parent::__construct($container, $request, $route_info);

        $this->template_data += [
            'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'])
                ->setValidate([[$this, 'formValidate']])
                ->setSubmit([[$this, 'formSubmitted']]),
        ];

        $this->processFormSubmit();
    }

    /**
     * process form submission
     *
     * @return void
     * @throws BasicException
     */
    protected function processFormSubmit()
    {
        $this->getApp()->event('before_form_process', ['form' => $this->getForm()]);
        if ($this->getForm() != null) {
            $this->getForm()->process();
        }
    }
}
