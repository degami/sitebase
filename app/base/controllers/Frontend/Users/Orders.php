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

namespace App\Base\Controllers\Frontend\Users;

use App\App;
use App\Base\Abstracts\Controllers\LoggedUserFormPage;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Models\Order;
use App\Base\Models\OrderStatus;
use Degami\PHPFormsApi as FAPI;
use App\Base\Models\OrderComment;

class Orders extends LoggedUserFormPage
{
    const ITEMS_PER_PAGE = 20;
    /**
     * @inheritDoc
     */
    public static function isEnabled(): bool
    {
        return boolval(App::getInstance()->getEnv('ENABLE_COMMERCE', false)) && boolval(App::getInstance()->getEnv('ENABLE_LOGGEDPAGES', false));
    }

    /**
     * @inheritDoc
     */
    public function getTemplateName(): string
    {
        return 'users/orders';
    }

    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'orders';
    }

    /**
     * @inheritdoc
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'view_logged_site';
    }

    /**
     * @inheritDoc
     */
    public function getTemplateData(): array
    {
        $page = $this->getRequest()->get('page') ?? 0;
        $total = $this->getOrders()->count();

        $orders = $this->getOrders()->limit(self::ITEMS_PER_PAGE, $page * self::ITEMS_PER_PAGE)->getItems();

        $this->template_data += [
            'current_user' => $this->getCurrentUser(),
            'orders' => $orders,
            'paginator' => $this->getHtmlRenderer()->renderPaginator($page, $total, $this, self::ITEMS_PER_PAGE, 5),
        ];
        return $this->template_data;
    }

    protected function getOrders() : BaseCollection
    {
        return Order::getCollection()->where(['user_id' => $this->getCurrentUser()->getId()]);
    }

    /**
     * gets form definition object
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $order = $this->getOrder();

        $action = $this->getRequest()->get('action');
        switch ($action) {
            case 'view':
                $form->addCss('.form-container, form#orders {width: 100%;}');
                $form->addMarkup('<h2>'.$order->getOrderNumber() . ' - ' . $order->getOrderStatus()->getStatus().'</h2>');

                $form->addMarkup(
                    $this->getCardHtml(
                        $this->getUtils()->translate('Billing:'),
                        $order->getBillingAddress()->getFullContact() . '<br />' .
                        $order->getBillingAddress()->getFullAddress()
                    )
                );

                if ($order->getShippingAddress()) {
                    $form->addMarkup(
                        $this->getCardHtml(
                            $this->getUtils()->translate('Shipping:'),
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
                    'prefix' => '<div class="card mt-3"><div class="card-header"><h6 class="cart-title">'.$this->getUtils()->translate('Order Items:').'</h6></div><div class="card-body">',
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
                        ->addField('product_'.$table->numRows(), ['type' => 'markup', 'value' => $item->getProduct()->getTitle()])
                        ->addField('quantity_'.$table->numRows(), ['type' => 'markup', 'value' => $item->getQuantity()])
                        ->addField('unit_price_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getUnitPrice(), $item->getCurrencyCode())])
                        ->addField('subtotal_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getSubTotal(), $item->getCurrencyCode())])
                        ->addField('tax_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getTaxAmount(), $item->getCurrencyCode())])
                        ->addField('total_'.$table->numRows(), ['type' => 'markup', 'value' => $this->getUtils()->formatPrice($item->getTotalInclTax(), $order->getCurrencyCode())]);
                }

                $form->addMarkup(
                    '<h5 class="mt-3">' .
                        $this->getUtils()->translate('Order Total:') . ' ' .
                        $this->getUtils()->formatPrice($order->getTotalInclTax(), $order->getCurrencyCode()) . 
                    '</h5>' 
                );
                $form->addMarkup(
                    '<h6>' .
                        $this->getUtils()->translate('Subtotal:') . ' ' .
                        $this->getUtils()->formatPrice($order->getSubTotal(), $order->getCurrencyCode()) . 
                    '</h6>' 
                );
                $form->addMarkup(
                    '<h6>' .
                        $this->getUtils()->translate('Discount:') . ' ' .
                        $this->getUtils()->formatPrice($order->getDiscountAmount(), $order->getCurrencyCode()) . 
                    '</h6>' 
                );
                $form->addMarkup(
                    '<h6>' .
                        $this->getUtils()->translate('Tax:') . ' ' .
                        $this->getUtils()->formatPrice($order->getTaxAmount(), $order->getCurrencyCode()) . 
                    '</h6>'
                );
                if ($order->getShippingAddress()) {
                    $form->addMarkup(
                        '<h6>' .
                            $this->getUtils()->translate('Shipping:') . ' ' .
                            $this->getUtils()->formatPrice($order->getShippingAmount(), $order->getCurrencyCode()) . 
                        '</h6>'
                    );
                }

               if (!empty($order->getComments()->getItems())) {
                    $form->addMarkup('<hr /><h5>' . $this->getUtils()->translate('Comments:') . '</h5>');
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

                $form->addMarkup('<hr />');

                $form->addField('comment', [
                    'type' => 'textarea',
                    'title' => 'Add a comment',
                    'rows' => 5,
                ]);

                $this->addSubmitButton($form);


                break;
            case 'cancel':
                $this->fillConfirmationForm("Do you really want to cancel this order?", $form);
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
     * validates form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, array &$form_state): bool|string
    {
        return true;
    }

    /**
     * handles form submission
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     */
    public function formSubmitted(FAPI\Form $form, array &$form_state): mixed
    {
        $order = $this->getOrder();
        $values = $form->values();

        $action = $this->getRequest()->get('action');

        switch ($action) {
            case 'view':

                if (!empty($values->comment)) {
                    /** @var OrderComment $orderComment */
                    $orderComment = $this->containerMake(OrderComment::class);
                    $orderComment
                        ->setOrderId($order->getId())
                        ->setWebsiteId($order->getWebsiteId())
                        ->setUserId($this->getCurrentUser()->getId())
                        ->setComment($values->comment)
                        ->persist();


                    $this->addInfoFlashMessage($this->getUtils()->translate("Comment submitted"));
                }

                break;
            case 'cancel':
                $order->setOrderStatus(OrderStatus::getByStatus(OrderStatus::CANCELED))->persist();

                $this->addInfoFlashMessage($this->getUtils()->translate("Order cancelled"));
                break;
        }

        return $this->doRedirect($this->getUrl('frontend.users.orders'));
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getRouteName(): string
    {
        return $this->getUtils()->translate('My Orders', locale: $this->getCurrentLocale());
    }

    protected function getOrder() : ?Order
    {
        /** @var Order $order */
        $id = $this->getRequest()->get('id');
        if (!is_numeric($id)) {
            return null;
        }

        $order = Order::load($id);

        return $order;
    }
}