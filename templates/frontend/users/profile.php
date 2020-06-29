<?php $this->layout('frontend::layout', ['title' => 'Profile'] + get_defined_vars()) ?>


<div class="container-fluid">
    <div class="row">
        <nav id="sidebar" class="col-md-2 bg-light sidebar<?= ($controller->getRequest()->cookies->get('sidebar_size') == 'minimized') ? ' collapsed' : ''; ?>">
            <div class="sidebar-sticky">
                <a href="#" class="closebtn d-sm-block d-md-none">&times;</a>
                <?php $this->insert('frontend::users/partials/sidemenu', ['controller' => $controller]); ?>
            </div>
        </nav>
        <main role="main" class="col-md-10 ml-sm-auto col-lg-10 pt-3 px-4">
            <?php if ($controller->getRequest()->get('action') == 'edit' || $controller->getRequest()->get('action') == 'change_pass') :?>
                <?= $form;?>
            <?php else :?>
                <div class="card" style="width: 400px;max-width: 100%;">
                  <?php echo $this->sitebase()->getGravatar($current_user->email, 400, 'mp', 'g', 'card-img-top');?>
                  <div class="card-body">
                    <h5 class="card-title"><?= $current_user->getNickname();?> (<?= $current_user->getUsername();?>)</h5>
                    <p class="card-text">
                        <?= $this->sitebase()->translate("Email");?>: <?= $current_user->getEmail(); ?><br />
                        <?= $this->sitebase()->translate("Role");?>: <?= $current_user->getRole()->getName(); ?><br />
                        <?= $this->sitebase()->translate("Locale");?>: <?= $current_user->getLocale(); ?><br />
                        <?= $this->sitebase()->translate("Registered since");?>: <?= $current_user->getRegisteredSince(); ?>
                    </p>
                    <a class="btn btn-primary" href="<?=$controller->getControllerUrl();?>?action=edit"><?= $this->sitebase()->translate("Edit");?></a>
                    <a class="btn btn-primary" href="<?=$controller->getControllerUrl();?>?action=change_pass"><?= $this->sitebase()->translate("Change password");?></a>

                  </div>
                </div>
            <?php endif;?>
        </main>
    </div>
</div>

