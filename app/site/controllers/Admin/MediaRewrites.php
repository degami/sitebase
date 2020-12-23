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

use App\Base\Exceptions\PermissionDeniedException;
use App\Site\Models\Rewrite;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \App\Base\Abstracts\Controllers\AdminManageModelsPage;
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
     * @param Request|null $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PermissionDeniedException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws OutOfRangeException
     */
    public function __construct(ContainerInterface $container, Request $request, RouteInfo $route_info)
    {
        parent::__construct($container, $request, $route_info);
        $this->page_title = 'Rewrite / Media';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'base_admin_page';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_medias';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass(): string
    {
        return MediaElementRewrite::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'media_rewrite_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $media_rewrite = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':
                $this->addBackButton();

                $rewrites = ['none' => ''];
                foreach ($this->getDb()->rewrite()->fetchAll() as $rewrite) {
                    $rewrites[$rewrite->id] = $rewrite->url . " ({$rewrite->route})";
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

                $form->addField('rewrite_id', [
                    'type' => 'select',
                    'title' => 'Rewrite',
                    'default_value' => $media_rewrite_rewrite_id,
                    'options' => $rewrites,
                    'validate' => ['required'],
                ])->addField('media_id', [
                    'type' => 'select',
                    'title' => 'Media',
                    'default_value' => $media_rewrite_media_id,
                    'options' => $medias,
                    'validate' => ['required'],
                ]);

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
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return boolean|string
     */
    public function formValidate(FAPI\Form $form, &$form_state)
    {
        //$values = $form->values();
        return true;
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
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
                $media_rewrite->rewrite_id = $values['rewrite_id'] == 'none' ? null : $values['rewrite_id'];
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
    protected function getTableHeader(): ?array
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
     * @param array $data
     * @return array
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($elem) {
                return [
                    'ID' => $elem->getId(),
                    'Preview' => $elem->getMediaElement()->getThumb('100x100'),
                    'Filename - Path' => $elem->getMediaElement()->getFilename(),
                    'Website' => $elem->getRewriteId() != null ? $elem->getRewrite()->getWebsite()->getDomain() : 'All',
                    'Rewrite - Url' => $elem->getRewriteId() != null ? $elem->getRewrite()->getUrl() : 'Everywhere',
                    'Locale' => $elem->getRewriteId() != null ? $elem->getRewrite()->getLocale() : 'Any',
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
