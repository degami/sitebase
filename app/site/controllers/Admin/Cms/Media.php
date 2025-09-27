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

use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Routing\RouteInfo;
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
use App\Base\Abstracts\Models\BaseCollection;
use Degami\Basics\Html\TagElement;
use App\Site\Models\DownloadableProduct;

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

            $this->addActionLink(
                'make_folder', 
                'make_folder', 
                $this->getHtmlRenderer()->getIcon('folder-plus') . '&nbsp;' .$this->getUtils()->translate('Create Folder'),
                $this->getControllerUrl().'?action=addfolder&parent_id='.$this->getRequest()->get('parent_id'),
                'btn btn-sm btn-warning',
            );

            if ($this->getRequest()->get('parent_id')) {
                $parent_id = $this->getRequest()->get('parent_id');
                if (is_numeric($parent_id)) {
                    $parentObj = MediaElement::load($parent_id);

                    $this->addActionLink(
                        'go-up',
                        'go-up',
                        $this->getHtmlRenderer()->getIcon('corner-left-up') . ' ' . __('Up'),
                        $this->getControllerUrl().'?parent_id='.$parentObj->parent_id,
                        'btn btn-sm btn-light'
                    );
                }
            }

        } elseif ($this->template_data['action'] == 'usage') {
            $this->addBackButton();
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
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'media';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_medias';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return MediaElement::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'media_id';
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
            'icon' => 'image',
            'text' => 'Media',
            'section' => 'cms',
            'order' => 40,
        ];
    }

    protected function getCollection(): BaseCollection
    {
        $collection = parent::getCollection();

        if ($this->getRequest()->get('parent_id')) {
            $parent_id = $this->getRequest()->get('parent_id');
            if (is_numeric($parent_id)) {
                $collection->addCondition(['parent_id' => $parent_id]);
            }
        } else {
            $collection->addCondition(['parent_id' => null]);
        }

        $collection->addSelect('*')->addSelect('IF(mimetype =\'inode/directory\', 1, 0) AS is_dir');
        $collection->addOrder(['is_dir' => 'DESC'], 'start');

        return $collection;
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
     * @throws \Exception
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
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
            case 'addfolder':
                $this->addBackButton();

                $parentName = 'Root Folder';
                $parent_id = $this->getRequest()->get('parent_id');
                if (is_numeric($parent_id)) {
                    $parentObj = MediaElement::load($parent_id);

                    $parentName = $parentObj->getFilename();

                    $form->addField('parent_id', [
                        'type' => 'hidden',
                        'default_value' => $parentObj->getId(),
                    ]);    
                }
                $form
                ->addMarkup('<h3>'.__('Add folder into "%s"', [$parentName]).'</h3>')
                ->addField('name', [
                    'type' => 'textfield',
                    'title' => 'Folder name',
                ]);

                $this->addSubmitButton($form);
                break;
            case 'edit':
                $elem_data = $media->getData();
                try {
                    $elem_data['owner'] = $media->getOwner()->getUsername();
                } catch (\Exception $e) {}

                unset($elem_data['id']);
                unset($elem_data['user_id']);

                try {
                    if ($media->isImage()) {
                        $box = $media->getImageBox();
                        if ($box) {
                            $elem_data += [
                                'width' => $box->getWidth() . ' px',
                                'height' => $box->getHeight() . ' px',
                            ];    
                        }
                    }    
                } catch (\Exception $e) {}

                array_walk(
                    $elem_data,
                    function (&$el, $key) {
                        $el = '<strong>' . $key . '</strong>: ' . $el;
                    }
                );


                try {
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
                } catch (\Exception $e) {}

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

                $destinationDir = App::getDir(App::MEDIA);
                if ($this->getRequest()->get('parent_id')) {
                    $parent_id = $this->getRequest()->get('parent_id');
                    if (is_numeric($parent_id)) {
                        $parentFolder = MediaElement::load($parent_id);
                        $destinationDir = $parentFolder->getPath();

                        $form->addField('parent_id', [
                            'type' => 'hidden',
                            'default_value' => $parentFolder->getId(),
                        ]);  
                    }
                }

                $form->addField('upload_file', [
                    'type' => 'file',
                    'destination' => $destinationDir,
                    'rename_on_existing' => true,
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

                if ($this->getRequest()->get('product_id')) {
                    /** @var DownloadableProduct $product */
                    $product = $this->containerCall([DownloadableProduct::class, 'load'], ['id' => $this->getRequest()->get('product_id')]);
                    $form->addField('product_id', [
                        'type' => 'hidden',
                        'default_value' => $product->getId(),
                    ]);
                }

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
                            /* .' '.* @var Page $page */
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
            case 'page_deassoc':
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
            case 'downloadable_product_assoc':
                $not_in = array_map(
                    function ($el) {
                        return $el->page_id;
                    },
                    $this->getDb()->downloadable_product_media_elementList()->where('media_element_id', $media->getId())->fetchAll()
                );

                $products = array_filter(
                    array_map(
                        function ($product) use ($not_in) {
                            /** @var DownloadableProduct $product */
                            if (in_array($product->getId(), $not_in)) {
                                return null;
                            }

                            return ['title' => $product->getTitle() . ' - ' . $product->getRewrite()->getUrl(), 'id' => $product->getId()];
                        },
                        DownloadableProduct::getCollection()->getItems()
                    )
                );

                $products = array_combine(array_column($products, 'id'), array_column($products, 'title'));

                $form->addField('product_id', [
                    'type' => 'select',
                    'options' => ['' => ''] + $products,
                    'default_value' => '',
                ])->addField('media_id', [
                    'type' => 'hidden',
                    'default_value' => $media->getId(),
                ]);

                $this->addSubmitButton($form, true);
                break;
            case 'downloadable_product_deassoc':
                /** @var DownloadableProduct $product */
                $product = $this->containerCall([DownloadableProduct::class, 'load'], ['id' => $this->getRequest()->get('product_id')]);
                $form->addField('product_id', [
                    'type' => 'hidden',
                    'default_value' => $product->getId(),
                ])->addField('media_id', [
                    'type' => 'hidden',
                    'default_value' => $media->getId(),
                ])->addField('confirm', [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the selected element from the "' . $product->getTitle() . '" product (ID: ' . $product->getId() . ') ?',
                    'suffix' => '<br /><br />',
                ])->addMarkup('<a class="btn btn-danger btn-sm" href="' . $this->getUrl('crud.app.site.controllers.admin.json.downloadablemedia', ['id' => $product->getId()]) . '?page_id=' . $product->getId() . '&action=new">Cancel</a>');

                $this->addSubmitButton($form, true);
                break;
            case 'delete':
                $confirmMessage = 'Do you confirm the deletion of the selected element?';
                if ($media->isDirectory()) {
                    $confirmMessage = 'Do you confirm the deletion of the selected folder and any included element or folder?';
                }
                $this->fillConfirmationForm($confirmMessage, $form);
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
         * @var MediaElement $media
         */
        $media = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'addfolder':
                $folder = $this->newEmptyObject();
                $folder->setParentId($values['parent_id'] ?? null);
                $folder->setFilename($values['name']);

                if ($folder->getParentId()) {
                    $parentFolder = MediaElement::load($folder->getParentId());
                    $folder->setPath($parentFolder->getPath() . DS . $folder->getFilename());
                } else {
                    $folder->setPath(App::getDir(App::MEDIA) . DS . $folder->getFilename());
                }
                @mkdir($folder->getPath(), 0755, true);
                
                $folder->setFilesize(0);
                $folder->setMimetype('inode/directory');

                $this->setAdminActionLogData($folder->getChangedData());

                $folder->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Folder Created."));

                break;
            case 'new':
                $media->setUserId($this->getCurrentUser()->getId());
                $media->setParentId($values->parent_id ?? null);
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
                if ($values->upload_file->renamed) {
                    $this->addInfoFlashMessage(
                        $this->getUtils()->translate(
                            "File was renamed to %s",
                            [$values->upload_file->filename]
                        )
                    );
                }
                $media->setLazyload($values->lazyload);

                $this->setAdminActionLogData($media->getChangedData());

                $media->persist();

                if ($values['page_id'] != null) {
                    $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']])->addMedia($media);
                } else if ($values['product_id'] != null) {
                    $this->containerCall([DownloadableProduct::class, 'load'], ['id' => $values['product_id']])->addMedia($media);
                } else {
                    $this->addSuccessFlashMessage($this->getUtils()->translate("Media Saved."));
                }
                break;
            case 'page_deassoc':
                if ($values['page_id']) {
                    /** @var Page $page */
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->removeMedia($media);
                }
                break;
            case 'page_assoc':
                if ($values['page_id']) {
                    /** @var Page $page */
                    $page = $this->containerCall([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->addMedia($media);
                }
                break;
            case 'downloadable_product_deassoc':
                if ($values['product_id']) {
                    /** @var DownloadableProduct $page */
                    $product = $this->containerCall([DownloadableProduct::class, 'load'], ['id' => $values['product_id']]);
                    $product->removeMedia($media);
                }
                break;
            case 'downloadable_product_assoc':
                if ($values['product_id']) {
                    /** @var DownloadableProduct $product */
                    $product = $this->containerCall([DownloadableProduct::class, 'load'], ['id' => $values['product_id']]);
                    $product->addMedia($media);
                }
                break;
            case 'delete':
                if ($media->isDirectory()) {
                    $deletedElements = $this->deleteMediaFolder($media);

                    $this->setAdminActionLogData('Deleted media Folder ' . $media->getId());
    
                    $this->addInfoFlashMessage($this->getUtils()->translate("Media Folder Deleted. %d total elements removed.", [$deletedElements]));    

                } else {
                    $media->delete();

                    $this->setAdminActionLogData('Deleted media ' . $media->getId());
    
                    $this->addInfoFlashMessage($this->getUtils()->translate("Media Deleted."));    
                }

                break;
        }
        if ($this->getRequest()->request->get('page_id') != null) {
            return new JsonResponse(['success' => true]);
        }
        return $this->refreshPage();
    }

    protected function deleteMediaFolder(MediaElement $folder) : int
    {
        if (!$folder->isDirectory()) {
            return 0;
        }

        $childrenCollection = $this->containerCall([static::getObjectClass(), 'getCollection']);   
        $childrenCollection->addCondition(['parent_id' => $folder->getId()]);
        $childrenCollection->addSelect('*')->addSelect('IF(mimetype =\'inode/directory\', 1, 0) AS is_dir');
        $childrenCollection->addOrder(['is_dir' => 'DESC'], 'start');

        $deleted = 0;
        foreach ($childrenCollection as $child) {
            /** @var MediaElement $child  */
            if ($child->isDirectory()) {
                $deleted += $this->deleteMediaFolder($child);
            } else {
                $child->delete();
                $deleted++;
            }
        }
        
        $folder->delete();

        $deleted++;

        return $deleted;
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

    protected function getMediaPreview(MediaElement $elem) : string
    {
        if ($elem->isDirectory()) {
            return '<h2>'.$elem->getMimeIcon('solid').'</h2>';
        }
        return $elem->isImage() ? $elem->getThumb('50x50', null, null, ['for_admin' => '']) : '';
    }

    /**
     * {@inheritdoc}
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
            function (MediaElement $elem) {
                $actions = match($elem->isDirectory()) {
                    true => [
                        $this->getChangeDirButton($elem->id),
                        $this->getDeleteButton($elem->id),
                    ],
                    default => [
                        $this->getActionButton('usage', $elem->id, 'success', 'zoom-in', 'Usage'),
                        $this->getEditButton($elem->id),
                        $this->getDeleteButton($elem->id),
                    ]
                };
                return [
                    'ID' => $elem->getId(),
                    'Preview' => $this->getMediaPreview($elem),
                    'Filename - Path' => $elem->getFilename() . '<br /><abbr style="font-size: 0.6rem;">' . $elem->getPath() . '</abbr>',
                    'Mimetype' => $elem->getMimetype(),
                    'Filesize' => $elem->isDirectory() ? '' : $this->formatBytes($elem->getFilesize()),
                    'Owner' => $elem->getOwner()->username,
                    'Height' => $elem->isImage() ? $elem->getImageBox()?->getHeight() . ' px' : '',
                    'Width' => $elem->isImage() ? $elem->getImageBox()?->getWidth() . ' px' : '',
                    'Lazyload' => $elem->isImage() ? $this->getUtils()->translate($elem->getLazyload() ? 'Yes' : 'No', locale: $this->getCurrentLocale()) : '',
                    'actions' => implode(
                        " ",
                        $actions
                    ),
                ];
            },
            $data
        );
    }

    /**
     * adds a "new" button
     *
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addNewButton()
    {
        $this->addActionLink('new-btn', 'new-btn', $this->getHtmlRenderer()->getIcon('plus') . ' ' . $this->getUtils()->translate('New', locale: $this->getCurrentLocale()), $this->getControllerUrl() . '?action=new' . ($this->getRequest()->get('parent_id') ? '&parent_id='.$this->getRequest()->get('parent_id') : ''), 'btn btn-sm btn-success');
    }


    public function getChangeDirButton(int $object_id): string
    {
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-info',
                    'href' => $this->getControllerUrl() . '?parent_id=' .$object_id,
                    'title' => $this->getUtils()->translate('Get into folder', locale: $this->getCurrentLocale()),
                ],
                'text' => $this->getHtmlRenderer()->getIcon('corner-left-down'),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {
        }

        return '';
    }
}
