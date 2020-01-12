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
use \HaydenPierce\ClassFinder\ClassFinder;
use \App\Base\Abstracts\AdminFormPage;
use \App\Base\Abstracts\AdminManageModelsPage;
use \Degami\PHPFormsApi as FAPI;
use \App\Site\Models\Block;

/**
 * "Blocks" Admin Page
 */
class Blocks extends AdminManageModelsPage
{
    /**
     * {@inheritdocs}
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        AdminFormPage::__construct($container);
        if (($this->getRequest()->get('action') ?? 'list') == 'list') {
            $blockClasses = ClassFinder::getClassesInNamespace('App\Site\Blocks');

            foreach ($blockClasses as $blockClass) {
                $existing_blocks = $this->getContainer()->call([Block::class, 'where'], ['condition' => ['instance_class' => $blockClass] ]);
                if (count($existing_blocks) > 0) {
                    continue;
                }

                $new_block = $this->getContainer()->make(Block::class);
                $new_block->region = null;
                $new_block->title = str_replace("App\\Site\\Blocks\\", "", $blockClass);
                $new_block->locale = null;
                $new_block->instance_class = $blockClass;
                $new_block->content = null;

                $new_block->persist();
            }
        }

        parent::__construct($container);
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName()
    {
        return 'blocks';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_blocks';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getObjectClass()
    {
        return Block::class;
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
        $block = null;
        if ($this->getRequest()->get('block_id')) {
            $block = $this->loadObject($this->getRequest()->get('block_id'));
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

                $languages = $this->getUtils()->getSiteLanguagesSelectOptions();

                $rewrite_options = [];
                foreach ($this->getDb()->rewrite()->fetchAll() as $rewrite) {
                    $rewrite_options[$rewrite->id] = $rewrite->url;
                }

                $block_rewrites = [];
                if ($block instanceof Block) {
                    $block_rewrites = array_map(
                        function ($el) {
                            return $el->getId();
                        },
                        $block->getRewrites()
                    );
                }

                $block_region = $block_locale = $block_title = $block_content = $block_order = '';
                if ($block instanceof Block) {
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
                    'options' => $this->getUtils()->getBlockRegions(),
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
                    $form->addField(
                        'locale',
                        [
                        'type' => 'select',
                        'title' => 'Locale',
                        'default_value' => $block_locale,
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
                            'default_value' => $block_content,
                            'rows' => 20,
                            ]
                        );
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

                $form->addField(
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
         * @var Block $block
         */
        $block = $this->newEmptyObject();
        if ($this->getRequest()->get('block_id')) {
            $block = $this->loadObject($this->getRequest()->get('block_id'));
        }

        $values = $form->values();
        switch ($values['action']) {
            case 'new':
                $block->user_id = $this->getCurrentUser()->id;
                $block->instance_class = Block::class;

                // intentional fall trough
                // no break
            case 'edit':
                $block->region = $values['region'];
                $block->title = $values['title'];
                $block->locale = $values['locale'];
                $block->order = $values['order'];

                if ($values['action'] == 'new' || $block->getInstance() == Block::class) {
                    $block->content = $values['content'];
                }

                if ($values['config'] && !empty($values['config']->getData())) {
                    $block->config = json_encode($values['config']->getData());
                }

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
                    $this->getDb()->createRow(
                        'block_rewrite',
                        [
                        'block_id' => $block->id,
                        'rewrite_id' => $id_to_add,
                        ]
                    )
                        ->save();
                }

                break;
            case 'delete':
                $block->delete();
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
            'ID' => ['order' => 'id'],
            'Website' => ['order' => 'website_id'],
            'Region' => ['order' => 'region', 'search' => 'region'],
            'Locale' => ['order' => 'locale', 'search' => 'locale'],
            'Title' => ['order' => 'title', 'search' => 'title'],
            'Where' => null,
            'Order' => null,
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
            function ($block) {
                return [
                'ID' => $block->id,
                'Website' => $block->getWebsiteId() == null ? $this->getUtils()->translate('All websites', $this->getCurrentLocale()) : $block->getWebsite()->domain,
                'Region' => $block->region,
                'Locale' => $block->isCodeBlock() ? $block->locale : $this->getUtils()->translate('All languages', $this->getCurrentLocale()),
                'Title' => $block->title,
                'Where' => (count($block->getRewrites())== 0) ? $this->getUtils()->translate('All Pages', $this->getCurrentLocale()) : implode(
                    "<br>",
                    array_map(
                        function ($e) {
                            return $e->getUrl();
                        },
                        $block->getRewrites()
                    )
                ),
                'Order' => $block->order,
                'actions' => '<a class="btn btn-primary btn-sm" href="'. $this->getControllerUrl() .'?action=edit&block_id='. $block->id.'">'.$this->getUtils()->getIcon('edit') .'</a>' .
                    ((!$block->isCodeBlock()) ? '<a class="btn btn-danger btn-sm" href="'. $this->getControllerUrl() .'?action=delete&block_id='. $block->id.'">'.$this->getUtils()->getIcon('trash') .'</a>' : '')
                ];
            },
            $data
        );
    }
}
