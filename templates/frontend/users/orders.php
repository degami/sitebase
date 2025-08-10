<?php
/**
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 * @var $orders \App\Base\Models\Order[]
 * @var $form \Degami\PHPFormsApi\Form
 * @var $controller \App\Base\Controllers\Frontend\Users\Addresses
 */
$this->layout('frontend::layout', ['title' => 'Orders'] + get_defined_vars()) ?>


<div class="container-fluid">
    <div class="row">
        <nav id="sidebar" class="col-md-2 bg-light sidebar<?= ($controller->getRequest()->cookies->get('sidebar_size') == 'minimized') ? ' collapsed' : ''; ?>">
            <div class="sidebar-sticky">
                <a href="#" class="closebtn d-sm-block d-md-none">&times;</a>
                <?php $this->insert('frontend::users/partials/sidemenu', ['controller' => $controller]); ?>
            </div>
        </nav>
        <main role="main" class="col-md-10 ml-sm-auto col-lg-10 pt-3 px-4">
            <div class="row">
            <?php if ($controller->getRequest()->get('action') == 'cancel' || $controller->getRequest()->get('action') == 'view') :?>
                    <?= $form;?>
            <?php else: ?>
                <?php if (empty($orders)) :?>
                    <h3><?= $this->sitebase()->translate("No elements found");?></h3>
                <?php else:?>
                    <table class="table table-striped">
                        <thead>
                            <th><?= $this->sitebase()->translate('Order Number') ?></th>
                            <th><?= $this->sitebase()->translate('Address') ?></th>
                            <th><?= $this->sitebase()->translate('Contact') ?></th>
                            <th><?= $this->sitebase()->translate('Order Total') ?></th>
                            <th>&nbsp;</th>
                        </thead>
                        <tbody>
                    <?php foreach($orders as $order) :?>
                        <tr>
                            <td class="order-number"><?= /** @var \App\Base\Models\Order $address */ $order->getOrderNumber() ;?></td>
                            <td class="full-address"><?= /** @var \App\Base\Models\Order $address */ $order->getBillingAddress()->getFullAddress() ;?></td>
                            <td class="full-contact"><?= /** @var \App\Base\Models\Order $address */ $order->getBillingAddress()->getFullContact() ;?></td>
                            <td class="order-total"><?= /** @var \App\Base\Models\Order $address */  $this->sitebase()->formatPrice($order->getTotalInclTax(), $order->getCurrencyCode());?></td>
                            <td class="actions d-md-flex justify-content-md-end">
                                <a href="?action=view&id=<?= $order->getId(); ?>" class="btn btn-primary mr-1"><?php $this->sitebase()->drawIcon('zoom-in');?></a>
                                <a href="?action=cancel&id=<?= $order->getId(); ?>" class="btn btn-danger mr-1"><?php $this->sitebase()->drawIcon('x-circle');?></a>
                            </td>
                        </tr>
                    <?php endforeach;?>
                        </tbody>
                    </table>
                    <?= $paginator ?? ''; ?>
                <?php endif;?>
            <?php endif; ?>
            </div>
        </main>
    </div>
</div>

