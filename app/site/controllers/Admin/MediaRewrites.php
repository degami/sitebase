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
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\MediaElementRewrite;

/**
 * "MediaRewrites" Admin Page
 */
class MediaRewrites extends AdminManageModelsPage
{
    /**
     * {@inherithdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
        $this->page_title = 'Rewrite / Media';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'media_rewrites';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_medias';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return MediaElementRewrite::class;
    }

   /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'media_rewrite_id';
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
        $media_rewrite = $this->getObject();

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

                $rewrites = ['' => ''];
                foreach ($this->getDb()->rewrite()->fetchAll() as $rewrite) {
                    $rewrites[$rewrite->id] = $rewrite->url." ({$rewrite->route})";
                }

                $medias = ['' => ''];
                foreach ($this->getDb()->media_element()->fetchAll() as $media) {
                    $medias[$media->id] = $media->filename;
                }

                $media_rewrite_rewrite_id = $media_rewrite_media_id = '';
                if ($media_rewrite->isLoaded()) {
                    $media_rewrite_rewrite_id = $media_rewrite->rewrite_id;
                    $media_rewrite_media_id = $media_rewrite->media_element_id;
                }

                $form
                ->addField(
                    'rewrite_id',
                    [
                    'type' => 'select',
                    'title' => 'Rewrite',
                    'default_value' => $media_rewrite_rewrite_id,
                    'options' => $rewrites,
                    'validate' => ['required'],
                    ]
                )
                ->addField(
                    'media_id',
                    [
                    'type' => 'select',
                    'title' => 'Media',
                    'default_value' => $media_rewrite_media_id,
                    'options' => $medias,
                    'validate' => ['required'],
                    ]
                );

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
         * @var Rewrite $rewrite
         */
        $media_rewrite = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $media_rewrite->rewrite_id = $values['rewrite_id'];
                $media_rewrite->media_element_id = $values['media_id'];
                $media_rewrite->persist();
                break;
            case 'delete':
                $media_rewrite->delete();
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
            'Preview' => null,
            'Filename - Path' => 'media_element_id',
            'Website' => null,
            'Rewrite - Url' => 'rewrite_id',
            'Locale' => null,
            'Owner' => 'user_id',
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
            function ($elem) {
                return [
                'ID' => $elem->getId(),
                'Preview' => $elem->getMediaElement()->getThumb('100x100'),
                'Filename - Path' => $elem->getMediaElement()->getFilename(),
                'Website' => $elem->getRewrite()->getWebsite()->getDomain(),
                'Rewrite - Url' => $elem->getRewrite()->getUrl(),
                'Locale' => $elem->getRewrite()->getLocale(),
                'Owner' => $elem->getOwner()->username,
                'actions' => implode(
                    " ",
                    [
                    $this->getEditButton($elem->id),
                    $this->getDeleteButton($elem->id),
                    ]
                ),
                ];
            },
            $data
        );
    }
}
