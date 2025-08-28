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

namespace App\Site\Controllers\Admin\Json;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Site\Models\Contact;
use App\Base\Routing\RouteInfo;
use Degami\PHPFormsApi as FAPI;
use App\Site\Controllers\Admin\ContactForms as ContactFormsController;
use Symfony\Component\HttpFoundation\Response;

/**
 * Contact Form AJAX callback
 */
class ContactCallback extends AdminJsonPage
{
    /**
     * @var FAPI\Form form object
     */
    protected FAPI\Form $form;

    /**
     * @var Contact|null contact object
     */
    protected ?Contact $contact = null;

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_contact';
    }

    /**
     * returns an empty form
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    public function emptyForm(FAPI\Form $form, &$form_state): FAPI\Form
    {
        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param RouteInfo|null $route_info
     * @param array $route_data
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function process(?RouteInfo $route_info = null, array $route_data = []): Response
    {
        try {
            $contact_form_controller = $this->containerMake(ContactFormsController::class);
            $this->form = $contact_form_controller->getForm();
            $out = json_decode($this->form->render());

            if ($out == null) {
                $out = ['html' => '', 'js' => '', 'is_submitted' => false];
            }

            return $this->getUtils()->createJsonResponse($out);
        } catch (Exception $e) {
            return $this->getUtils()->exceptionJson($e, $this->getRequest());
        }
    }

    /**
     * ajax callback
     *
     * @param FAPI\Form $form
     * @return FAPI\Abstracts\Base\Element|null
     */
    public static function contactFormsCallback(FAPI\Form $form): ?FAPI\Abstracts\Base\Element
    {
        return $form->getField('form_definition') ?? current(array_filter($form->getFields(), fn ($el) => $el->getName() == 'form_definition'));
    }

    //not used on this class

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getJsonData(): array
    {
        return [];
    }
}
