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

namespace App\Base\Controllers\Admin\Json;

use App\Base\Controllers\Admin\Commerce\Carts;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Models\Cart;
use App\Base\Models\CartDiscount;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Controllers\Admin\Commerce\ProductStocks as ProductStocksController;

/**
 * product stocks JSON
 */
class ProductStocks extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/product/{product_details}/stocks';
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
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $route_data = $this->getRouteData();

        $productDetails = base64_decode(
            $this->getRouteData('product_details') ?? ''
        );

        if (!isJson($productDetails)) {
            return [
                'success' => false,
                'message' => 'Invalid product details',
            ];
        }

        $productDetails = json_decode($productDetails, true);

        /** @var Product $product */
        $product = $this->containerCall([$productDetails['product_class'], 'load'], ['id' => $productDetails['product_id']]);

        if (!$product instanceof \App\Base\Interfaces\Model\PhysicalProductInterface) {
            return [
                'success' => false,
                'message' => 'Product is not a physical product',
            ];
        }
 
        /** @var ProductStock $stockItem */
        $stockItem = call_user_func([$product, 'getProductStock']);

        $action = $stockItem?->getId() ? 'edit' : 'new';
        $this->getEnvironment()->getRequest()->query->set('action', $action);
        if ($action == 'edit') {
            $this->getEnvironment()->getRequest()->query->set('stock_id', $stockItem->getId());
        }
        $stocksController = $this->containerMake(ProductStocksController::class);

        $form = $stocksController->getForm();
        $form->getField('product_id')->setValue($product->getId());
        $form->getField('product_class')->setValue(get_class($product));


        $form->setAction($this->getUrl('admin.commerce.productstocks') . '?action=' . $action . (($stockItem) ? '&stock_id='.$stockItem->getId() : ''));

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'html' => $form->render(),
            'js' => "",
        ];
    }
}
