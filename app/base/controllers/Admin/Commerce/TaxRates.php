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

namespace App\Base\Controllers\Admin\Commerce;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use App\Base\Models\TaxClass;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\TaxRate as TaxRateModel;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;

/**
 * "Tax Rates" Admin Page
 */
class TaxRates extends AdminManageModelsPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Tax Rates';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_orders';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return TaxRateModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'tax_rate_id';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'percent',
            'text' => 'Tax Rates',
            'section' => 'commerce',
            'order' => 17,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';

        /**
         * @var TaxRateModel $tarRate
         */
        $taxRate = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        $websites = $this->getUtils()->getWebsitesSelectOptions();

        $taxClasses = ['' => '-- Select --'];
        foreach (TaxClass::getCollection() as $taxClass) {
            $taxClasses[$taxClass->getId()] = $taxClass->getClassName();
        }

        $countries = ['*' => 'All Countries'] + $this->getUtils()->getCountriesSelectOptions();

        switch ($type) {
            case 'edit':
            case 'new':

                $form->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'options' => $websites,
                    'validate' => ['required'],
                    'default_value' => $taxRate->getWebsiteId(),
                ])->addField('tax_class_id', [
                    'type' => 'select',
                    'title' => 'Tax Class',
                    'options' => $taxClasses,
                    'validate' => ['required'],
                    'default_value' => $taxRate->getTaxClassId(),
                ])->addField('country_code', [
                    'type' => 'select',
                    'title' => 'Country',
                    'options' => $countries,
                    'default_value' => $taxRate->getCountryCode(),
                ])->addField('rate', [
                    'type' => 'textfield',
                    'title' => 'Rate (%)',
                    'validate' => ['required', 'numeric'],
                    'default_value' => $taxRate->getRate(),
                ]);

                $this->addSubmitButton($form);

                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        //$values = $form->values();
        // @todo : check if page language is in page website languages?
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var TaxRateModel $taxRate
         */
        $taxRate = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':

            // intentional fall trough
            // no break
            case 'edit':

                $taxRate
                    ->setTaxClassId($values['tax_class_id'])
                    ->setCountryCode($values['country_code'])
                    ->setWebsiteId($values['website_id'])
                    ->setRate((float)$values['rate']);

                $this->setAdminActionLogData($taxRate->getChangedData());

                $taxRate->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Order status Saved."));
                break;
            case 'delete':
                $taxRate->delete();

                $this->setAdminActionLogData('Deleted tax rate ' . $taxRate->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Tax rate Deleted."));

                break;
        }
        return $this->refreshPage();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Tax Class' => ['order' => 'tax_class_id', 'foreign' => 'tax_class_id', 'table' => $this->getModelTableName(), 'view' => 'class_name'],
            'Country' => ['order' => 'country_code', 'search' => 'country_code'],
            'Rate' => ['order' => 'rate', 'search' => 'rate'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($taxRate) {
                return [
                    'ID' => $taxRate->id,
                    'Website' => $taxRate->getWebsiteId() == null ? 'All websites' : $taxRate->getWebsite()->domain,
                    'Tax Class' => $taxRate->getTaxClass() ? $taxRate->getTaxClass()->getClassName() : 'N/A',
                    'Country' => $taxRate->getCountryCode(),
                    'Rate' => $taxRate->getRate() . ' %',
                    'actions' => $this->getModelRowButtons($taxRate),
                ];
            },
            $data
        );
    }

    protected function beforeRender(): BasePage|Response 
    {
        if (App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false) == false) {
            $this->addWarningFlashMessage($this->getUtils()->translate("Commerce functionallity is currently disabled"), true);
        }
        return parent::beforeRender();
    }

    public static function exposeDataToDashboard() : mixed
    {
        return null;
    }
}
