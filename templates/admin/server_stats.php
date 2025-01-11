<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $last_beat string
 * @var $table string
 * @var $paginator string
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<div class="container">
    <div class="jumbotron">

        <div class="row align-items-center m-1">
            <div class="col-3 text-center">
                <?= $this->sitebase()->drawIcon('grid', ['style' => 'width: 30px; height: 30px']); ?><br />
                <?= $this->sitebase()->translate('Memory Status'); ?>
            </div>    
            <ul class="col-9">
                <li>
                <?= $this->sitebase()->translate('Memory Total'); ?>
                <?= $memoryTotal; ?>
                </li>
                <li>
                <?= $this->sitebase()->translate('Memory Used'); ?>
                <?= $memoryUsed; ?>
                </li>
                <li>
                <?= $this->sitebase()->translate('Memory Free'); ?>
                <?= $memoryFree; ?>
                </li>
            </ul>
        </div>

        <hr />

        <div class="row m-1">
            <div class="col-3 text-center">
                <?= $this->sitebase()->drawIcon('cpu', ['style' => 'width: 30px; height: 30px']); ?><br />
                <?= $this->sitebase()->translate('CPU Load'); ?>
            </div>
            <span  class="col-9"><?= $cpuLoad; ?></span>
        </div>

        <hr />

        <div class="row m-1">
            <div class="col-3 text-center">
            <?= $this->sitebase()->drawIcon('hard-drive', ['style' => 'width: 30px; height: 30px']); ?><br />    
            <?= $this->sitebase()->translate('Disk Status'); ?>
            </div>
            <ul class="col-9">
                <li>
                <?= $this->sitebase()->translate('Disk Total'); ?>
                <?= $diskTotal; ?>
                </li>
                <li>
                <?= $this->sitebase()->translate('Disk Used'); ?>
                <?= $diskUsed; ?>
                </li>
                <li>
                <?= $this->sitebase()->translate('Disk Free'); ?>
                <?= $diskFree; ?>
                </li>
            </ul>
        </div>

    </div>
</div>
