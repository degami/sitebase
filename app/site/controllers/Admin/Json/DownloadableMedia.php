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

use App\Site\Controllers\Admin\Cms\Media;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Site\Models\DownloadableProduct;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * media for downloadable product JSON
 */
class DownloadableMedia extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/downloadableproducts/{id:\d+}/media';
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
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $route_data = $this->getRouteData();
        $product = $this->containerCall([DownloadableProduct::class, 'load'], ['id' => $route_data['id']]);

        $gallery = array_map(
            function ($el) use ($product) {
                return '<div class="gallery-elem card h-100 mr-2">' .
                    '<div class="card-header text-right p-2">' .
                        ' <a class="deassoc_lnk" data-product_id="' . $product->id . '" data-media_id="' . $el->id . '" href="' . $this->getUrl('crud.app.site.controllers.admin.json.downloadablemedia', ['id' => $product->id]) . '?product_id=' . $product->id . '&media_id=' . $el->id . '&action=downloadable_product_deassoc">&times;</a>' .
                    '</div>' .
                    '<div class="card-body text-center d-flex align-items-center justify-content-center">' .
                    $el->getThumb("150x100", null, 'img-fluid img-thumbnail') .
                    '</div>' .
                '</div>';
            },
            $product->getGallery()
        );

        $galleryData = array_map(
            function ($el) {
                return $el->getData();
            },
            $product->getGallery()
        );

        $mediaController = $this->containerMake(Media::class);
        $form = $mediaController->getForm();

        $form->setAction($this->getUrl('admin.cms.media') . '?action=' . $this->getRequest()->query->get('action'). ($this->getRequest()->query->get('media_id') ? '&media_id=' . $this->getRequest()->query->get('media_id') : ''));
        $form->addField(
            'product_type',
            [
                'type' => 'hidden',
                'default_value' => 'downloadable',
            ]
        );
        $form->addField(
            'product_id',
            [
                'type' => 'hidden',
                'default_value' => $product->getId(),
            ]
        );

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'gallery' => $galleryData,
            'html' => ($this->getRequest()->query->get('action') == 'new' ? "<div class=\"d-flex justify-content-start downloadable-gallery\">" . implode("", $gallery) . "</div><hr />" : '') . $form->render(),
            'js' => "",
        ];
    }
}
