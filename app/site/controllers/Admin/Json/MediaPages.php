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

use App\Site\Controllers\Admin\Pages;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Site\Models\Page;
use App\Site\Models\MediaElement as Media;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * pages for media JSON
 */
class MediaPages extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/media/{id:\d+}/pages';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_medias';
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
        $media = $this->containerCall([Media::class, 'load'], ['id' => $route_data['id']]);

        $pages = array_map(
            function ($el) use ($media) {
                $page = $this->containerMake(Page::class, ['db_row' => $el]);
                return $page->getTitle() .
                    ' <a class="deassoc_lnk ml-auto" data-page_id="' . $page->id . '" data-media_id="' . $el->id . '" href="' . $this->getUrl('crud.app.site.controllers.admin.json.mediapages', ['id' => $media->id]) . '?page_id=' . $page->id . '&media_id=' . $el->id . '&action=media_deassoc">&times;</a>';
            },
            $this->getDb()->page_media_elementList()->where('media_element_id', $media->getId())->page()->fetchAll()
        );

        $pagesData = array_map(
            function ($el) {
                $page = $this->containerMake(Page::class, ['db_row' => $el]);
                return $page->getData();
            },
            $this->getDb()->page_media_elementList()->where('media_element_id', $media->getId())->page()->fetchAll()
        );


        if ($this->getRequest()->get('action') == 'media_deassoc') {
            $pagesController = $this->containerMake(Pages::class);
            $form = $pagesController->getForm();

            $form->setAction($this->getUrl('admin.pages') . '?action=' . $this->getRequest()->get('action'));
            $form->addField(
                'media_id',
                [
                    'type' => 'hidden',
                    'default_value' => $media->getId(),
                ]
            );
        } else {
            $mediaController = $this->containerMake(\App\Site\Controllers\Admin\Media::class);
            $form = $mediaController->getForm();

            $form->setAction($this->getUrl('admin.media') . '?action=' . $this->getRequest()->get('action'));
            $form->addField(
                'media_id',
                [
                    'type' => 'hidden',
                    'default_value' => $media->getId(),
                ]
            );
        }

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'pages' => $pagesData,
            'html' => ($this->getRequest()->get('action') == 'page_assoc' ? "<ul class=\"elements_list list-group\"><li class=\"list-group-item d-flex flex-row align-items-center\">" . implode("</li><li class=\"list-group-item d-flex flex-row align-items-center\">", $pages) . "</li></ul><hr />" : '') . $form->render(),
            'js' => "",
        ];
    }
}
