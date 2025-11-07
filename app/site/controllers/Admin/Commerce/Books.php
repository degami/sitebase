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

namespace App\Site\Controllers\Admin\Commerce;

use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Abstracts\Controllers\AdminManageProductsPage;
use Degami\PHPFormsApi as FAPI;
use App\Site\Models\Book;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Models\TaxClass;

/**
 * "Books" Admin Page
 */
class Books extends AdminManageProductsPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Books';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return Book::class;
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
            'icon' => 'book',
            'text' => 'Books',
            'section' => 'commerce',
            'order' => 80,
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

        /**
         * @var Book $product
         */
        $product = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
                $this->addActionLink(
                    'media-btn',
                    'media-btn',
                    $this->getHtmlRenderer()->getIcon('image') . ' ' . $this->getUtils()->translate('Media', locale: $this->getCurrentLocale()),
                    $this->getUrl('crud.app.site.controllers.admin.json.bookmedia', ['id' => $this->getRequest()->query->get('product_id')]) . '?product_id=' . $this->getRequest()->query->get('product_id') . '&product_type=book&action=new',
                    'btn btn-sm btn-light inToolSidePanel'
                );

                // intentional fall trough
                // no break
            case 'new':

                $product_title = $product_content = '';
                $product_price = $product_weight = $product_length =$product_width = $product_height = 0.0;
                if ($product->isLoaded()) {
                    $product_title = $product->title;
                    $product_content = $product->content;
                    $product_price = $product->price;
                    $product_weight = $product->weight;
                    $product_length = $product->length;
                    $product_width = $product->width;
                    $product_height = $product->height;
                }

                $tax_classes = ['' => '-- Select --'];
                foreach(TaxClass::getCollection() as $tax_class) {
                    /** @var TaxClass $tax_class */
                    $tax_classes[$tax_class->getId()] = $tax_class->getClassName();
                }

                $form->addField('title', [
                    'type' => 'textfield',
                    'title' => 'Title',
                    'default_value' => $product_title,
                    'validate' => ['required'],
                ])->addField('content', [
                    'type' => 'tinymce',
                    'title' => 'Content',
                    'tinymce_options' => DEFAULT_TINYMCE_OPTIONS,
                    'default_value' => $product_content,
                    'rows' => 20,
                ])->addField('price', [
                    'type' => 'number',
                    'title' => 'Price',
                    'default_value' => $product_price,
                    'min' => '0.00',
                    'max' => '1000000.00',
                    'step' => '0.01',
                    'validate' => ['required'],
                ])->addField('tax_class_id', [
                    'type' => 'select',
                    'title' => 'Tax Class',
                    'default_value' => $product->getTaxClassId(),
                    'options' => $tax_classes,
                    'validate' => ['required'],
                ]);

                $this->addPhysicalProductFormElements($form, $form_state);
                $this->addFrontendFormElements($form, $form_state);
                $this->addSeoFormElements($form, $form_state);

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
         * @var Book $product
         */
        $product = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':

            // intentional fall trough
            // no break
            case 'edit':

                $product->setUrl($values['frontend']['url']);
                $product->setTitle($values['title']);
                $product->setLocale($values['frontend']['locale']);
                $product->setContent($values['content']);
                $product->setWebsiteId($values['frontend']['website_id']);
                $product->setWeight((float)$values['physical']['weight']);
                $product->setHeight((float)$values['physical']['height']);
                $product->setLength((float)$values['physical']['length']);
                $product->setWidth((float)$values['physical']['width']);
                $product->setPrice((float)$values['price']);
                $product->setTaxClassId($values['tax_class_id']);

                $this->setAdminActionLogData($product->getChangedData());

                $product->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Book Saved."));
                break;
            case 'delete':
                $product->delete();

                $this->setAdminActionLogData('Deleted book ' . $product->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Book Deleted."));

                break;
        }

        return $this->refreshPage();
    }
}
