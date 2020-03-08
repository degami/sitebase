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
namespace App\Base\Abstracts\Controllers;

use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use \Degami\PHPFormsApi as FAPI;
use \Degami\Basics\Html\TagElement;
use \App\App;

/**
 * Base for admin page that manages a Frontend Model
 */
abstract class AdminManageFrontendModelsPage extends AdminManageModelsPage
{
    /**
     * {@inheriydocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    protected function addFrontendFormElements(FAPI\Form $form, &$form_state, $form_elements = ['url', 'website_id', 'locale'])
    {
        $object = $this->getObject();

        $form_elements = array_intersect($form_elements, ['url', 'website_id', 'locale']);

        $object_url = $object_website = $object_locale = '';
        if ($object instanceof FrontendModel && $object->isLoaded()) {
            $languages = $this->getUtils()->getSiteLanguagesSelectOptions($object->getWebsiteId());

            foreach ($form_elements as $key => $element) {
                ${"object_".$element} = $object->{$element};
            }
        } else {
            $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
        }

        $websites = $this->getUtils()->getWebsitesSelectOptions();

        $fieldset = $form->addField(
            'frontend',
            [
                'type' => 'fieldset',
                'title' => 'Frontend',
                'collapsible' => true,
                'collapsed' => false,
            ]
        );

        if (in_array('url', $form_elements)) {
            $fieldset->addField(
                'url',
                [
                'type' => 'textfield',
                'title' => 'Url key',
                'default_value' => $object_url,
                'validate' => ['required'],
                ]
            );
        }

        if (in_array('website_id', $form_elements)) {
            $fieldset->addField(
                'website_id',
                [
                'type' => 'select',
                'title' => 'Website',
                'default_value' => $object_website,
                'options' => $websites,
                'validate' => ['required'],
                ]
            );
        }

        if (in_array('locale', $form_elements)) {
            $fieldset->addField(
                'locale',
                [
                'type' => 'select',
                'title' => 'Locale',
                'default_value' => $object_locale,
                'options' => $languages,
                'validate' => ['required'],
                ]
            );
        }

        return $form;
    }

    protected function addSeoFormElements(FAPI\Form $form, &$form_state)
    {
        $object = $this->getObject();

        $object_meta_description = $object_meta_keywords = $object_html_title = '';
        if ($object instanceof FrontendModel && $object->isLoaded()) {
            $object_meta_description = $object->meta_description;
            $object_meta_keywords = $object->meta_keywords;
            $object_html_title = $object->html_title;
        }

        $fieldset = $form->addField(
            'seo',
            [
                'type' => 'fieldset',
                'title' => 'SEO',
                'collapsible' => true,
                'collapsed' => true,
            ]
        );

        $fieldset->addField(
            'meta_description',
            [
            'type' => 'textfield',
            'title' => 'Meta Description',
            'default_value' => $object_meta_description,
            ]
        )
        ->addField(
            'meta_keywords',
            [
            'type' => 'textfield',
            'title' => 'Meta Keywords',
            'default_value' => $object_meta_keywords,
            ]
        )
        ->addField(
            'html_title',
            [
            'type' => 'textfield',
            'title' => 'Html Title',
            'default_value' => $object_html_title,
            ]
        );


        return $form;
    }
}
