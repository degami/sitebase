<?php
/**
 * @var string $subject
 * @var \App\Base\Models\Order $order
 * @var \App\Base\Models\OrderPayment $payment
 * @var \App\Base\Models\Website $website
 */

$orderItems = $order->getItems();
$orderNumber = $order->getOrderNumber();

$this->layout('mails::layout', get_defined_vars()) ?>

<p><?= $this->sitebase()->translate('Hello'); ?> <?= $order->getBillingAddress()->getFullName(); ?></p>

<p><?= $this->sitebase()->translate('Thanks for your purchase on %s', [$website->getSiteName()]);?></p>

<p><?= $this->sitebase()->translate('These are your order %s details', [$orderNumber]); ?></p>

<table id="order_items" class="table table-striped" width="100%" style="width: 100%; margin-bottom:20px">
    <thead class="thead">
        <tr>
            <th class="th" style="text-align: left;"><?= $this->sitebase()->translate('Product'); ?></th>
            <th class="th" style="text-align: right;"><?= $this->sitebase()->translate('Quantity'); ?></th>
            <th class="th" style="text-align: right;"><?= $this->sitebase()->translate('Unit Price'); ?></th>
            <th class="th" style="text-align: right;"><?= $this->sitebase()->translate('Subtotal'); ?></th>
            <th class="th" style="text-align: right;"><?= $this->sitebase()->translate('Tax'); ?></th>
            <th class="th" style="text-align: right;"><?= $this->sitebase()->translate('Total'); ?></th>
        </tr>
    </thead>
    <tbody class="tbody">
        <?php $trIndex = 0; foreach ($orderItems as $item) :?>
        <tr class="tr <?= $trIndex++ % 2 == 0 ? 'even' : 'odd'?>">
            <td style="text-align: left;"><?= $item->getProduct()->getName(); ?></td>
            <td style="text-align: right;"><?= $item->getQuantity(); ?></td>
            <td style="text-align: right;"><?= $this->sitebase()->formatPrice($item->getUnitPrice(), $item->getCurrencyCode()); ?></td>
            <td style="text-align: right;"><?= $this->sitebase()->formatPrice($item->getSubTotal(), $item->getCurrencyCode()); ?></td>
            <td style="text-align: right;"><?= $this->sitebase()->formatPrice($item->getTaxAmount(), $item->getCurrencyCode()); ?></td>
            <td style="text-align: right;"><?= $this->sitebase()->formatPrice($item->getTotalInclTax(), $item->getCurrencyCode()); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>


<div>
    <?= $this->sitebase()->translate('Subtotal') ;?>: 
    <?= $this->sitebase()->formatPrice($order->getSubTotal(), $order->getCurrencyCode()) ;?>
</div>

<div>
    <?= $this->sitebase()->translate('Discounts') ;?>: 
    <?= $this->sitebase()->formatPrice($order->getDiscountAmount(), $order->getCurrencyCode()) ;?>
</div>

<div>
    <?= $this->sitebase()->translate('Tax') ;?>: 
    <?= $this->sitebase()->formatPrice($order->getTaxAmount(), $order->getCurrencyCode()) ;?>
</div>

<?php if ($order->getShippingAddress()) : ?>
<div>
    <?= $this->sitebase()->translate('Shipping') ;?>: 
    <?= $this->sitebase()->formatPrice($order->getShippingAmount(), $order->getCurrencyCode()) ;?>
</div>
<?php endif; ?>

<div>
    <strong><?= $this->sitebase()->translate('Order Total') ;?>: </strong>
    <?= $this->sitebase()->formatPrice($order->getTotalInclTax(), $order->getCurrencyCode()) ;?>
</div>


<p><?= $this->sitebase()->translate('These are your payment details'); ?></p>

<ul>
    <li><?= $this->sitebase()->translate('Payment Method'); ?>: <?= $payment->getPaymentMethod(); ?></li>
    <li><?= $this->sitebase()->translate('Transaction Id'); ?>: <?= $payment->getTransactionId(); ?></li>
</ul>

<p><?= $this->sitebase()->translate('As soon as the order will be shipped you will receive detailed email informations.')?></p>

<p><?= $this->sitebase()->translate('Best regards, the %s team', [$website->getSiteName()]); ?></p>