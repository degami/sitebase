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

use App\Site\Controllers\Admin\Taxonomy;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Site\Models\Page;
use Degami\PHPFormsApi\Abstracts\Base\FieldsContainer;
use DI\DependencyException;
use DI\NotFoundException;

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
    public static function getRoutePath(): string
    {
        return 'json/page/{id:\d+}/terms';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_pages';
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
        $page = $this->containerCall([Page::class, 'load'], ['id' => $route_data['id']]);

        $terms = array_map(
            function ($el) use ($page) {
                return $el->getTitle() . ' <a class="deassoc_lnk ml-auto" data-page_id="' . $page->id . '" data-term_id="' . $el->id . '" href="' . $this->getUrl('crud.app.site.controllers.admin.json.pageterms', ['id' => $page->id]) . '?page_id=' . $page->id . '&term_id=' . $el->id . '&action=deassoc">&times;</a>';
            },
            $page->getTerms()
        );

        $termsData = array_map(
            function ($el) {
                return $el->getData();
            },
            $page->getTerms()
        );

        $taxonomyController = $this->containerMake(Taxonomy::class);
        $form = $taxonomyController->getForm();

        $form->removeField('seo');

        // update html_ids to avoid select2 issues
        foreach ($form->getFields() as $field) {
            if (!($field instanceof FieldsContainer)) {
                $field->setId('sidebar-' . $field->getHtmlId());
            }
        }
        foreach ($form->getField('frontend')->getFields() as $field) {
            if (!($field instanceof FieldsContainer)) {
                $field->setId('sidebar-' . $field->getHtmlId());
            }
        }

        if ($this->getRequest()->get('action') == 'new') {
            foreach (['content', 'template_name'] as $fieldname) {
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

        $form->setAction($this->getUrl('admin.taxonomy') . '?action=' . $this->getRequest()->get('action'));
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
            'html' => ($this->getRequest()->get('action') == 'new' ? "<ul class=\"elements_list list-group\"><li class=\"list-group-item d-flex flex-row align-items-center\">" . implode("</li><li class=\"list-group-item d-flex flex-row align-items-center\">", $terms) . "</li></ul><hr />" : '') . $form->render(),
            'js' => "",
        ];
    }
}
