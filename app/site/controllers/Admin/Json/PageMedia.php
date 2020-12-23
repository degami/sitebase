<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Controllers\Admin\Json;

use App\Site\Controllers\Admin\Media;
use Degami\Basics\Exceptions\BasicException;
use \App\Base\Abstracts\Controllers\AdminJsonPage;
use \App\Site\Models\Page;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * media for page JSON
 */
class PageMedia extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/page/{id:\d+}/media';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_pages';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $route_data = $this->getRouteData();
        $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $route_data['id']]);

        $gallery = array_map(
            function ($el) use ($page) {
                return '<div class="gallery-elem">' .
                    $el->getThumb("150x100", null, 'img-fluid img-thumbnail') .
                    ' <a class="deassoc_lnk" data-page_id="' . $page->id . '" data-media_id="' . $el->id . '" href="' . $this->getUrl('admin.json.pagemedia', ['id' => $page->id]) . '?page_id=' . $page->id . '&media_id=' . $el->id . '&action=deassoc">&times;</a>' .
                    '</div>';
            },
            $page->getGallery()
        );

        $galleryData = array_map(
            function ($el) {
                return $el->getData();
            },
            $page->getGallery()
        );

        $mediaController = $this->getContainer()->make(Media::class);
        $form = $mediaController->getForm();

        $form->setAction($this->getUrl('admin.media') . '?action=' . $this->getRequest()->get('action'));
        $form->addField(
            'page_id',
            [
                'type' => 'hidden',
                'default_value' => $page->getId(),
            ]
        );

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'gallery' => $galleryData,
            'html' => ($this->getRequest()->get('action') == 'new' ? "<div class=\"page-gallery\">" . implode("", $gallery) . "</div><hr />" : '') . $form->render(),
            'js' => "",
        ];
    }
}
