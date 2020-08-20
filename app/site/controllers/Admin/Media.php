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
use Degami\Basics\Exceptions\BasicException;
use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Request;
use \Symfony\Component\HttpFoundation\JsonResponse;
use \App\Base\Abstracts\Controllers\AdminFormPage;
use \App\Base\Abstracts\Controllers\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\MediaElement;
use \App\Site\Models\Page;
use \App\App;

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
     * @throws BasicException
     * @throws FAPI\Exceptions\FormException
     * @throws PermissionDeniedException
     */
    public function __construct(ContainerInterface $container, Request $request = null)
    {
        AdminFormPage::__construct($container, $request);
        if ($this->templateData['action'] == 'list') {
            parent::__construct($container, $request);
        } elseif ($this->templateData['action'] == 'usage') {
            $media = $this->getContainer()->call([MediaElement::class, 'load'], ['id' => $this->getRequest()->get('media_id')]);
            $elem_data = $media->getData();
            $elem_data['owner'] = $media->getOwner()->username;

            unset($elem_data['id']);
            unset($elem_data['user_id']);

            array_walk($elem_data, function (&$el, $key) {
                $el = '<strong>'.$key.'</strong>: '.$el;
            });

            $this->templateData += [
                'media_elem' => $media,
                'elem_data' => $elem_data,
                'pages' => array_map(
                    function ($el) {
                        $page = $this->getContainer()->make(Page::class, ['dbrow' => $el]);
                        return ['url' => $page->getRewrite()->getUrl(), 'title' => $page->getTitle(). ' - '.$page->getRewrite()->getUrl(), 'id' => $page->getId()];
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
    protected function getTemplateName()
    {
        return 'media';
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
        return MediaElement::class;
    }

   /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getObjectIdQueryParam()
    {
        return 'media_id';
    }

    /**
     * {@inheritdocs}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     */
    public function getFormDefinition(FAPI\Form $form, &$form_state)
    {
        $type = $this->getRequest()->get('action') ?? 'list';
        $media = $this->getObject();

        $form->addField(
            'action',
            [
            'type' => 'value',
            'value' => $type,
            ]
        );
        //        $form->addMarkup('<pre>'.var_export($type, true)."\n".var_export($_POST, true)."\n".var_export($_FILES, true).'</pre>');
        switch ($type) {
            case 'edit':
                $elem_data = $media->getData();
                $elem_data['owner'] = $media->getOwner()->username;

                unset($elem_data['id']);
                unset($elem_data['user_id']);

                array_walk(
                    $elem_data,
                    function (&$el, $key) {
                        $el = '<strong>'.$key.'</strong>: '.$el;
                    }
                );

                $this->addActionLink(
                    'pages-btn',
                    'pages-btn',
                    '&#9776; Pages',
                    $this->getUrl('admin.json.mediapages', ['id' => $this->getRequest()->get('media_id')]).'?media_id='.$this->getRequest()->get('media_id').'&action=page_assoc',
                    'btn btn-sm btn-light inToolSidePanel'
                );

                $form->addField(
                    'pre',
                    [
                    'type' => 'markup',
                    'value' => '<ul><li>'.implode('</li><li>', $elem_data).'</li></ul>',
                    ]
                );

                // intentional fall trough
                // no break
            case 'new':
                $this->addBackButton();

                $form->addField(
                    'upload_file',
                    [
                    'type' => 'file',
                    'destination' => App::getDir(App::MEDIA),
                    'title' => 'Upload new file',
                        ]
                )
                ->addField(
                    'lazyload',
                    [
                    'type' => 'switchbox',
                    'title' => 'Lazyload',
                    'default_value' => boolval($media->lazyload) ? 1 : 0,
                    'yes_value' => 1,
                    'yes_label' => 'Yes',
                    'no_value' => 0,
                    'no_label' => 'No',
                    'field_class' => 'switchbox',
                    ]
                );

                $this->addSubmitButton($form);

                if ($this->getRequest()->get('page_id')) {
                    $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                    $form->addField(
                        'page_id',
                        [
                        'type' => 'hidden',
                        'default_value' => $page->id,
                        ]
                    );
                }
                break;
            case 'deassoc':
                $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $this->getRequest()->get('page_id')]);
                $form->addField(
                    'page_id',
                    [
                    'type' => 'hidden',
                    'default_value' => $page->id,
                    ]
                )
                ->addField(
                    'media_id',
                    [
                    'type' => 'hidden',
                    'default_value' => $media->id,
                    ]
                )
                ->addField(
                    'confirm',
                    [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the selected element from the "'.$page->title.'" page (ID: '.$page->id.') ?',
                    'suffix' => '<br /><br />',
                    ]
                )
                ->addMarkup('<a class="btn btn-danger btn-sm" href="'. $this->getUrl('admin.json.pagemedia', ['id' => $page->id]).'?page_id='.$page->id.'&action=new">Cancel</a>');

                $this->addSubmitButton($form, true);
                break;
            case 'page_assoc':
                $not_in = array_map(
                    function ($el) {
                        return $el->page_id;
                    },
                    $this->getDb()->page_media_elementList()->where('media_element_id', $media->id)->fetchAll()
                );

                $pages = array_filter(
                    array_map(
                        function ($el) use ($not_in) {
                            if (in_array($el->id, $not_in)) {
                                return null;
                            }
                            $page = $this->getContainer()->make(Page::class, ['dbrow' => $el]);
                            return ['title' => $page->getTitle(). ' - '.$page->getRewrite()->getUrl(), 'id' => $page->getId()];
                        },
                        $this->getDb()->page()->fetchAll()
                    )
                );

                $pages = array_combine(array_column($pages, 'id'), array_column($pages, 'title'));

                $form
                    ->addField(
                        'page_id',
                        [
                        'type' => 'select',
                        'options' => ['' => ''] + $pages,
                        'default_value' => '',
                        ]
                    )
                    ->addField(
                        'media_id',
                        [
                        'type' => 'hidden',
                        'default_value' => $media->id,
                        ]
                    );

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
     * @param  FAPI\Form $form
     * @param  array     &$form_state
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
     */
    public function formSubmitted(FAPI\Form $form, &$form_state)
    {
        /**
         * @var MediaElement $media
         */
        $media = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $media->user_id = $this->getCurrentUser()->id;
                // intentional fall trough
                // no break
            case 'edit':
                if ($values->upload_file->filepath) {
                    $media->path = $values->upload_file->filepath;
                }
                if ($values->upload_file->filename) {
                    $media->filename = $values->upload_file->filename;
                }
                if ($values->upload_file->mimetype) {
                    $media->mimetype = $values->upload_file->mimetype;
                }
                if ($values->upload_file->filesize) {
                    $media->filesize = $values->upload_file->filesize;
                }
                $media->lazyload = $values->lazyload;

                $this->setAdminActionLogData($media->getChangedData());

                $media->persist();

                if ($values['page_id'] != null) {
                    $this
                    ->getContainer()
                    ->call([Page::class, 'load'], ['id' => $values['page_id']])
                    ->addMedia($media);
                }
                break;
            case 'deassoc':
                if ($values['page_id']) {
                    $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->removeMedia($media);
                }
                break;
            case 'page_assoc':
                if ($values['page_id']) {
                    $page = $this->getContainer()->call([Page::class, 'load'], ['id' => $values['page_id']]);
                    $page->addMedia($media);
                }
                break;
            case 'delete':
                $media->delete();

                $this->setAdminActionLogData('Deleted meida '.$media->getId());

                break;
        }
        if ($this->getRequest()->request->get('page_id') != null) {
            return JsonResponse::create(['success'=>true]);
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
            'Filename - Path' => ['order' => 'filename', 'search' => 'filename'],
            'Mimetype' => ['order' => 'mimetype', 'search' => 'mimetype'],
            'Filesize' => ['order' => 'filesize', 'search' => 'filesize'],
            'Owner' => 'user_id',
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
     */
    protected function getTableElements($data)
    {
        return array_map(
            function ($elem) {
                return [
                'ID' => $elem->getId(),
                'Preview' => $elem->getThumb('100x100', null, null, ['for_admin' => '']),
                'Filename - Path' => $elem->getFilename() .'<br /><abbr style="font-size: 0.6rem;">'. $elem->getPath() .'</abbr>',
                'Mimetype' => $elem->getMimetype(),
                'Filesize' => $this->formatBytes($elem->getFilesize()),
                'Owner' => $elem->getOwner()->username,
                'Lazyload' => $this->getUtils()->translate($elem->getLazyload() ? 'Yes' : 'No', $this->getCurrentLocale()),
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
