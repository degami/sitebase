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

namespace App\Base\Abstracts\Controllers;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use App\Base\Interfaces\Model\ProductInterface;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Models\TaxClass;

/**
 * "Manage Products" Admin Page
 */
abstract class AdminManageProductsPage extends AdminManageFrontendModelsPage
{

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
        return 'administer_products';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'product_id';
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
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Tax Class' => ['order' => 'tax_class_id', 'foreign' => 'tax_class_id', 'table' => $this->getModelTableName(), 'view' => 'class_name'],
            'Price' => ['order' => 'price', 'search' => 'price'],
            'Is Physical' => [],
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
            function ($product) {
                return [
                    'ID' => $product->id,
                    'Website' => $product->getWebsiteId() == null ? 'All websites' : $product->getWebsite()->domain,
                    'Locale' => $product->getLocale(),
                    'Title' => $product->getTitle(),
                    'Tax Class' => $product->getTaxClassId() ? TaxClass::load($product->getTaxClassId())?->getClassName() : 'N/A',
                    'Price' => $product->getPrice(),
                    'Is Physical' => $product->isPhysical() ? 'Yes' : 'No',
                    'actions' => $this->getModelRowButtons($product),
                ];
            },
            $data
        );
    }

    protected function getProductTypes(): array
    {
        $classes = array_filter(ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE), fn($modelClass) => is_subclass_of($modelClass, ProductInterface::class));
        return array_combine($classes, array_map(fn($class) => strtolower(basename($class)), $classes));
    }

    protected function collectActionButtons() : self
    {
        parent::collectActionButtons();


        if (($this->template_data['action'] ?? 'list') == 'edit') {
            $product = $this->getObject();
            if ($product instanceof ProductInterface) {
                // Add Manage Stock button for physical products
                if ($product->isPhysical()) {
                    $this->addActionLink(
                        'stock-btn',
                        'stock-btn',
                        $this->getHtmlRenderer()->getIcon('layers') . ' ' .$this->getUtils()->translate('Manage Stock', locale: $this->getCurrentLocale()),
                        $this->getUrl('crud.app.base.controllers.admin.json.productstocks', ['product_details' => base64_encode(json_encode(['product_class' => $this->getObjectClass(),'product_id' => $product->getId()]))]) . '?product_id=' . $this->getRequest()->query->get('product_id') . '&action=',
                        'btn btn-sm btn-light inToolSidePanel', 
                        ['data-panelWidth' => '80%']
                    );
                }
            }
        }

        return $this;
    }
}
