<?php
/**
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 * @var $downloads \App\Base\Models\DownloadableProduct[]
 * @var $form \Degami\PHPFormsApi\Form
 * @var $controller \App\Base\Controllers\Frontend\Users\Addresses
 */
$this->layout('frontend::layout', ['title' => 'Downloads'] + get_defined_vars()) ?>


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
                <?php if (empty($downloads)) :?>
                    <h3><?= $this->sitebase()->translate("No elements found");?></h3>
                <?php else:?>
                    <table class="table table-striped">
                        <thead>
                            <th><?= $this->sitebase()->translate('File name') ?></th>
                            <th><?= $this->sitebase()->translate('File size') ?></th>
                            <th>&nbsp;</th>
                        </thead>
                        <tbody>
                    <?php foreach($downloads as $download) :?>
                        <tr>
                            <td class="file-name"><?= /** @var \App\Base\Models\DownloadableProduct $download */ $download->getFilename() ;?></td>
                            <td class="file-size"><?= /** @var \App\Base\Models\DownloadableProduct $download */ $this->sitebase()->formatBytes($download->getFilesize()) ;?></td>
                            <td class="actions d-md-flex justify-content-md-end">
                                <a href="?action=download&id=<?= $download->getId(); ?>" class="btn btn-primary mr-1"><?php $this->sitebase()->drawIcon('download');?></a>
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

