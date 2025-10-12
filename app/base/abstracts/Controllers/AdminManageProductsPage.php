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
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Interfaces\Model\ProductInterface;
use App\Site\Models\DownloadableProduct;
use HaydenPierce\ClassFinder\ClassFinder;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Site\Models\MediaElement;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
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
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data): array
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
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($product->id),
                        static::DELETE_BTN => $this->getDeleteButton($product->id),
                    ],
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
}
