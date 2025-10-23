<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $request \Symfony\Component\HttpFoundation\Request
 * @var $logHtml string
 * @var $listing string
 * @var $before_listing string
 * @var $paginator string
 */
$icon_style = 'vertical-align: text-top; width: 24px; height: 24px;';

$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()); ?>
<?php if ($action == 'logs' && is_numeric($request->query->get('id'))) : ?>
    <?= $logHtml; ?>
<?php elseif ($action == 'logs') : ?>
   <?= $before_listing ?? ''; ?>
    <div class="table-responsive">
        <?= $listing; ?>
    </div>
    <?= $paginator ?? ''; ?>
<?php else : ?>
    <div class="row m-4 pt-4">
        <div class="col-2">&nbsp;</div>
        <div class="col-4">
            <a class="btn btn-lg btn-block btn-outline-info nowrap p-4" role="button" href="<?= $controller->getControllerUrl();?>?logtype=request">
                <?php $this->sitebase()->drawIcon('globe', ['style' => $icon_style]); ?> 
                <?= $this->sitebase()->translate('Requests');?>
            </a>
        </div>
        <div class="col-4">
            <a class="btn btn-lg btn-block btn-outline-info nowrap p-4" role="button" href="<?= $controller->getControllerUrl();?>?logtype=mail">
                <?php $this->sitebase()->drawIcon('mail', ['style' => $icon_style]); ?> 
                <?= $this->sitebase()->translate('Mails');?>
            </a>
        </div>
        <div class="col-2">&nbsp;</div>
    </div>
    <div class="row m-4">
        <div class="col-2">&nbsp;</div>
        <div class="col-4">
            <a class="btn btn-lg btn-block btn-outline-info nowrap p-4" role="button" href="<?= $controller->getControllerUrl();?>?logtype=cron">
                <?php $this->sitebase()->drawIcon('watch', ['style' => $icon_style]); ?> 
                <?= $this->sitebase()->translate('Cron');?>
            </a>
        </div>
        <div class="col-4">
            <a class="btn btn-lg btn-block btn-outline-info nowrap p-4" role="button" href="<?= $controller->getControllerUrl();?>?logtype=adminactions">
                <?php $this->sitebase()->drawIcon('star', ['style' => $icon_style]); ?> 
                <?= $this->sitebase()->translate('Admin Actions');?></a>
        </div>
        <div class="col-2">&nbsp;</div>
    </div>
    <div class="row m-4">
        <div class="col-2">&nbsp;</div>
        <div class="col-4">
            <a class="btn btn-lg btn-block btn-outline-info nowrap p-4" role="button" href="<?= $controller->getControllerUrl();?>?logtype=progress_manager">
                <?php $this->sitebase()->drawIcon('coffee', ['style' => $icon_style]); ?> 
                <?= $this->sitebase()->translate('Progress Manager Processes');?>
            </a>
        </div>
        <div class="col-4">
            <!-- next log button here -->
        </div>
        <div class="col-2">&nbsp;</div>
    </div>
<?php endif; ?>
