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
use \App\Base\Exceptions\PermissionDeniedException;

/**
 * Base for admin form page
 */
abstract class LoggedUserFormPage extends LoggedUserPage
{
    use FormPageTrait;

    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     * @param Request|null $request
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PermissionDeniedException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function __construct(ContainerInterface $container, Request $request = null)
    {
        parent::__construct($container, $request);
        $this->templateData = [
            'form' => FAPI\FormBuilder::getForm([$this, 'getFormDefinition'], $this->getFormId())
            ->setValidate([ [$this, 'formValidate'] ])
            ->setSubmit([ [$this, 'formSubmitted'] ]),
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
    protected function processFormSubmit()
    {
        if (!$this->checkCredentials()) {
            throw new PermissionDeniedException();
        } else {
            $this->getApp()->event('before_form_process', ['form' => $this->templateData['form']]);
            $this->templateData['form']->process();
        }
    }
}
