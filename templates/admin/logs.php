<?php
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()); ?>

<?php if ($action == 'logs') : ?>
    <div class="table-responsive">
    <table width="100%" style="width: 100%;" class="table table-striped">
        <thead class="thead-dark">
            <tr>
                <?php foreach ($header as $key) : ?>
                    <th scope="col"><?= ucwords(str_replace("_", " ", $this->sitebase()->translate($key))); ?></th>
                <?php endforeach;?>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($logs as $key => $log) :?>
            <tr class="<?= $key % 2 == 0 ? 'odd' : 'even';?>">
                <?php foreach ($header as $key) : ?>
                    <td scope="row"><?= $log->{$key}; ?></td>
                <?php endforeach;?>
            </tr>
        <?php endforeach;?>            
        </tbody>
    </table>
    </div>
    <?= $paginator; ?>
<?php else : ?>
    <div class="row">
        <div class="col-3">
            <a class="btn btn-lg btn-block btn-outline-info nowrap" role="button" href="<?= $controller->getControllerUrl();?>?logtype=request"><?php $this->sitebase()->drawIcon('globe', ['style' => 'vertical-align: middle']); ?> <?= $this->sitebase()->translate('Requests');?></a>
        </div>
        <div class="col-3">
            <a class="btn btn-lg btn-block btn-outline-info nowrap" role="button" href="<?= $controller->getControllerUrl();?>?logtype=mail"><?php $this->sitebase()->drawIcon('mail', ['style' => 'vertical-align: middle']); ?> <?= $this->sitebase()->translate('Mails');?></a>
        </div>
        <div class="col-3">
            <a class="btn btn-lg btn-block btn-outline-info nowrap" role="button" href="<?= $controller->getControllerUrl();?>?logtype=cron"><?php $this->sitebase()->drawIcon('watch', ['style' => 'vertical-align: middle']); ?> <?= $this->sitebase()->translate('Cron');?></a>
        </div>
    </div>
<?php endif; ?>
