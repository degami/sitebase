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

namespace App\Base\Controllers\Frontend\Users;

use App\App;
use App\Base\Abstracts\Controllers\LoggedUserFormPage;
use App\Base\Exceptions\NotAllowedException;
use App\Base\Models\Address;
use Degami\PHPFormsApi as FAPI;

class Addresses extends LoggedUserFormPage
{
    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return boolval(App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false)) && boolval(App::getInstance()->getEnvironment()->getVariable('ENABLE_LOGGEDPAGES', false));
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'users/addresses';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'addresses';
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        $this->template_data += [
            'current_user' => $this->getCurrentUser(),
            'addresses' => $this->getAddresses(),
        ];
        return $this->template_data;
    }

    protected function getAddresses() : array
    {
        return Address::getCollection()->where(['user_id' => $this->getCurrentUser()->getId()])->getItems();
    }

    /**
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $address = $this->getAddress();

        $countries = $this->getUtils()->getCountriesSelectOptions();

        $action = $this->getRequest()->query->get('action');
        switch ($action) {
            case 'add':
            case 'edit':
                $form
                ->addMarkup('<div class="row">')
                ->addField('first_name', [
                    'type' => 'textfield',
                    'title' => 'First Name',
                    'container_class' => 'col-sm-6 pb-2',
                    'validate' => ['required'],
                    'default_value' => $address->getFirstName(),
                ])
                ->addField('last_name', [
                    'type' => 'textfield',
                    'title' => 'Last Name',
                    'container_class' => 'col-sm-6 pb-2',
                    'validate' => ['required'],
                    'default_value' => $address->getLastName(),
                ])
                ->addField('company', [
                    'type' => 'textfield',
                    'title' => 'Company',
                    'container_class' => 'col-sm-12 pb-2',
                    'default_value' => $address->getCompany(),
                ])
                ->addField('address1', [
                    'type' => 'textfield',
                    'title' => 'Address 1',
                    'container_class' => 'col-sm-6 pb-2',
                    'validate' => ['required'],
                    'default_value' => $address->getAddress1(),
                ])
                ->addField('address2', [
                    'type' => 'textfield',
                    'title' => 'Address 2',
                    'container_class' => 'col-sm-6 pb-2',
                    'default_value' => $address->getAddress2(),
                ])
                ->addField('city', [
                    'type' => 'textfield',
                    'title' => 'City',
                    'container_class' => 'col-sm-6 pb-2',
                    'validate' => ['required'],
                    'default_value' => $address->getCity(),
                ])
                ->addField('state', [
                    'type' => 'textfield',
                    'title' => 'State',
                    'container_class' => 'col-sm-6 pb-2',
                    'default_value' => $address->getState(),
                ])
                ->addField('postcode', [
                    'type' => 'textfield',
                    'title' => 'Post Code',
                    'container_class' => 'col-sm-6 pb-2',
                    'validate' => ['required'],
                    'default_value' => $address->getPostcode(),
                ])
                ->addField('country_code', [
                    'type' => 'select',
                    'title' => 'Country',
                    'container_class' => 'col-sm-6 pb-2',
                    'options' => ['' => '-- Select --'] + $countries,
                    'validate' => ['required'],
                    'default_value' => $address->getCountryCode(),
                ])
                ->addField('phone', [
                    'type' => 'textfield',
                    'title' => 'Phone',
                    'container_class' => 'col-sm-6',
                    'default_value' => $address->getPhone(),
                ])
                ->addField('email', [
                    'type' => 'email',
                    'title' => 'Email',
                    'container_class' => 'col-sm-6',
                    'validate' => ['required', 'email'],
                    'default_value' => $address->getEmail(),
                ])
                ->addMarkup('</div>');

                $this->addSubmitButton($form);
                break;
            case 'delete':
                $this->fillConfirmationForm("Do you really want to delete this address?", $form);
                break;
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
        $address = $this->getAddress();
        $values = $form->values();

        $action = $this->getRequest()->query->get('action');

        switch ($action) {
            case 'add':
            case 'edit':
                $address
                    ->setUserId($this->getCurrentUser()->getId())
                    ->setWebsiteId($this->getCurrentWebsiteId())
                    ->setFirstName($values['first_name'])
                    ->setLastName($values['last_name'])
                    ->setCompany($values['company'])
                    ->setAddress1($values['address1'])
                    ->setAddress2($values['address2'])
                    ->setCity($values['city'])
                    ->setState($values['state'])
                    ->setPostcode($values['postcode'])
                    ->setCountryCode($values['country_code'])
                    ->setPhone($values['phone'])
                    ->setEmail($values['email'])
                    ->persist();

                $this->addInfoFlashMessage($this->getUtils()->translate("Address saved"));
                break;
            case 'delete':
                $address->delete();

                $this->addInfoFlashMessage($this->getUtils()->translate("Address deleted"));
                break;
        }

        return $this->doRedirect($this->getUrl('frontend.users.addresses'));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('Addresses', locale: $this->getCurrentLocale());
    }

    protected function getAddress() : Address
    {
        $action = $this->getRequest()->query->get('action');

        /** @var Address $address */
        if ($action == 'edit') {
            $id = $this->getRequest()->query->get('id');
            $address = Address::load($id);

            if ($address->getUserId() != $this->getCurrentUser()->getId()) {
                throw new NotAllowedException();
            }
        } else {
            $address = Address::new();
        }

        return $address;
    }
}