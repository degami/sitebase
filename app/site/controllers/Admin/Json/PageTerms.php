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

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\AdminJsonPage;
use \App\Site\Models\Page;
use \App\Site\Models\Media;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;

/**
 * terms for page JSON
 */
class PageTerms extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath()
    {
        return 'json/page/{id:\d+}/terms';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_pages';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getJsonData()
    {
        $route_data = $this->getRouteInfo()->getVars();
        $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $route_data['id']]);

        $terms = array_map(
            function ($el) use ($page) {
                return $el->getTitle().' <a class="deassoc_lnk" data-page_id="'.$page->id.'" data-term_id="'.$el->id.'" href="'.$this->getUrl('admin.json.pageterms', ['id' => $page->id]).'?page_id='.$page->id.'&term_id='.$el->id.'&action=deassoc">&times;</a>';
            },
            $page->getTerms()
        );

        $termsData = array_map(
            function ($el) {
                return $el->getData();
            },
            $page->getTerms()
        );

        $taxonomyController = $this->getContainer()->make(\App\Site\Controllers\Admin\Taxonomy::class);
        $form = $taxonomyController->getForm();

        if ($this->getRequest()->get('action') == 'new') {
            foreach (['content','meta_description','meta_keywords','template_name'] as $fieldname) {
                $newField = $form->getFieldObj(
                    $fieldname,
                    [
                    'type' => 'hidden',
                    'default_value' => '',
                    ]
                );
                $form->setField($fieldname, $newField);
            }

            $newLocaleField = $form->getFieldObj(
                'locale',
                [
                'type' => 'hidden',
                'default_value' => $page->getLocale(),
                ]
            );
            $form->setField('locale', $newLocaleField);
        }

        $form->setAction($this->getUrl('admin.taxonomy').'?action='.$this->getRequest()->get('action'));
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
            'gallery' => $termsData,
            'html' => ($this->getRequest()->get('action') == 'new' ? "<ul class=\"elements_list\"><li>" . implode("</li><li>", $terms) . "</li></ul><hr />": ''). $form->render(),
            'js' => "",
        ];
    }
}
