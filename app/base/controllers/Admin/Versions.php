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

namespace App\Base\Controllers\Admin;

use App\Base\Abstracts\Controllers\AdminFormPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\ModelVersion;
use App\Base\Abstracts\Models\BaseModel;

/**
 * "Versions" Admin Page
 */
class Versions extends AdminFormPage
{
    
    /**
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        if ($this->getRequest()->query->get('action') == 'delete') {
            $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form, $this->getUrl($this->getRequest()->request->get('return_route')));
        }

        if ($this->getRequest()->query->get('action') == 'restore') {
            $this->fillConfirmationForm('Do you confirm the restore of the selected element?', $form, $this->getUrl($this->getRequest()->request->get('return_route')));
        }

        $form->addField('return_route', [
            'type' => 'hidden',
            'value' => $this->getRequest()->request->get('return_route'),
        ]);

        return $form;
    }

    /**
     * validates form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, array &$form_state): bool|string
    {
        return true;
    }

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, array &$form_state): mixed
    {
        $version = ModelVersion::load($this->getRequest()->query->get('version_id'));
        $action = $this->getRequest()->query->get('action');

        if ($action == 'delete') {
            $message = 'The version has been deleted';
            $version->remove();
        } else {
            $message = 'The version has been restored';
            /** @var BaseModel $object */
            $object = $version->getObject();
            $object->restoreVersion($version, true);
        }

        $values = $form->values();

        $returnRoute = $values->return_route;

        $this->addInfoFlashMessage($this->getUtils()->translate($message));

        return $this->doRedirect($this->getUrl($returnRoute));
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'form_admin_page';
    }

}