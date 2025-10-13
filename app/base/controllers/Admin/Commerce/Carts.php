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

namespace App\Base\Controllers\Admin\Commerce;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use App\Base\Abstracts\Controllers\AdminManageFrontendModelsPage;
use App\Base\Models\Address;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Cart as CartModel;
use App\Base\Models\CartDiscount;
use App\Base\Models\CartItem;
use App\Base\Models\Country;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Degami\Basics\Html\TagElement;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;

/**
 * "Carts" Admin Page
 */
class Carts extends AdminManageFrontendModelsPage
{
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
        return 'administer_orders';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getObjectClass(): string
    {
        return CartModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'cart_id';
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
            'icon' => 'shopping-cart',
            'text' => 'Carts',
            'section' => 'commerce',
            'order' => 10,
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
         * @var CartModel $cart
         */
        $cart = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

//        $form->addMarkup('Form Type: '.$type);

        $websites = $this->getUtils()->getWebsitesSelectOptions();
        $users = $this->getUtils()->getUsersSelectOptions();

        switch ($type) {
            case 'edit':
                if (!$cart->getIsActive()) {
                    $this->addReopenButton($cart->getId());
                }

                $this->addActionLink(
                    'discount-btn',
                    'discount-btn',
                    $this->getHtmlRenderer()->getIcon('percent') . 'Discounts',
                    $this->getUrl('crud.app.base.controllers.admin.json.cartdiscounts', ['id' => $this->getRequest()->query->get('cart_id')]) . '?cart_id=' . $this->getRequest()->query->get('cart_id') . '&action=new_discount',
                    'btn btn-sm btn-light inToolSidePanel'
                );

                $this->addActionLink(
                    'billing-btn',
                    'billing-btn',
                    $this->getHtmlRenderer()->getIcon('credit-card') . 'Billing',
                    $this->getUrl('crud.app.base.controllers.admin.json.cartbilling', ['id' => $this->getRequest()->query->get('cart_id')]) . '?cart_id=' . $this->getRequest()->query->get('cart_id') . '&action=billing',
                    'btn btn-sm btn-light inToolSidePanel'
                );

                if ($cart->requireShipping()) {
                    $this->addActionLink(
                        'shipping-btn',
                        'shipping-btn',
                        $this->getHtmlRenderer()->getIcon('truck') . 'Shipping',
                        $this->getUrl('crud.app.base.controllers.admin.json.cartshipping', ['id' => $this->getRequest()->query->get('cart_id')]) . '?cart_id=' . $this->getRequest()->query->get('cart_id') . '&action=shipping',
                        'btn btn-sm btn-light inToolSidePanel'
                    );
                }

            case 'new':
                
                $form->addField('website_id', [
                    'type' => 'select',
                    'title' => 'Website',
                    'options' => $websites,
                    'validate' => ['required'],
                    'default_value' => $cart->getWebsiteId(),
                ])->addField('user_id', [
                    'type' => 'select',
                    'title' => 'User',
                    'options' => $users,
                    'validate' => ['required'],
                    'default_value' => $cart->getUserId(),
                ]);

                /** @var TableContainer */
                $table = $form->addField('cart_items', [
                    'type' => 'table_container',
                    'attributes' => [
                        'class' => 'table table-striped',
                    ],
                ]);

                $table->setTableHeader([
                    $this->getUtils()->translate('Product'),
                    $this->getUtils()->translate('Quantity'),
                    $this->getUtils()->translate('Unit Price'),
                    $this->getUtils()->translate('Subtotal'),
                    $this->getUtils()->translate('Tax'),
                    $this->getUtils()->translate('Row Total'),
                    '',
                ]);

                foreach ($cart->getItems() ?? [] as $item) {
                    $itemTitle = $item->getProduct()->getTitle();
                    if (is_callable([$item->getProduct(), 'getFrontendUrl']) && $item->getProduct()->getFrontendUrl()) {
                        $itemTitle = '<a href="'.$item->getProduct()->getFrontendUrl().'" target="_blank">' . $item->getProduct()->getTitle() . '</a>';
                    }

                    $table->addRow()
                        ->addField('product_name_'.$table->numRows(), [
                            'type' => 'markup', 
                            'value' => $itemTitle,
                        ])
                        ->addField('quantity_'.$item->getId(), [
                            'type' => 'number',
                            'value' => $item->getQuantity(),
                            'attributes' => [
                                'min' => 1,
                                'class' => 'form-control',
                                'style' => 'max-width: 100px;',
                            ],
                        ])
                        ->addField('unit_price_'.$table->numRows(), [
                            'type' => 'markup',
                            'value' => $this->getUtils()->formatPrice($item->getUnitPrice(), $item->getCurrencyCode()),
                        ])
                        ->addField('subtotal_'.$table->numRows(), [
                            'type' => 'markup',
                            'value' => $this->getUtils()->formatPrice($item->getSubTotal(), $item->getCurrencyCode()),
                        ])
                        ->addField('tax_'.$table->numRows(), [
                            'type' => 'markup',
                            'value' => $this->getUtils()->formatPrice($item->getTaxAmount(), $item->getCurrencyCode()),
                        ])
                        ->addField('total_'.$table->numRows(), [
                            'type' => 'markup',
                            'value' => $this->getUtils()->formatPrice($item->getTotalInclTax(), $item->getCurrencyCode()),
                        ])
                        ->addField('remove_'.$table->numRows(), [
                            'type' => 'markup',
                            'value' => $this->getDeleteCartItemButton($item),
                            'container_class' => 'text-right'
                        ]);
                }

                $this->addSubmitButton($form);

                break;
            case 'reopen':
                $this->fillConfirmationForm('Do you confirm the re-opening of the selected element?', $form);
                break;
            case 'delete_cart_item':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form, $this->getControllerUrl().'?action=edit&'.$this->getObjectIdQueryParam().'='.$cart->getId());
                break;
            case 'delete':
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
            case 'new_discount':
                $form->addMarkup('<h4>'.$this->getUtils()->translate('Add a custom discount').'</h4>');
                $form->addField('is_json', [
                    'type' => 'hidden',
                    'default_value' => 1,
                ]);
                $form->addField('discount_amount', [
                    'type' => 'textfield',
                    'title' => 'Discount Amount',
                    'validate' => ['numeric'],
                    'description' => $cart->getCurrencyCode(),
                ]);
                $this->addSubmitButton($form);
                break;
            case 'remove_discount':
                $form->addField('discount_id', [
                    'type' => 'hidden',
                    'default_value' => $this->getRequest()->query->get('discount_id'),
                ]);
                $form->addField('is_json', [
                    'type' => 'hidden',
                    'default_value' => 1,
                ]);
                $this->fillConfirmationForm('Do you confirm the deletion of the selected element?', $form);
                break;
            case 'billing':
                $form = $this->fillAddressForm($cart, $form);
                $this->addSubmitButton($form);
                break;
            case 'shipping':
                $form = $this->fillAddressForm($cart, $form);
                $this->addSubmitButton($form);
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
        $values = $form->getValues();

        switch ($values['action']) {
            case 'billing':
            case 'shipping':

                if (empty($values->copy_address)) {
                    $requiredFields = ['first_name', 'last_name', 'address1', 'city', 'postcode', 'country_code', 'email'];
                    foreach ($requiredFields as $field) {
                        if (empty($values->{$field})) {
                            return $this->getUtils()->translate('Please fill in all required fields.');
                        }
                    }

                    if (!filter_var($values->email, FILTER_VALIDATE_EMAIL)) {
                        return $this->getUtils()->translate('Please provide a valid email address.');
                    }
                }

                break;
        }

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
         * @var CartModel $cart
         */
        $cart = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'new':

            // intentional fall trough
            // no break
            case 'edit':

                $cart->setWebsiteId($values['website_id'])->setUserId($values['user_id']);

                foreach($values->cart_items->getData() as $key => $value) {
                    if (preg_match('/^quantity_/', $key)) {
                        $item_id = str_replace('quantity_', '', $key);
                        $quantity = (int) $value;
                        if ($quantity > 0) {
                            $cart->getCartItem($item_id)?->setQuantity($quantity);
                        } else {
                            $cart->removeItem($item_id);
                        }
                    }
                }

                $this->setAdminActionLogData($cart->getChangedData());

                $cart->calculate()->persist();

                $this->addSuccessFlashMessage($this->getUtils()->translate("Cart Saved."));
                break;
            case 'reopen':
                foreach (CartModel::getCollection()->where(['user_id' => $cart->getUserId()])->getItems() as $elem) {
                    /** @var CartModel $elem */
                    $elem->setIsActive(false)->persist();
                }

                $cart->setIsActive(true)->persist();

                $this->setAdminActionLogData('Re-opened cart ' . $cart->getId());

                $this->addSuccessFlashMessage($this->getUtils()->translate("Cart Saved."));
                break;
            case 'delete_cart_item':
                $cartItemId = $this->getRequest()->query->get('cart_item_id');
                if ($cartItemId) {
                    if ($cart->getCartItem($cartItemId)) {
                        $cart->removeItem($cartItemId)->calculate()->persist();

                        $this->setAdminActionLogData('Deleted cart item ' . $cartItemId . ' from cart ' . $cart->getId());

                        $this->addInfoFlashMessage($this->getUtils()->translate("Cart Item Deleted."));
                    }
                }

                return $this->doRedirect($this->getControllerUrl(). '?action=edit&'.$this->getObjectIdQueryParam().'='.$cart->getId());

                break;
            case 'delete':
                $cart->delete();

                $this->setAdminActionLogData('Deleted cart ' . $cart->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Cart Deleted."));

                break;
            case 'new_discount':
                /** @var CartDiscount $cartDiscount */
                $cartDiscount = $this->containerMake(CartDiscount::class);
                $cartDiscount
                    ->setCart($cart)
                    ->setDiscountAmount($values->discount_amount)
                    ->setAdminDiscountAmount($this->getUtils()->convertFromCurrencyToCurrency($values->discount_amount, $cart->getCurrencyCode(), $cart->getAdminCurrencyCode()))
                    ->persist();

                $cart->resetDiscounts()->calculate()->persist();
                break;
            case 'remove_discount':
                $discountId = $values->discount_id;
                $cartDiscount = CartDiscount::load($discountId);
                if ($cartDiscount) {
                    $cartDiscount->delete();
                }

                $cart->calculate()->persist();
                break;
            case 'billing':
            case 'shipping':
                if (!empty($values->copy_address)) {
                    $address = Address::load($values->copy_address);
                } else {
                    $address = new Address();
                    $address
                        ->setUserId($this->getCurrentUser()->getId())
                        ->setWebsiteId($this->getCurrentWebsiteId())
                        ->setFirstName($values->first_name)
                        ->setLastName($values->last_name)
                        ->setCompany($values->company)
                        ->setAddress1($values->address1)
                        ->setAddress2($values->address2)
                        ->setCity($values->city)
                        ->setState($values->state)
                        ->setPostcode($values->postcode)
                        ->setCountryCode($values->country_code)
                        ->setPhone($values->phone)
                        ->setEmail($values->email)
                        ->persist();
                }

                if ($values['action'] == 'shipping') {
                    $cart->setShippingAddress($address);
                } else {
                    $cart->setBillingAddress($address);
                }

                $cart->calculate()->persist();
                break;
        }

        if ($values->is_json != null) {
            return new JsonResponse(['success' => true]);
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
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Is Active' => ['order' => 'is_active'],
            'Customer' => ['order' => 'created_at', 'foreign' => 'user_id', 'table' => $this->getModelTableName(), 'view' => 'email'],
            'Total' => ['order' => 'total_incl_tax'],
            'Created At' => ['order' => 'created_at', 'search' => 'created_at'],
            'Updated At' => ['order' => 'updated_at', 'search' => 'updated_at'],
            'actions' => null,
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param array $data
     * @return array
     * @throws BasicException
     * @throws Exception
     */
    protected function getTableElements(array $data): array
    {
        return array_map(
            function (CartModel $cart) {
                return [
                    'ID' => $cart->id,
                    'Website' => $cart->getWebsiteId() == null ? 'All websites' : $cart->getWebsite()->domain,
                    'Is Active' => $cart->getIsActive() ? $this->getUtils()->translate('Yes') : $this->getUtils()->translate('No'),
                    'Customer' => $cart->getOwner()->getEmail(),
                    'Total' => $this->getUtils()->formatPrice($cart->getTotalInclTax(), $cart->getCurrencyCode()),
                    'Created At' => $cart->getCreatedAt(),
                    'Updated At' => $cart->getUpdatedAt(),
                    'actions' => [
                        static::EDIT_BTN => $this->getEditButton($cart->id),
                        static::DELETE_BTN => $this->getDeleteButton($cart->id),
                    ],
                ];
            },
            $data
        );
    }

    public function addReopenButton(int $object_id) : void
    {
        $query_params = http_build_query(['action' => 'reopen', 'cart_id' => $object_id]);
        $this->addActionLink('reopen-btn', 'reopen-btn', $this->getHtmlRenderer()->getIcon('book-open') . ' ' . $this->getUtils()->translate('Re-Open', locale: $this->getCurrentLocale()), $this->getControllerUrl() . '?' . $query_params);
    }

    /**
     * gets delete button html
     *
     * @param int $object_id
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDeleteCartItemButton(CartItem $cartItem): string
    {
        $title = 'Delete Cart Item';
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-danger',
                    'href' => $this->getControllerUrl() . '?action=delete_cart_item&' . $this->getObjectIdQueryParam() . '=' . $cartItem->getCartId().'&cart_item_id='.$cartItem->getId(),
                    'title' => (trim($title) != '') ? $this->getUtils()->translate($title, locale: $this->getCurrentLocale()) : '',
                ],
                'text' => $this->getHtmlRenderer()->getIcon('trash'),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {}

        return '';
    }

    protected function fillAddressForm(CartModel $cart, FAPI\Form $form) : FAPI\Form
    {
        $countriesItems = Country::getCollection()->getItems();
        $countries = array_combine(
            array_map(fn($el) => $el->getIso2(), $countriesItems),
            array_map(fn($el) => $el->getNameEn(), $countriesItems),
        );

        $addressesItems = $this->getAddresses($cart);
        $addresses = array_combine(
            array_map(fn($el) => $el->getId(), $addressesItems),
            array_map(fn($el) => $el->getFullAddress(), $addressesItems),
        );

        $form->addMarkup('<div class="row mt-3">'.$this->getUtils()->translate('Choose an existing Address').'</div>');

        $form->addField('copy_address', [
            'type' => 'select',
            'title' => '',
            'options' => ['' => '-- Select --'] + $addresses,
            'default_value' => $cart->getBillingAddressId(),
        ]);

        $form->addMarkup('<div class="row mt-3">'.$this->getUtils()->translate('or create a new one').'</div>');

        $form
            ->addMarkup('<div class="row mt-3">')
            ->addField('first_name', [
                'type' => 'textfield',
                'title' => 'First Name <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('last_name', [
                'type' => 'textfield',
                'title' => 'Last Name <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('company', [
                'type' => 'textfield',
                'title' => 'Company',
                'container_class' => 'col-sm-12 pb-2',
                'default_value' => '',
            ])
            ->addField('address1', [
                'type' => 'textfield',
                'title' => 'Address 1 <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('address2', [
                'type' => 'textfield',
                'title' => 'Address 2',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('city', [
                'type' => 'textfield',
                'title' => 'City <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('state', [
                'type' => 'textfield',
                'title' => 'State',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('postcode', [
                'type' => 'textfield',
                'title' => 'Post Code <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'default_value' => '',
            ])
            ->addField('country_code', [
                'type' => 'select',
                'title' => 'Country <span class="required">*</span>',
                'container_class' => 'col-sm-6 pb-2',
                'options' => ['' => '-- Select --'] + $countries,
                'default_value' => '',
            ])
            ->addField('phone', [
                'type' => 'textfield',
                'title' => 'Phone',
                'container_class' => 'col-sm-6',
                'default_value' => '',
            ])
            ->addField('email', [
                'type' => 'textfield',
                'title' => 'Email <span class="required">*</span>',
                'container_class' => 'col-sm-6',
                'default_value' => '',
            ])
            ->addMarkup('</div>');

        return $form;
    }

    protected function getAddresses(CartModel $cart) : array
    {
        return Address::getCollection()->where(['user_id' => $cart->getUserId()])->getItems();
    }

    protected function beforeRender(): BasePage|Response 
    {
        if (App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false) == false) {
            $this->addWarningFlashMessage($this->getUtils()->translate("Commerce functionallity is currently disabled"), true);
        }
        return parent::beforeRender();
    }
}
