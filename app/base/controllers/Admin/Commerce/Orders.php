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
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\Order as OrderModel;
use App\Base\Models\OrderItem;
use App\Base\Models\OrderPayment;
use App\Base\Models\OrderStatus;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Models\OrderComment;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Controllers\BasePage;
use App\Base\Models\OrderShipment;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * "Orders" Admin Page
 */
class Orders extends AdminManageFrontendModelsPage
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
        return OrderModel::class;
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getObjectIdQueryParam(): string
    {
        return 'order_id';
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
            'icon' => 'check',
            'text' => 'Orders',
            'section' => 'commerce',
            'order' => 20,
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
         * @var OrderModel $order
         */
        $order = $this->getObject();

        $form->addField('action', [
            'type' => 'value',
            'value' => $type,
        ]);

        switch ($type) {
            case 'edit':
                if ($order->requiresShipping() || $order->getShipments()) {
                    $this->addActionLink(
                        'ship-btn',
                        'ship-btn',
                        $this->getHtmlRenderer()->getIcon('truck') .' ' . $this->getUtils()->translate('Shipments', locale: $this->getCurrentLocale()),
                        $this->getUrl('crud.app.base.controllers.admin.json.ordershipment', ['id' => $this->getRequest()->query->get('order_id')]) . '?order_id=' . $this->getRequest()->query->get('order_id') . '&action=ship',
                        'btn btn-sm btn-light inToolSidePanel'
                    );
                }

                // intenitional fallthrough

            case 'view':

                if (in_array($order->getOrderStatus()?->getStatus(), [OrderStatus::PAID, OrderStatus::WAITING_FOR_PAYMENT])) {
                    $orderPayment = OrderPayment::getCollection()->where(['order_id' => $order->getId()])->addOrder(['created_at' => 'DESC'])->getFirst();
                    if ($orderPayment) {
                        $this->addActionLink('payment-btn', 'payment-btn', $this->getHtmlRenderer()->getIcon('dollar-sign') . ' ' . $this->getUtils()->translate('Payment', locale: $this->getCurrentLocale()), $this->getUrl('admin.commerce.orderpayments') . '?' . http_build_query(['action' => 'view', 'payment_id' => $orderPayment->getId()]) );
                    }
                }

                $form->addMarkup('<h2>'.$order->getOrderNumber() . ' - ' . $order->getOrderStatus()->getStatus().'</h2>');

                $form->addMarkup(
                    $this->getCardHtml(
                        $this->getUtils()->translate('Customer'),
                        $order->getOwner()->getEmail() . ' (' . $order->getOwner()->getFullName() . ')'
                    )
                );

                $form->addMarkup(
                    $this->getCardHtml(
                        $this->getUtils()->translate('Billing'),
                        $order->getBillingAddress()->getFullContact() . '<br />' .
                        $order->getBillingAddress()->getFullAddress()
                    )
                );

                if ($order->getShippingAddress()) {
                    $form->addMarkup(
                        $this->getCardHtml(
                            $this->getUtils()->translate('Shipping'),
                            $order->getShippingAddress()->getFullContact() . '<br />' .
                            $order->getShippingAddress()->getFullAddress()
                        )
                    );
                }

                $table = $form->addField('order_items', [
                    'type' => 'table_container',
                    'title' => 'Order Items',
                    'attributes' => [
                        'class' => 'table table-striped',
                    ],
                    'prefix' => '<div class="card mt-3"><div class="card-header"><h6 class="cart-title">'.$this->getUtils()->translate('Order Items').'</h6></div><div class="card-body">',
                    'suffix' => '</div></div>',
                ]);

                $table->setTableHeader([
                    $this->getUtils()->translate('Id'),
                    $this->getUtils()->translate('Product'),
                    $this->getUtils()->translate('Quantity'),
                    $this->getUtils()->translate('Unit Price'),
                    $this->getUtils()->translate('Subtotal'),
                    $this->getUtils()->translate('Tax'),
                    $this->getUtils()->translate('Total'),
                ]);
                
                foreach ($order->getItems() as $item) {
                    $table->addRow()
                        ->addField('item_id_'.$table->numRows(), ['type' => 'markup', 'value' => $item->getId()])
                        ->addField('product_'.$table->numRows(), ['type' => 'markup', 'value' => $item->getProduct()->getName()])
                        ->addField('quantity_'.$table->numRows(), ['type' => 'markup', 'value' => $item->getQuantity()])
                        ->addField('unit_price_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getUnitPrice(), $item->getCurrencyCode())])
                        ->addField('subtotal_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getSubTotal(), $item->getCurrencyCode())])
                        ->addField('tax_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getTaxAmount(), $item->getCurrencyCode())])
                        ->addField('total_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getTotalInclTax(), $order->getCurrencyCode())]);
                }

                $form->addMarkup(
                    '<h5>' .
                        $this->getUtils()->translate('Order Total') . ': ' .
                        $this->getUtils()->formatPrice($order->getTotalInclTax(), $order->getCurrencyCode()) . 
                    '</h5>' 
                );
                $form->addMarkup(
                    '<h6>' .
                        $this->getUtils()->translate('Subtotal') . ': ' .
                        $this->getUtils()->formatPrice($order->getSubTotal(), $order->getCurrencyCode()) . 
                    '</h6>' 
                );
                $form->addMarkup(
                    '<h6>' .
                        $this->getUtils()->translate('Discounts') . ': ' .
                        $this->getUtils()->formatPrice($order->getDiscountAmount(), $order->getCurrencyCode()) . 
                    '</h6>' 
                );
                $form->addMarkup(
                    '<h6>' .
                        $this->getUtils()->translate('Tax') . ': ' .
                        $this->getUtils()->formatPrice($order->getTaxAmount(), $order->getCurrencyCode()) . 
                    '</h6>'
                );
                if ($order->getShippingAddress()) {
                    $form->addMarkup(
                        '<h6>' .
                            $this->getUtils()->translate('Shipping') . ': ' .
                            $this->getUtils()->formatPrice($order->getShippingAmount(), $order->getCurrencyCode()) . 
                        '</h6>'
                    );
                }

                if (!empty($order->getComments()->getItems())) {
                    $form->addMarkup('<hr /><h5>' . $this->getUtils()->translate('Comments') . ':</h5>');
                    foreach ($order->getComments()->getItems() as $orderComment) {
                        /** @var OrderComment $orderComment */

                        $form->addMarkup(
                            $this->getCardHtml(
                                $this->getUtils()->translate("on %s from %s", [$orderComment->getCreatedAt(), $orderComment->getOwner()->getNickname()]),
                                $orderComment->getComment()
                            )
                        );
                    }
                }

                if ($type == 'edit') {

                    $form->addMarkup('<hr />');

                    $orderOptions = ['' => '-- Select --'];
                    foreach (OrderStatus::getCollection()->getItems() as $status) {
                        $orderOptions[$status->getId()] = $status->getStatus();
                    }

                    $form->addField('status', [
                        'type' => 'select',
                        'title' => 'Change Order Status',
                        'options' => $orderOptions,
                        'default_value' => $order->getOrderStatusId(),
                        'validate' => ['required'],
                    ]);

                    $form->addField('comment', [
                        'type' => 'textarea',
                        'title' => 'Add a comment',
                        'rows' => 5,
                    ]);

                    $form->addField('notify_email', [
                        'type' => 'switchbox',
                        'title' => 'Notify by Email',
                        'default_value' => false,
                    ]);
    
                    $this->addSubmitButton($form);
                }

                break;

            case 'ship':
                $form->addMarkup('<h4>'.$this->getUtils()->translate('Create new Shipment', locale: $this->getCurrentLocale()).'</h4>');

                $form->addField('shipping_method', [
                    'type' => 'textfield',
                    'title' => 'Shipping Method',
                    'default_value' => '',
                ])->addField('shipment_code', [
                    'type' => 'textfield',
                    'title' => 'Shipment Code',
                    'default_value' => '',
                ]);

                foreach ($order->getItems() as $orderItem) {
                    /** @var OrderItem $orderItem */
                    if ($orderItem->requireShipping()) {
                        $form->addField('row_'.$orderItem->getId(), [
                            'type' => 'fieldset',
                            'title' => $orderItem->getProduct()->getName() . ' ('.$orderItem->getProduct()->getSku().')',
                            'inner_attributes' => [
                                'class' => 'row',
                            ]
                        ])->addField('ship_'.$orderItem->getId(), [
                            'type' => 'checkbox',
                            'title' => 'Ship',
                            'default_value' => true,
                            'label_class' => 'mr-2',
                            'container_class' => 'col-1',
                        ])->addField('ship_'.$orderItem->getId().'_qty', [
                            'type' => 'number',
                            'title' => 'Quantity',
                            'default_value' => $orderItem->getQuantity(),
                            'label_class' => 'mr-2',
                            'container_class' => 'col-11 d-flex',
                        ]);
                    }
                }

                $this->addSubmitButton($form);
                break;

            case 'cancel':
                $this->fillConfirmationForm('Do you confirm the cancellation of the selected element?', $form);
                break;
        }

        return $form;
    }

    protected function getCardHtml(string $title, string $content): string
    {
        return '<div class="card mt-3">
  <div class="card-header">
    <h6 class="cart-title">'.$title.'</h6>
  </div>
  <div class="card-body">
    <p class="card-text">'.$content.'</p>
  </div>
</div>';
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
         * @var OrderModel $order
         */
        $order = $this->getObject();

        $values = $form->values();

        switch ($values['action']) {
            case 'edit':

                $order->setOrderStatus(OrderStatus::getCollection()->where(['id' => $values['status']])->getFirst());

                $this->setAdminActionLogData($order->getChangedData());

                $order->persist();

                if (!empty($values->comment)) {
                    /** @var OrderComment $orderComment */
                    $orderComment = $this->containerMake(OrderComment::class);
                    $orderComment
                        ->setOrderId($order->getId())
                        ->setWebsiteId($order->getWebsiteId())
                        ->setUserId($this->getCurrentUser()->getId())
                        ->setComment($values->comment)
                        ->persist();

                    if ($values->notify_email) {
                        $this->getUtils()->queueTemplateMail(
                            App::getInstance()->getSiteData()->getConfigValue('commerce/emails/customer_care') ?? App::getInstance()->getSiteData()->getSiteEmail(),
                            $order->getBillingAddress()->getEmail(),
                            $this->getUtils()->translate("New comment on your order %s", [$order->getOrderNumber()], locale: $order->getOwner()->getLocale()),
                            ['orderNumber' => $order->getOrderNumber(), 'comment' => $values->comment],
                            'commerce/order_comment'
                        );
                    }
                }

                $this->addInfoFlashMessage($this->getUtils()->translate("Order updated."));

                break;
            case 'ship':

                $shipmentItems = [];
                foreach ($order->getItems() as $orderItem) {
                    /** @var OrderItem $orderItem */
                    if ($orderItem->requireShipping()) {
                        if ($values['row_'.$orderItem->getId()]['ship_'.$orderItem->getId()]) {
                            $shipmentItems[] = [
                                'order_item' => $orderItem,
                                'quantity' => $values['row_'.$orderItem->getId()]['ship_'.$orderItem->getId().'_qty'],
                            ];
                        }
                    }
                }

                $order->ship($values['shipping_method'], $values['shipment_code'], $shipmentItems);

//                $this->addInfoFlashMessage($this->getUtils()->translate("Shipment created."));

                return new JsonResponse(['success' => true, 'js' => '$(\'#admin\').appAdmin(\'closeSidePanel\')']);

                break;
            case 'cancel':
                $order->setOrderStatus(OrderStatus::getCollection()->where(['status' => OrderStatus::CANCELED])->getFirst())->persist();

                $this->setAdminActionLogData('Cancelled order ' . $order->getId());

                $this->addInfoFlashMessage($this->getUtils()->translate("Order Cancelled."));

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
            'Website' => ['order' => 'website_id', 'foreign' => 'website_id', 'table' => $this->getModelTableName(), 'view' => 'site_name'],
            'Order Number' => ['order' => 'order_number', 'search' => 'order_number'],
            'Order Status' => ['order' => 'order_status_id', 'foreign' => 'order_status_id', 'table' => $this->getModelTableName(), 'view' => 'status'],
            'Customer' => ['order' => 'user_id', 'foreign' => 'user_id', 'table' => $this->getModelTableName(), 'view' => 'email'],
            'Total' => ['order' => 'total_incl_tax', 'search' => 'total_incl_tax'],
            'Admin Total' => ['order' => 'admin_total_incl_tax', 'search' => 'admin_total_incl_tax'],
            'Created At' => ['order' => 'created_at', 'search' => 'created_at'],
            'Updated At' => ['order' => 'updated_at', 'search' => 'updated_at'],
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
     * @throws Exception
     */
    protected function getTableElements(array $data, array $options = []): array
    {
        return array_map(
            function (OrderModel $order) {
                return [
                    'ID' => $order->id,
                    'Website' => $order->getWebsiteId() == null ? 'All websites' : $order->getWebsite()->domain,
                    'Order Number' => $order->getOrderNumber(),
                    'Order Status' => $order->getOrderStatus()->getStatus(),
                    'Customer' => $order->getOwner()->getEmail(),
                    'Total' => $this->getUtils()->formatPrice($order->getTotalInclTax(), $order->getCurrencyCode()),
                    'Admin Total' => $this->getUtils()->formatPrice($order->getAdminTotalInclTax(), $order->getAdminCurrencyCode()),
                    'Created At' => $order->getCreatedAt(),
                    'Updated At' => $order->getUpdatedAt(),
                    'actions' => [
                        static::VIEW_BTN => $this->getActionButton('view', $order->id, 'info', 'search', 'View'),
                        static::EDIT_BTN => $this->getEditButton($order->id),
                        'cancel-btn' => $this->getActionButton('cancel', $order->id, 'danger', 'x-circle', 'Cancel'),
                    ],
                ];
            },
            $data
        );
    }

    protected function beforeRender(): BasePage|Response 
    {
        if (App::getInstance()->getEnvironment()->getVariable('ENABLE_COMMERCE', false) == false) {
            $this->addWarningFlashMessage($this->getUtils()->translate("Commerce functionallity is currently disabled"), true);
        }
        return parent::beforeRender();
    }
}
