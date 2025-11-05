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
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\ProductStock;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;
use HaydenPierce\ClassFinder\ClassFinder;

/**
 * "Product Stocks" Admin Page
 */
class ProductStocks extends AdminManageFrontendModelsPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Product Stocks';

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
        return ProductStock::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'stock_id';
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
            'icon' => 'layers',
            'text' => 'Product Stocks',
            'section' => 'commerce',
            'order' => 30,
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
         * @var ProductStock $productStock
         */
        $productStock = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        $websites = $this->getUtils()->getWebsitesSelectOptions();

        $physicalProductClasses = array_filter(array_merge(
            ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::BASE_MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE)
        ), function ($className) {
            return is_subclass_of($className, \App\Base\Interfaces\Model\PhysicalProductInterface::class);
        });

        switch ($type) {
            case 'edit':
            case 'new':

                $form->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'options' => $websites,
                    'required' => true,
                    'default_value' => $productStock->getWebsiteId(),
                ]);
                
                $classesOptions = [];
                foreach ($physicalProductClasses as $class) {
                    $classesOptions[$class] = $this->getUtils()->getClassBasename($class);
                }

                $form->addField('product_class', [
                    'type' => 'select',
                    'title' => 'Product Class',
                    'options' => $classesOptions,
                    'required' => true,
                    'default_value' => $productStock->getProductClass(),
                ]);

                $form->addField('product_id', [
                    'type' => 'textfield',
                    'title' => 'Product ID',
                    'required' => true,
                    'default_value' => $productStock->getProductId(),
                ]);

                $form->addField('quantity', [
                    'type' => 'number',
                    'title' => 'Quantity',
                    'required' => true,
                    'default_value' => $productStock->getQuantity(),
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
         * @var ProductStock $productStock
         */
        $productStock = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':

            // intentional fall trough
            // no break
            case 'edit':

                $this->setAdminActionLogData($productStock->getChangedData());

                $productStock
                    ->setUserId($this->getCurrentUser()?->getId())
                    ->setProductClass($values['product_class'])
                    ->setProductId($values['product_id'])
                    ->setQuantity($values['quantity'])
                    ->setWebsiteId($values['website_id']);

                $productStock->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Product stock Saved."));
                break;
            case 'delete':
                $productStock->delete();

                $this->setAdminActionLogData('Deleted product stock ' . $productStock->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Product stock Deleted."));

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
            'Product Class' => ['order' => 'product_class', 'search' => 'product_class'],
            'Product Id' => ['order' => 'product_id'],
            'Quantity' => ['order' => 'quantity', 'search' => 'quantity'],
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
            function ($productStock) {
                return [
                    'ID' => $productStock->id,
                    'Website' => $productStock->getWebsiteId() == null ? 'All websites' : $productStock->getWebsite()->domain,
                    'Product Class' => $productStock->getProductClass(),
                    'Product Id' => $productStock->getProductId(),
                    'Quantity' => $productStock->getQuantity(),
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($productStock->id),
                        static::DELETE_BTN => $this->getDeleteButton($productStock->id),
                    ],
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
}
