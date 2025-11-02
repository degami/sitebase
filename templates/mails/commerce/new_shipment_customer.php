<?php
/**
 * @var string $subject
 * @var \App\Base\Models\Order $order
 * @var \App\Base\Models\OrderShipment $shipment
 * @var \App\Base\Models\Website $website
 */

$orderNumber = $order['order_number'];
$shippingMethod = $shipment->getShippingMethod();
$shipmentCode = $shipment->getShipmentCode();
$shipmentItems = $shipment->getItems();

$this->layout('mails::layout', get_defined_vars()) ?>

<p><?= $this->sitebase()->translate('Hello'); ?> <?= $order->getBillingAddress()->getFullName(); ?></p>

<p><?= $this->sitebase()->translate('A new shipment has been prepared for your order %s', [$orderNumber]); ?></p>

<p><?= $this->sitebase()->translate('Your shipment will be handled by <strong>%s</strong> - shipment code is <strong>%s</strong>', [$shippingMethod, $shipmentCode]) ;?></p>

<p>
    <?= $this->sitebase()->translate('Items included in this shipment'); ?><br />
    <ul>
        <?php foreach ($shipmentItems as $item) : /** @var \App\Base\Models\OrderShipmentItem $item */ ?>
            <li><strong><?= $item->getQuantity();?>x</strong> <?= $item->getOrderItem()->getProduct()->getName(); ?> (<?= $item->getOrderItem()->getProduct()->getSku(); ?>)</li>
        <?php endforeach;?>
    </ul>
</p>

<?= $this->sitebase()->translate('Best regards, the %s team', [$website->getSiteName()]); ?>