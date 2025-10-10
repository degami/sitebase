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
use Exception;

/**
 * "Mass Delete" Admin Page
 */
class Massdelete extends AdminFormPage
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
        $this->fillConfirmationForm('Do you confirm the deletion of the selected elements?', $form, $this->getUrl($this->getRequest()->request->get('return_route')));

        $formItems = $form->addField('class_name', [
            'type' => 'hidden',
            'value' => $this->getRequest()->request->get('class_name'),
        ]);
        
        $formItems = $form->addField('return_route', [
            'type' => 'hidden',
            'value' => $this->getRequest()->request->get('return_route'),
        ]);

        $items = $this->getRequest()->request->all('items');

        $formItems = $form->addField('items', [
            'type' => 'seamless_container',
            'container_tag' => '',
        ]);

        foreach ((array)$items as $k => $item) {
            $formItems->addField('items['.$k.']', [
                'type' => 'hidden',
                'value' => $item,
            ]);
        }

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
        $values = $form->values();
        $items = $values->items;

        $returnRoute = $values->return_route;
        $className = str_replace("\\\\","\\",$values->class_name);
        $removed = 0;
        foreach ($items as $item) {
            $pk = json_decode($item, true);
            $obj = $this->containerCall([$className, 'load'], ['id' => $pk]);
            try {
                $obj->remove();
                $removed++;
            } catch (Exception $e) {}
        }

        $this->addInfoFlashMessage($this->getUtils()->translate("%d elements deleted.", [$removed]));

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