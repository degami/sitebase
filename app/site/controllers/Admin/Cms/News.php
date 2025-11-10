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

namespace App\Site\Controllers\Admin\Cms;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\News as NewsModel;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;

/**
 * "News" Admin Page
 */
class News extends AdminManageFrontendModelsPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_news';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return NewsModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'news_id';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'file-text',
            'text' => 'News',
            'section' => 'cms',
            'order' => 20,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';
        $news = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':

                $news_title = $news_content = $news_date = '';
                if ($news->isLoaded()) {
                    $news_title = $news->title;
                    $news_content = $news->content;
                    $news_date = $news->date;
                }
                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $news_title,
                    'validate' => ['required'],
                ])->addField('date', [
                    'type' => 'datepicker',
                    'title' => 'Date',
                    'default_value' => $news_date,
                    'validate' => ['required'],
                ])->addField('content', [
                    'type' => 'tinymce',
                    'title' => 'Content',
                    'tinymce_options' => DEFAULT_TINYMCE_OPTIONS,
                    'default_value' => $news_content,
                    'rows' => 20,
                ]);

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
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        //$values = $form->values();
        // @todo : check if page language is in page website languages?
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var NewsModel $news
         */
        $news = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':
                $news->setUserId($this->getCurrentUser()->getId());
            // intentional fall trough
            // no break
            case 'edit':
                $news->setUrl($values['frontend']['url']);
                $news->setTitle($values['title']);
                $news->setLocale($values['frontend']['locale']);
                $news->setContent($values['content']);
                $news->setWebsiteId($values['frontend']['website_id']);
                $news->setDate($values['date']);

                $this->setAdminActionLogData($news->getChangedData());

                $news->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("News Saved."));
                break;
            case 'delete':
                $news->delete();

                $this->setAdminActionLogData('Deleted news ' . $news->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("News Deleted."));

                break;
        }
        return $this->refreshPage();
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => 'id',
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'URL' => ['order' => 'url', 'search' => 'url'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Date' => ['order' => 'date', 'search' => 'date'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data, array $options = []): array
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
                    'actions' => $this->getModelRowButtons($news),
                ];
            },
            $data
        );
    }
}
