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
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\News as NewsModel;
use \App\App;

/**
 * "News" Admin Page
 */
class News extends AdminManageModelsPage
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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $news = null;
        if ($this->getRequest()->get('news_id')) {
            $news = $this->loadObject($this->getRequest()->get('news_id'));
        }

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
                
                if ($news instanceof NewsModel) {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions($news->getWebsiteId());
                } else {
                    $languages = $this->getUtils()->getSiteLanguagesSelectOptions();
                }

                $websites = $this->getUtils()->getWebsitesSelectOptions();

                $news_url = $news_locale = $news_title = $news_content = $news_website = $news_date = '';
                if ($news instanceof NewsModel) {
                    $news_url = $news->url;
                    $news_locale = $news->locale;
                    $news_title = $news->title;
                    $news_content = $news->content;
                    $news_website = $news->website_id;
                    $news_date = $news->date;
                }
                $form->addField(
                    'url',
                    [
                    'type' => 'textfield',
                    'title' => 'Page url',
                    'default_value' => $news_url,
                    'validate' => ['required'],
                    ]
                )
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
                        'website_id',
                        [
                        'type' => 'select',
                        'title' => 'Website',
                        'default_value' => $news_website,
                        'options' => $websites,
                        'validate' => ['required'],
                        ]
                    )
                    ->addField(
                        'locale',
                        [
                        'type' => 'select',
                        'title' => 'Locale',
                        'default_value' => $news_locale,
                        'options' => $languages,
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
                    )
                    ->addField(
                        'button',
                        [
                        'type' => 'submit',
                        'value' => 'ok',
                        'container_class' => 'form-item mt-3',
                        'attributes' => ['class' => 'btn btn-primary btn-lg btn-block'],
                        ]
                    );
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
        $news = $this->newEmptyObject();
        if ($this->getRequest()->get('news_id')) {
            $news = $this->loadObject($this->getRequest()->get('news_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $news->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                $news->url = $values['url'];
                $news->title = $values['title'];
                $news->locale = $values['locale'];
                $news->content = $values['content'];
                $news->website_id = $values['website_id'];
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
            'URL' => 'url',
            'Locale' => 'locale',
            'Title' => 'title',
            'Date' => 'date',
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
