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

namespace App\Site\Controllers\Admin\Json;

use Degami\Basics\Exceptions\BasicException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \App\Base\Abstracts\Controllers\AdminJsonPage;
use \App\Base\Traits\AdminFormTrait;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contact Form AJAX callback
 */
class WebsiteLanguagesCallback extends AdminJsonPage
{
    use AdminFormTrait;

    /**
     * @var FAPI\Form form object
     */
    protected $form;


    /**
     * gets object class
     *
     * @return mixed|null
     */
    public function getObjectClass()
    {
        return $this->getRequest()->get('object_class');
    }

    /**
     * gets an object class instance
     *
     * @return mixed
     * @throws \DI\DependencyException
     * @throws \DI\NotFoundException
     */
    public function getObject()
    {
        return $this->getContainer()->make($this->getRequest()->get('object_class'));
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * returns an empty form
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function emptyForm(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $this->addFrontendFormElements($form, $form_state);

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function process(RouteInfo $route_info = null, $route_data = []): Response
    {
        try {
            $this->form = FAPI\FormBuilder::getForm([$this, 'emptyForm'], 'emptyForm', json_decode($this->getRequest()->request->get('jsondata'), true));

            $out = json_decode($this->form->render());

            if ($out == null) {
                $out = ['html' => '', 'js' => '', 'is_submitted' => false];
            }

            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setData($out);
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e, $this->getRequest());
        }
    }

    //not used on this class

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getJsonData(): array
    {
        return [];
    }
}
