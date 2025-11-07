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
use App\Site\Models\Book;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * media for book JSON
 */
class BookMedia extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/books/{id:\d+}/media';
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
        $product = $this->containerCall([Book::class, 'load'], ['id' => $route_data['id']]);

        $gallery = array_map(
            function ($el) use ($product) {
                return '<div class="gallery-elem">' .
                    $el->getThumb("150x100", null, 'img-fluid img-thumbnail') .
                    ' <a class="deassoc_lnk" data-product_id="' . $product->id . '" data-media_id="' . $el->id . '" href="' . $this->getUrl('crud.app.site.controllers.admin.json.bookmedia', ['id' => $product->id]) . '?product_id=' . $product->id . '&media_id=' . $el->id . '&action=book_deassoc">&times;</a>' .
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
            'html' => ($this->getRequest()->query->get('action') == 'new' ? "<div class=\"page-gallery\">" . implode("", $gallery) . "</div><hr />" : '') . $form->render(),
            'js' => "",
        ];
    }
}
