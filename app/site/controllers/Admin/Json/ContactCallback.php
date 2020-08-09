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
use \App\Site\Models\Contact;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Controllers\Admin\ContactForms as ContactFormsController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contact Form AJAX callback
 */
class ContactCallback extends AdminJsonPage
{
    /**
     * @var FAPI\Form form object
     */
    protected $form;

    /**
     * @var Contact contact object
     */
    protected $contact = null;

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_contact';
    }

    /**
     * returns an empty form
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function emptyForm(FAPI\Form $form, &$form_state)
    {
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
    public function process(RouteInfo $route_info = null, $route_data = [])
    {
        try {
            $contact_form_controller = $this->getContainer()->make(ContactFormsController::class);
            $this->form = $contact_form_controller->getForm();
            $out = json_decode($this->form->render());

            if ($out == null) {
                $out = ['html'=>'', 'js'=>'', 'is_submitted'=> false];
            }

            return $this
                ->getResponse()
                ->prepare($this->getRequest())
                ->setData($out);
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e);
        }
    }

    /**
     * ajax callback
     *
     * @param FAPI\Form $form
     * @return FAPI\Abstracts\Base\Element|null
     */
    public static function contactFormsCallback(FAPI\Form $form)
    {
        return $form->getField('form_definition');
    }

    //not used on this class
    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getJsonData()
    {
        return [];
    }
}
