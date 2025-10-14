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
use App\Site\Models\DownloadableProduct;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Site\Models\MediaElement;
use App\Base\Models\TaxClass;
use App\Site\Models\MediaElement as Media;

/**
 * "Downloadable Products" Admin Page
 */
class DownloadableProducts extends AdminManageProductsPage
{
    /**
     * @var string page title
     */
    protected ?string $page_title = 'Downloadable Products';

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return DownloadableProduct::class;
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
            'icon' => 'download',
            'text' => 'Downloadable Products',
            'section' => 'commerce',
            'order' => 70,
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
         * @var DownloadableProduct $product
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
                    '&#9776; Media',
                    $this->getUrl('crud.app.site.controllers.admin.json.downloadablemedia', ['id' => $this->getRequest()->query->get('product_id')]) . '?product_id=' . $this->getRequest()->query->get('product_id') . '&action=new',
                    'btn btn-sm btn-light inToolSidePanel'
                );

            case 'new':

                $product_title = $product_content = $product_media = '';
                $product_price = 0.0;
                if ($product->isLoaded()) {
                    $product_title = $product->title;
                    $product_content = $product->content;
                    $product_media = $product->media_id;
                    $product_price = $product->price;
                }

                $medias = ['' => ''];
                foreach (MediaElement::getCollection() as $media) {
                    /** @var MediaElement $media */
                    if ($media->isDirectory()) {
                        comtinue:
                    }
                    $medias[$media->getId()] = $media->getFilename();
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
                ])->addField('media_id', [
                    'type' => 'select',
                    'title' => 'Media',
                    'default_value' => $product_media,
                    'options' => $medias,
                    'validate' => ['required'],
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

                $this->addFrontendFormElements($form, $form_state);
                $this->addSeoFormElements($form, $form_state);

                $this->addSubmitButton($form);

                break;

            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;

            case 'media_deassoc':
                $media = $this->containerCall([Media::class, 'load'], ['id' => $this->getRequest()->query->get('media_id')]);
                $form->addField('product_id', [
                    'type' => 'hidden',
                    'default_value' => $product->id,
                ])->addField('media_id', [
                    'type' => 'hidden',
                    'default_value' => $media->id,
                ])->addField('confirm', [
                    'type' => 'markup',
                    'value' => 'Do you confirm the disassociation of the "' . $product->title . '" product from the media ID: ' . $media->id . '?',
                    'suffix' => '<br /><br />',
                ])->addMarkup('<a class="btn btn-danger btn-sm" href="' . $this->getUrl('crud.app.site.controllers.admin.json.mediadownloadableproducts', ['id' => $media->id]) . '?media_id=' . $media->id . '&action=downloadable_product_deassoc">Cancel</a>');

                $this->addSubmitButton($form, true);
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
         * @var DownloadableProduct $product
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
                $product->setMediaId($values['media_id']);
                $product->setPrice((float)$values['price']);
                $product->setTaxClassId($values['tax_class_id']);

                $this->setAdminActionLogData($product->getChangedData());

                $product->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Product Saved."));
                break;
            case 'delete':
                $product->delete();

                $this->setAdminActionLogData('Deleted product ' . $product->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Product Deleted."));

                break;
            case 'media_deassoc':
                if ($values['media_id']) {
                    $media = $this->containerCall([Media::class, 'load'], ['id' => $values['media_id']]);
                    $product->removeMedia($media);
                }
                break;
        }

        return $this->refreshPage();
    }
}
