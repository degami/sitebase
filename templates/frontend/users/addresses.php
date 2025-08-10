<?php
/**
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 * @var $addresses \App\Base\Models\Address[]
 * @var $form \Degami\PHPFormsApi\Form
 * @var $controller \App\Base\Controllers\Frontend\Users\Addresses
 */
$this->layout('frontend::layout', ['title' => 'Addresses'] + get_defined_vars()) ?>


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
            <?php if ($controller->getRequest()->get('action') == 'edit' || $controller->getRequest()->get('action') == 'add' || $controller->getRequest()->get('action') == 'delete') :?>
                    <?= $form;?>
            <?php else: ?>
                <?php if (empty($addresses)) :?>
                    <h3><?= $this->sitebase()->translate("No elements found");?></h3>
                <?php else:?>
                    <?php foreach($addresses as $address) :?>
                        <div class="col-sm-4 pb-3">
                            <div class="card">
                                <h5 class="card-header"><?= /** @var \App\Base\Models\Address $address */ $address->getFullName() ;?></h5>
                                <div class="card-body">
                                    <div class="full-address"><?= /** @var \App\Base\Models\Address $address */ $address->getFullAddress() ;?></div>
                                    <div class="full-contact"><?= /** @var \App\Base\Models\Address $address */ $address->getFullContact() ;?></div>

                                    <div class="d-grid mt-3 gap-2 d-md-flex justify-content-md-end">
                                        <a href="?action=edit&id=<?= $address->getId(); ?>" class="btn btn-primary mr-1"><?php $this->sitebase()->drawIcon('edit');?></a>
                                        <a href="?action=delete&id=<?= $address->getId(); ?>" class="btn btn-danger"><?php $this->sitebase()->drawIcon('trash');?></a>
                                    </div>

                                </div>
                            </div>
                        </div>
                    <?php endforeach;?>
                <?php endif;?>
                <div class="col-sm-12">
                    <a class="btn btn-primary" href="<?=$controller->getControllerUrl();?>?action=add"><?php $this->sitebase()->drawIcon('plus');?><?= $this->sitebase()->translate("Add");?></a>
                </div>
            <?php endif; ?>
            </div>
        </main>
    </div>
</div>

