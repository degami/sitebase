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
namespace App\Site\Controllers\Admin;

use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \App\Base\Abstracts\AdminManageFrontendModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\News as NewsModel;
use \App\App;

/**
 * "News" Admin Page
 */
class News extends AdminManageFrontendModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'news';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_news';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return NewsModel::class;
    }

   /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'news_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $news = $this->getObject();

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $news_title = $news_content = $news_date = '';
                if ($news->isLoaded()) {
                    $news_title = $news->title;
                    $news_content = $news->content;
                    $news_date = $news->date;
                }
                $form
                ->addField(
                    'title',
                    [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $news_title,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'date',
                    [
                    'type' => 'datepicker',
                    'title' => 'Date',
                    'default_value' => $news_date,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'content',
                    [
                    'type' => 'tinymce',
                    'title' => 'Content',
                    'tinymce_options' => [
                    'plugins' => "code,link,lists,hr,preview,searchreplace,media mediaembed,table,powerpaste",
                    ],
                    'default_value' => $news_content,
                    'rows' => 20,
                    ]
                );

                $this->addFrontendFormElements($form, $form_state);
                $this->addSubmitButton($form);

                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
        }

        return $form;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        $values = $form->values();
        // @todo : check if page language is in page website languages?
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /**
         * @var NewsModel $news
         */
        $news = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':
                $news->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                $news->url = $values['frontend']['url'];
                $news->title = $values['title'];
                $news->locale = $values['frontend']['locale'];
                $news->content = $values['content'];
                $news->website_id = $values['frontend']['website_id'];
                $news->date = $values['date'];

                $news->persist();
                break;
            case 'delete':
                $news->delete();
                break;
        }
        return $this->doRedirect($this->getControllerUrl());
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTableHeader()
    {
        return [
            'ID' => 'id',
            'Website' => 'website_id',
            'URL' => ['order' => 'url', 'search' => 'url'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Date' => ['order' => 'date', 'search' => 'date'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param  array $data
     * @return array
     */
    protected function getTableElements($data)
    {
        return array_map(
            function ($news) {
                return [
                'ID' => $news->id,
                'Website' => $news->getWebsiteId() == null ? 'All websites' : $news->getWebsite()->domain,
                'URL' => $news->url,
                'Locale' => $news->locale,
                'Title' => $news->title,
                'Date' => $news->date,
                'actions' => '<a class="btn btn-light btn-sm" href="'. $news->getFrontendUrl() .'" target="_blank">'.$this->getUtils()->getIcon('zoom-in') .'</a>
                    <a class="btn btn-primary btn-sm" href="'. $news->getControllerUrl() .'?action=edit&news_id='. $news->id.'">'.$this->getUtils()->getIcon('edit') .'</a>
                    <a class="btn btn-danger btn-sm" href="'. $news->getControllerUrl() .'?action=delete&news_id='. $news->id.'">'.$this->getUtils()->getIcon('trash') .'</a>'
                ];
            },
            $data
        );
    }
}
