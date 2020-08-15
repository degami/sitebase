<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $request \Symfony\Component\HttpFoundation\Request
 * @var $logHtml string
 * @var $table string
 * @var $paginator string
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()); ?>
<?php if ($action == 'logs' && is_numeric($request->query->get('id'))) : ?>
    <?= $logHtml; ?>
<?php elseif ($action == 'logs') : ?>
    <div class="table-responsive">
        <?= $table; ?>
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
        <div class="col-3">
            <a class="btn btn-lg btn-block btn-outline-info nowrap" role="button" href="<?= $controller->getControllerUrl();?>?logtype=adminactions"><?php $this->sitebase()->drawIcon('star', ['style' => 'vertical-align: middle']); ?> <?= $this->sitebase()->translate('Admin Actions');?></a>
        </div>
    </div>
<?php endif; ?>
