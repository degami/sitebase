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
use \App\Site\Models\Taxonomy;
use \App\Site\Routing\RouteInfo;
use \Degami\PHPFormsApi as FAPI;

/**
 * pages for term in JSON format
 */
class TermPages extends AdminJsonPage
{
    /**
     * return route path
     * @return string
     */
    public static function getRoutePath()
    {
        return 'json/term/{id:\d+}/pages';
    }

    /**
     * {@inheritdocs}
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_taxonomy';
    }

    /**
     * {@inheritdocs}
     * @return array
     */
    protected function getJsonData()
    {
        $route_data = $this->getRouteInfo()->getVars();
        $term = $this->getContainer()->call([Taxonomy::class, 'load'], ['id' => $route_data['id']]);

        $pages = array_map(function ($el) use ($term) {
            $page = $this->getContainer()->make(Page::class, ['dbrow' => $el]);
            return $page->getTitle().
                ' <a class="deassoc_lnk" data-page_id="'.$page->id.'" data-term_id="'.$el->id.'" href="'.$this->getUrl('admin.json.termpages', ['id' => $term->id]).'?page_id='.$page->id.'&term_id='.$el->id.'&action=term_deassoc">&times;</a>';
        }, $this->getDb()->page_taxonomyList()->where('taxonomy_id', $term->getId())->page()->fetchAll());

        $pagesData = array_map(function ($el) {
            $page = $this->getContainer()->make(Page::class, ['dbrow' => $el]);
            return $el->getData();
        }, $this->getDb()->page_taxonomyList()->where('taxonomy_id', $term->getId())->page()->fetchAll());


        if ($this->getRequest()->get('action') == 'term_deassoc') {
            $pagesController = $this->getContainer()->make(\App\Site\Controllers\Admin\Pages::class);
            $form = $pagesController->getForm();

            $form->setAction($this->getUrl('admin.pages').'?action='.$this->getRequest()->get('action'));
            $form->addField('term_id', [
                'type' => 'hidden',
                'default_value' => $term->getId(),
            ]);
        } else {
            $taxonomyController = $this->getContainer()->make(\App\Site\Controllers\Admin\Taxonomy::class);
            $form = $taxonomyController->getForm();

            $form->setAction($this->getUrl('admin.taxonomy').'?action='.$this->getRequest()->get('action'));
            $form->addField('term_id', [
                'type' => 'hidden',
                'default_value' => $term->getId(),
            ]);
        }

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'pages' => $pagesData,
            'html' => ($this->getRequest()->get('action') == 'page_assoc' ? "<ul class=\"elements_list\"><li>".implode("</li><li>", $pages) . "</li></ul><hr />" : '').$form->render(),
            'js' => "",
        ];


        return [];
    }
}
