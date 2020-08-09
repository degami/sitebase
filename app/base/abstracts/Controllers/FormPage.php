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
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PhpfastcacheSimpleCacheException
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
     * @throws BasicException
     */
    protected function processFormSubmit()
    {
        $this->getApp()->event('before_form_process', ['form' => $this->getForm()]);
        $this->getForm()->process();
    }
}
