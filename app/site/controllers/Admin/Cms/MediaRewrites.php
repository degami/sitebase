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
use App\Site\Models\MediaElement;
use App\Base\Models\Rewrite;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\MediaElementRewrite;
use Degami\Basics\Html\TagElement;

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
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        parent::__construct($container, $request, $route_info);
        $this->page_title = 'Rewrite / Media';
    }

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
        return 'administer_medias';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return MediaElementRewrite::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'media_rewrite_id';
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
            'icon' => 'layers',
            'text' => 'Rewrites Media',
            'section' => 'site',
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
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $type = $this->getRequest()->query->get('action') ?? 'list';
        /** @var MediaElementRewrite $media_rewrite */
        $media_rewrite = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
            case 'new':

                $rewrites = ['none' => ''];
                foreach (Rewrite::getCollection() as $rewrite) {
                    /** @var Rewrite $rewrite */
                    $rewrites[$rewrite->getId()] = $rewrite->getUrl() . " ({$rewrite->getRoute()})";
                }

                $medias = ['' => ''];
                foreach (MediaElement::getCollection() as $media) {
                    /** @var MediaElement $media */
                    if ($media->isDirectory()) {
                        comtinue:
                    }
                    $medias[$media->getId()] = $media->getFilename();
                }

                $media_rewrite_rewrite_id = $media_rewrite_media_id = '';
                if ($media_rewrite->isLoaded()) {
                    $media_rewrite_rewrite_id = $media_rewrite->getRewriteId();
                    $media_rewrite_media_id = $media_rewrite->getMediaElementId();
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
         * @var MediaElementRewrite $media_rewrite
         */
        $media_rewrite = $this->getObject();

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
            case 'edit':
                $media_rewrite->setRewriteId($values['rewrite_id'] == 'none' ? null : $values['rewrite_id']);
                $media_rewrite->setMediaElementId($values['media_id']);
                $media_rewrite->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Media Rewrite Saved."));
                break;
            case 'delete':
                $media_rewrite->delete();

                $this->addInfoFlashMessage($this->getUtils()->translate("Media Rewrite Deleted."));

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
     * {@inheritdoc}
     *
     * @param array $data
     * @param array $options
     * @return array
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($elem) {
                return [
                    'ID' => $elem->getId(),
                    'Preview' => $elem->getMediaElement()->getThumb('100x100'),
                    'Filename - Path' => $elem->getMediaElement()->getFilename(),
                    'Website' => $elem->getRewrite()?->getWebsite()?->getDomain() ?? 'All',
                    'Rewrite - Url' => $elem->getRewrite()?->getUrl() ?? 'Everywhere',
                    'Locale' => $elem->getRewrite()?->getLocale() ?? 'Any',
                    'Owner' => $elem->getOwner()->username,
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($elem->id),
                        static::DELETE_BTN => $this->getDeleteButton($elem->id),
                    ],
                ];
            },
            $data
        );
    }

    public function getGridCardBody(array $element, bool $selectCheckboxes = false) : TagElement
    {
        /** @var TagElement $cardBody */
        $cardBody = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'attributes' => ['class' => "card-body pb-0 container"],
            ]]
        );

        if ($selectCheckboxes == true) {
            if (isset($element['_admin_table_item_pk']) && (
                isset($element['actions'][AdminManageModelsPage::EDIT_BTN]) ||
                isset($element['actions'][AdminManageModelsPage::DELETE_BTN])                        
            )) {
                $cardBody->addChild(
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'label',
                            'text' => '<input class="table-row-selector" type="checkbox" /><span class="checkbox__icon"></span>',
                            'attributes' => ['class' => 'checkbox position-absolute', 'style' => 'top: 10px; right: 10px'],
                        ]]
                    )
                );
            }
        }

        /** @var TagElement $target */
        $target = $this->containerMake(
            TagElement::class,
            ['options' => [
                'tag' => 'div',
                'attributes' => ['class' => 'row'],
            ]]
        );
        $cardBody->addChild($target);

        if (isset($element['Filename - Path'])) {
            $cardBody->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'div',
                        'text' => $element['Filename - Path'],
                        'attributes' => ['class' => 'mt-2 word-break'],
                    ]]
                )
            );
        }

        if (isset($element['Preview'])) {
            $target->addChild(
                $this->containerMake(
                    TagElement::class,
                    ['options' => [
                        'tag' => 'div',
                        'text' => $element['Preview'],
                        'attributes' => ['class' => 'col-auto p-1'],
                    ]]
                )
            );

            $newtarget = $this->containerMake(
                TagElement::class,
                ['options' => [
                    'tag' => 'div',
                    'attributes' => ['class' => 'col'],
                ]]
            );

            $target->addChild($newtarget);
            $target = $newtarget;
        }

        foreach ($element as $tk => $dd) {
            if ($tk == 'actions' || $tk == '_admin_table_item_pk' || $tk == 'Preview' || $tk == 'Filename - Path') {
                continue;
            }
            $target->addChild(
                ($dd instanceof TagElement) ? $dd :
                    $this->containerMake(
                        TagElement::class,
                        ['options' => [
                            'tag' => 'div',
                            'text' => '<label class="mb-0 mr-2 font-weight-bold">'.(string)$tk . ':</label>' . (string)$dd,
                            'attributes' => ['class' => in_array(strtolower($tk), ['website', 'locale']) ? 'nowrap' : 'text-break'],
                        ]]
                    )
            );
        }

        return $cardBody;
    }

    public static function exposeDataToDashboard() : mixed
    {
        return null;
    }
}
