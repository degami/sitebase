<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Controllers\Admin;

use App\Base\Exceptions\PermissionDeniedException;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\MediaElement;
use App\Site\Models\Page;
use App\App;

/**
 * "Media" Admin Page
 */
class Media extends AdminManageModelsPage
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
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        AdminFormPage::__construct($container, $request, $route_info);
        if ($this->template_data['action'] == 'list') {
            parent::__construct($container, $request, $route_info);
        } elseif ($this->template_data['action'] == 'usage') {
            $media = $this->containerCall([MediaElement::class, 'load'], ['id' => $this->getRequest()->get('media_id')]);
            $elem_data = $media->getData();
            $elem_data['owner'] = $media->getOwner()->username;

            unset($elem_data['id']);
            unset($elem_data['user_id']);

            array_walk($elem_data, function (&$el, $key) {
                $el = '<strong>' . $key . '</strong>: ' . $el;
            });

            $this->template_data += [
                'media_elem' => $media,
                'elem_data' => $elem_data,
                'pages' => array_map(
                    function ($el) {
                        $page = $this->containerMake(Page::class, ['db_row' => $el]);
                        return ['url' => $page->getRewrite()->getUrl(), 'title' => $page->getTitle() . ' - ' . $page->getRewrite()->getUrl(), 'id' => $page->getId()];
                    },
                    $this->getDb()->page()->page_media_elementList()->where('media_element_id', $this->getRequest()->get('media_id'))->page()->fetchAll()
                ),
            ];
        }
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'media';
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
        return MediaElement::class;
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'media_id';
    }

    /**
     * {@inheritdocs}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => $this->getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'image',
            'text' => 'Media',
            'section' => 'cms',
            'order' => 40,
        ];
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
     * @throws \Exception
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        /** @var MediaElement $media */
        $media = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);
        //        $form->addMarkup('<pre>'.var_export($type, true)."\n".var_export($_POST, true)."\n".var_export($_FILES, true).'</pre>');
        switch ($type) {
            case 'edit':
                $elem_data = $media->getData();
                try {
                    $elem_data['owner'] = $media->getOwner()->getUsername();
                } catch (\Exception $e) {}

                unset($elem_data['id']);
                unset($elem_data['user_id']);

                if ($media->isImage()) {
                    $box = $media->getImageBox();
                    if ($box) {
                        $elem_data += [
                            'width' => $box->getWidth() . ' px',
                            'height' => $box->getHeight() . ' px',
                        ];    
                    }
                }

                array_walk(
                    $elem_data,
                    function (&$el, $key) {
                        $el = '<strong>' . $key . '</strong>: ' . $el;
                    }
                );


                if ($media->isImage()) {
                    $linkTo = $this->getUrl('admin.minipaint', ['path' => '']) . "?media_id=".$media->getId()."&imageUrl=".urlencode($media->getImageUrl())."&ts=".microtime();

                    $this->addActionLink(
                        'minipaint',
                        'minipaint',
                        $this->getHtmlRenderer()->getIcon('edit') . ' ' . 'Image Editor',
                        $linkTo,
                        'btn btn-sm btn-light'
                    );    
                }

                $this->addActionLink(
                    'pages-btn',
                    'pages-btn',
                    '&#9776; Pages',
                    $this->getUrl('crud.app.site.controllers.admin.json.mediapages', ['id' => $this->getRequest()->get('media_id')]) . '?media_id=' . $this->getRequest()->get('media_id') . '&action=page_assoc',
                    'btn btn-sm btn-light inToolSidePanel'
                );

                $form->addField('pre', [
                    'type' => 'markup',
                    'value' => '<ul><li>' . implode('</li><li>', $elem_data) . '</li></ul>',
                ]);

            // intentional fall trough
            // no break
            case 'new':
                $this->addBackButton();

                $form->addField('upload_file', [
                    'type' => 'file',
                    'destination' => App::getDir(App::MEDIA),
                    'title' => 'Upload new file',
                ])
                ->addField('lazyload', [
                    'type' => 'switchbox',
                    'title' => 'Lazyload',
                    'default_value' => boolval($media->getLazyload()) ? 1 : 0,
                    'yes_value' => 1,
                    'yes_label' => 'Yes',
                    'no_value' => 0,
                    'no_label' => 'No',
                    'field_class' => 'switchbox',
                ]);

                $this->addSubmitButton($form);

                if ($this->getRequest()->get('page_id')) {
                    /** @var Page $page */
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                    $form->addField('page_id', [
                        'type' => 'hidden',
                        'default_value' => $page->getId(),
                    ]);
                }
                break;
            case 'deassoc':
                /** @var Page $page */
                $page = $this->containerCall([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                $form->addField('page_id', [
                    'type' => 'hidden',
                    'default_value' => $page->getId(),
                ])->addField('media_id', [
                    'type' => 'hidden',
                    'default_value' => $media->getId(),
                ])->addField('confirm', [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the selected element from the "' . $page->getTitle() . '" page (ID: ' . $page->getId() . ') ?',
                    'suffix' => '<br /><br />',
                ])->addMarkup('<a class="btn btn-danger btn-sm" href="' . $this->getUrl('crud.app.site.controllers.admin.json.pagemedia', ['id' => $page->getId()]) . '?page_id=' . $page->getId() . '&action=new">Cancel</a>');

                $this->addSubmitButton($form, true);
                break;
            case 'page_assoc':
                $not_in = array_map(
                    function ($el) {
                        return $el->page_id;
                    },
                    $this->getDb()->page_media_elementList()->where('media_element_id', $media->getId())->fetchAll()
                );

                $pages = array_filter(
                    array_map(
                        function ($page) use ($not_in) {
                            /** @var Page $page */
                            if (in_array($page->getId(), $not_in)) {
                                return null;
                            }

                            return ['title' => $page->getTitle() . ' - ' . $page->getRewrite()->getUrl(), 'id' => $page->getId()];
                        },
                        Page::getCollection()->getItems()
                    )
                );

                $pages = array_combine(array_column($pages, 'id'), array_column($pages, 'title'));

                $form->addField('page_id', [
                    'type' => 'select',
                    'options' => ['' => ''] + $pages,
                    'default_value' => '',
                ])->addField('media_id', [
                    'type' => 'hidden',
                    'default_value' => $media->getId(),
                ]);

                $this->addSubmitButton($form, true);
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
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
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
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var MediaElement $media
         */
        $media = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $media->setUserId($this->getCurrentUser()->getId());
            // intentional fall trough
            // no break
            case 'edit':
                if ($values->upload_file->filepath) {
                    $media->setPath($values->upload_file->filepath);
                }
                if ($values->upload_file->filename) {
                    $media->setFilename($values->upload_file->filename);
                }
                if ($values->upload_file->mimetype) {
                    $media->setMimetype($values->upload_file->mimetype);
                }
                if ($values->upload_file->filesize) {
                    $media->setFilesize($values->upload_file->filesize);
                }
                $media->setLazyload($values->lazyload);

                $this->setAdminActionLogData($media->getChangedData());

                $media->persist();

                if ($values['page_id'] != null) {
                    $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']])->addMedia($media);
                } else {
                    $this->addSuccessFlashMessage($this->getUtils()->translate("Media Saved."));
                }
                break;
            case 'deassoc':
                if ($values['page_id']) {
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->removeMedia($media);
                }
                break;
            case 'page_assoc':
                if ($values['page_id']) {
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->addMedia($media);
                }
                break;
            case 'delete':
                $media->delete();

                $this->setAdminActionLogData('Deleted media ' . $media->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Media Deleted."));

                break;
        }
        if ($this->getRequest()->request->get('page_id') != null) {
            return new JsonResponse(['success' => true]);
        }
        return $this->refreshPage();
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
            'Filename - Path' => ['order' => 'filename', 'search' => 'filename'],
            'Mimetype' => ['order' => 'mimetype', 'search' => 'mimetype'],
            'Filesize' => ['order' => 'filesize', 'search' => 'filesize'],
            'Owner' => 'user_id',
            'Height' => null,
            'Width' => null,
            'Lazyload' => null,
            'actions' => null,
        ];
    }

    /**
     * {@inheritdocs}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function ($elem) {
                return [
                    'ID' => $elem->getId(),
                    'Preview' => $elem->getThumb('100x100', null, null, ['for_admin' => '']),
                    'Filename - Path' => $elem->getFilename() . '<br /><abbr style="font-size: 0.6rem;">' . $elem->getPath() . '</abbr>',
                    'Mimetype' => $elem->getMimetype(),
                    'Filesize' => $this->formatBytes($elem->getFilesize()),
                    'Owner' => $elem->getOwner()->username,
                    'Height' => $elem->getImageBox()->getHeight() . ' px',
                    'Width' => $elem->getImageBox()->getWidth() . ' px',
                    'Lazyload' => $this->getUtils()->translate($elem->getLazyload() ? 'Yes' : 'No', locale: $this->getCurrentLocale()),
                    'actions' => implode(
                        " ",
                        [
                            $this->getActionButton('usage', $elem->id, 'success', 'zoom-in', 'Usage'),
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
