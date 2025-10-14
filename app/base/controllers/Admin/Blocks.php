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

namespace App\Base\Controllers\Admin;

use App\App;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Traits\AdminFormTrait;
use App\Base\Models\Rewrite;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use Degami\SqlSchema\Exceptions\OutOfRangeException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Psr\Container\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use HaydenPierce\ClassFinder\ClassFinder;
use App\Base\Abstracts\Controllers\AdminFormPage;
use App\Base\Abstracts\Controllers\AdminManageModelsPage;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Block;

/**
 * "Blocks" Admin Page
 */
class Blocks extends AdminManageModelsPage
{
    use AdminFormTrait;

    /**
     * {@inheritdoc}
     *
     * @param ContainerInterface $container
     * @param Request $request
     * @param RouteInfo $route_info
     * @throws BasicException
     * @throws DependencyException
     * @throws FAPI\Exceptions\FormException
     * @throws NotFoundException
     * @throws PermissionDeniedException
     * @throws OutOfRangeException
     */
    public function __construct(
        protected ContainerInterface $container, 
        protected ?Request $request = null, 
        protected ?RouteInfo $route_info = null
    ) {
        AdminFormPage::__construct($container, $request, $route_info);
        if (($this->getRequest()->query->get('action') ?? 'list') == 'list') {
            $blockClasses = array_merge(
                ClassFinder::getClassesInNamespace(App::BASE_BLOCKS_NAMESPACE), 
                ClassFinder::getClassesInNamespace(App::BLOCKS_NAMESPACE)
            );

            foreach ($blockClasses as $blockClass) {
                $existing_blocks = Block::getCollection()->where(['instance_class' => $blockClass])->getItems();
                if (count($existing_blocks) > 0) {
                    continue;
                }

                /** @var Block $new_block */
                $new_block = $this->containerMake(Block::class);
                $new_block->setRegion(null);
                $new_block->setTitle(str_replace("App\\Site\\Blocks\\", "", $blockClass));
                $new_block->setLocale(null);
                $new_block->setInstanceClass($blockClass);
                $new_block->setContent(null);

                $new_block->persist();
            }
        }

        parent::__construct($container, $request, $route_info);
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
        return 'administer_blocks';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return Block::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'block_id';
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
            'icon' => 'box',
            'text' => 'Blocks',
            'section' => 'cms',
            'order' => 0,
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
        $block = $this->getObject();

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

                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();

                $rewrite_options = [];
                foreach (Rewrite::getCollection() as $rewrite) {
                    /** @var Rewrite $rewrite */
                    $rewrite_options[$rewrite->getId()] = $rewrite->getUrl();
                }

                $block_rewrites = [];
                if ($block->isLoaded()) {
                    $block_rewrites = array_map(
                        function ($el) {
                            return $el->getId();
                        },
                        $block->getRewrites()
                    );
                }

                $block_region = $block_locale = $block_title = $block_content = $block_order = '';
                if ($block->isLoaded()) {
                    $block_region = $block->region;
                    $block_locale = $block->locale;
                    $block_title = $block->title;
                    $block_content = $block->content;
                    $block_order = $block->order;
                }
                $form->addField(
                    'region',
                    [
                        'type' => 'select',
                        'title' => 'Block region',
                        'default_value' => $block_region,
                        'options' => $this->getSiteData()->getBlockRegions(),
                        'validate' => ['required'],
                    ]
                )
                    ->addField(
                        'title',
                        [
                            'type' => 'textfield',
                            'title' => 'Title',
                            'default_value' => $block_title,
                            'validate' => ['required'],
                        ]
                    );
                if ($type == 'new' || $block->instance_class == Block::class) {
                    $form
                        /*->addField(
                            'locale',
                            [
                            'type' => 'select',
                            'title' => 'Locale',
                            'default_value' => $block_locale,
                            'options' => $languages,
                            'validate' => ['required'],
                            ]
                        )*/
                        ->addField(
                            'content',
                            [
                                'type' => 'tinymce',
                                'title' => 'Content',
                                'tinymce_options' => DEFAULT_TINYMCE_OPTIONS,
                                'default_value' => $block_content,
                                'rows' => 20,
                            ]
                        );

                    $this->addFrontendFormElements($form, $form_state, ['website_id', 'locale']);
                }
                $form->addField(
                    'rewrites',
                    [
                        'type' => 'select',
                        'multiple' => true,
                        'title' => 'Rewrites',
                        'default_value' => $block_rewrites,
                        'options' => $rewrite_options,
                    ]
                )
                    ->addField(
                        'order',
                        [
                            'type' => 'textfield',
                            'title' => 'Order',
                            'default_value' => $block_order,
                        ]
                    );


                if ($block != null && method_exists($block->getRealInstance(), 'additionalConfigFieldset')) {
                    $config_fields = call_user_func_array(
                        [$block->getRealInstance(), 'additionalConfigFieldset'],
                        [
                            'form' => $form,
                            'form_state' => &$form_state,
                            'default_values' => json_decode($block->getConfig() ?? "{}", true),
                        ]
                    );
                    if (!empty($config_fields)) {
                        $fieldset = $form->addField(
                            'config',
                            [
                                'type' => 'fieldset',
                                'title' => 'Config',
                            ]
                        );

                        foreach ($config_fields as $key => $config_field) {
                            $fieldset->addField($config_field->getName(), $config_field);
                        }
                    }
                }

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
        // $values = $form->values();
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws Exception
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        /**
         * @var Block $block
         */
        $block = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':
                $block->setUserId($this->getCurrentUser()->getId());
                $block->setInstanceClass(Block::class);

            // intentional fall trough
            // no break
            case 'edit':
                $block->setRegion($values['region']);
                $block->setTitle($values['title']);
                if ($values['frontend'] != null) {
                    $block->setWebsiteId($values['frontend']['website_id']);
                    $block->setLocale($values['frontend']['locale']);
                }
                $block->setOrder(intval($values['order']));

                if ($values['action'] == 'new' || $block->getInstance() == Block::class) {
                    $block->setContent($values['content']);
                }

                if ($values['config'] && !empty($values['config']->getData())) {
                    $block->setConfig(json_encode($values['config']->getData()));
                }

                $this->setAdminActionLogData($block->getChangedData());

                $block->persist();

                $existing_rewrites = array_map(
                    function ($el) {
                        return $el->getId();
                    },
                    $block->getRewrites()
                );

                $new_rewrites = !empty($values['rewrites']) ? array_values($values['rewrites']->getData()) : [];
                $to_delete = array_diff($existing_rewrites, $new_rewrites);
                $remaining = array_diff($existing_rewrites, $to_delete);
                $to_add = array_diff($new_rewrites, $remaining);

                foreach ($this->getDb()->table('block_rewrite')->where('id', $to_delete) as $row) {
                    $row->delete();
                }
                foreach ($to_add as $id_to_add) {
                    try {
                        $this->getDb()->createRow('block_rewrite', [
                            'block_id' => $block->getId(),
                            'rewrite_id' => $id_to_add,
                        ])->save();
                    } catch (Exception $e) {
                    }
                }

                $this->addSuccessFlashMessage($this->getUtils()->translate("Block Saved."));
                break;
            case 'delete':
                $block->delete();

                $this->setAdminActionLogData('Deleted block ' . $block->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Block Deleted."));

                break;
        }

        return $this->refreshPage();
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    protected function getTableHeader(): ?array
    {
        return [
            'ID' => ['order' => 'id'],
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Region' => ['order' => 'region', 'search' => 'region'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Where' => null,
            'Order' => null,
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function ($block) {
                return [
                    'ID' => $block->id,
                    'Website' => $block->getWebsiteId() == null ? $this->getUtils()->translate('All websites', locale: $this->getCurrentLocale()) : $block->getWebsite()->domain,
                    'Region' => $block->region,
                    'Locale' => !$block->isCodeBlock() ? $block->locale : $this->getUtils()->translate('All languages', locale: $this->getCurrentLocale()),
                    'Title' => $block->title,
                    'Where' => (count($block->getRewrites()) == 0) ? $this->getUtils()->translate('All Pages', locale: $this->getCurrentLocale()) : implode(
                        "<br>",
                        array_map(
                            function ($e) {
                                return $e->getUrl();
                            },
                            $block->getRewrites()
                        )
                    ),
                    'Order' => $block->order,
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($block->id),
                        static::DELETE_BTN => $this->getDeleteButton($block->id),
                    ],
                ];
            },
            $data
        );
    }

    protected function hasLayoutSelector(): bool
    {
        return false;
    }    
}
