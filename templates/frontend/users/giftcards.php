<?php
/**
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 * @var $addresses \App\Base\Models\Address[]
 * @var $form \Degami\PHPFormsApi\Form
 * @var $controller \App\Base\Controllers\Frontend\Users\Addresses
 */
$this->layout('frontend::layout', ['title' => 'My Giftcards'] + get_defined_vars()) ?>


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
                <div class="col-12 pt-3 pb-2 mb-3 border-bottom">
                    <h3><?= $this->sitebase()->translate('My Store Credit');?></h3>
                    <div class="store-credit-summary">
                        <?= $credit_summary; ?>
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-12 pt-3 pb-2 mb-3 border-bottom">
                    <h3><?= $this->sitebase()->translate('My Giftcard Redeem Codes');?></h3>
                </div>
                <div class="col-12 pt-3 pb-2 mb-3 border-bottom">
                <?php if (empty($giftcard_codes)) :?>
                    <h3><?= $this->sitebase()->translate("No elements found");?></h3>
                <?php else:?>
                    <ul class="list-group">
                        <?php foreach($giftcard_codes as $giftcard_code) :?>
                            <li class="list-group-item">
                                <?= /** @var \App\Base\Models\GiftcardRedeemCode $giftcard_code */ $giftcard_code->getCode();?> - 
                                <?= /** @var \App\Base\Models\GiftcardRedeemCode $giftcard_code */ $giftcard_code->getUpdatedAt();?>
                            </li>
                        <?php endforeach;?>
                    </ul>
                <?php endif;?>
                </div>
                <div class="col-sm-12">
                    <?= $form;?>
                </div>
            </div>
        </main>
    </div>
</div>

