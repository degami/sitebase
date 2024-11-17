<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $top_visitors array
 * @var $most_viewed array
 * @var $top_errors array
 * @var $top_scanners array
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
    <a class="nav-item nav-link active" id="nav-visitors-tab" data-toggle="tab" href="#nav-visitors" role="tab" aria-controls="nav-visitors" aria-selected="true"><?= $this->sitebase()->translate('Top Visitors') ?></a>
    <a class="nav-item nav-link" id="nav-viewed-tab" data-toggle="tab" href="#nav-viewed" role="tab" aria-controls="nav-viewed" aria-selected="false"><?= $this->sitebase()->translate('Most viewed') ?></a>
    <a class="nav-item nav-link" id="nav-errors-tab" data-toggle="tab" href="#nav-errors" role="tab" aria-controls="nav-errors" aria-selected="false"><?= $this->sitebase()->translate('Top Errors') ?></a>
    <a class="nav-item nav-link" id="nav-scanners-tab" data-toggle="tab" href="#nav-scanners" role="tab" aria-controls="nav-scanners" aria-selected="false"><?= $this->sitebase()->translate('Top Scanners') ?></a>
  </div>
</nav>
<div class="tab-content" id="nav-tabContent">
  <div class="tab-pane fade show active" id="nav-visitors" role="tabpanel" aria-labelledby="nav-visitors-tab">
    <br />
    <h3><?= $this->sitebase()->translate('Top Visitors') ?></h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th><?= $this->sitebase()->translate('Ip Address') ?></th>
        <th><?= $this->sitebase()->translate('Visits') ?></th>
        <th><?= $this->sitebase()->translate('Whois') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($top_visitors as $row) : ?>
        <tr>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['ip_address' => $row['ip_address']]]) ?>"><?= $row['ip_address'];?></a></td>
            <td><?= $row['cnt'];?></td>
            <td><a class="btn btn-info btn-sm" title="<?= $this->sitebase()->translate('Whois') ?>" href="https://www.whois.com/whois/<?= $row['ip_address'];?>" target="_blank"><?php $this->sitebase()->drawIcon('help-circle');?></a></td>
        </tr>
    <?php endforeach;?>
    </tbody>
    </table>
  </div>
  <div class="tab-pane fade show" id="nav-viewed" role="tabpanel" aria-labelledby="nav-viewed-tab">
    <br />
    <h3><?= $this->sitebase()->translate('Most viewed') ?></h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th><?= $this->sitebase()->translate('Url') ?></th>
        <th><?= $this->sitebase()->translate('Visits') ?></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($most_viewed as $row) : ?>
        <tr>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['url' => $row['url']]]) ?>"><?= $row['url'];?></a></td>
            <td><?= $row['cnt'];?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
    </table>
  </div>
  <div class="tab-pane fade" id="nav-errors" role="tabpanel" aria-labelledby="nav-errors-tab">
    <br />
    <h3><?= $this->sitebase()->translate('Top Errors') ?></h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th><?= $this->sitebase()->translate('Url') ?></th>
        <th><?= $this->sitebase()->translate('Ip Address') ?></th>
        <th><?= $this->sitebase()->translate('Response code') ?></th>
        <th><?= $this->sitebase()->translate('Visits') ?></th>
        <th><?= $this->sitebase()->translate('Whois') ?></th>
        <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($top_errors as $row) : ?>
        <tr>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['url' => $row['url']]]) ?>"><?= $row['url'];?></a></td>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['ip_address' => $row['ip_address']]]) ?>"><?= $row['ip_address'];?></a></td>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['response_code' => $row['response_code']]]) ?>"><?= $row['response_code'];?></a></td>
            <td>
                <a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['url' => $row['url'], 'ip_address' => $row['ip_address'], 'response_code' => $row['response_code']]]) ?>">
                    <?= $row['cnt'];?>
                </a>
            </td>
            <td><a class="btn btn-info btn-sm" title="<?= $this->sitebase()->translate('Whois') ?>" href="https://www.whois.com/whois/<?= $row['ip_address'];?>" target="_blank"><?php $this->sitebase()->drawIcon('help-circle');?></a></td>
            <td><a class="btn btn-sm btn-danger" title="<?= $this->sitebase()->translate('Ban') ?>" href="<?= $this->sitebase()->getUrl('admin.banip').'?'.http_build_query(['ip' => $row['ip_address']]);?>"><?php $this->sitebase()->drawIcon('slash');?></a></td>
        </tr>
    <?php endforeach;?>
    </tbody>
    </table>
  </div>
  <div class="tab-pane fade" id="nav-scanners" role="tabpanel" aria-labelledby="nav-scanners-tab">
    <br />
    <h3><?= $this->sitebase()->translate('Top Scanners') ?></h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th><?= $this->sitebase()->translate('Ip Address') ?></th>
        <th><?= $this->sitebase()->translate('Response codes') ?></th>
        <th><?= $this->sitebase()->translate('Visits') ?></th>
        <th><?= $this->sitebase()->translate('Whois') ?></th>
        <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($top_scanners as $row) : ?>
        <tr>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['ip_address' => $row['ip_address']]]) ?>"><?= $row['ip_address'];?></a></td>
            <td><?= $row['codes'];?></td>
            <td><?= $row['cnt'];?></td>
            <td><a class="btn btn-info btn-sm" title="<?= $this->sitebase()->translate('Whois') ?>" href="https://www.whois.com/whois/<?= $row['ip_address'];?>" target="_blank"><?php $this->sitebase()->drawIcon('help-circle');?></a></td>
            <td><a class="btn btn-sm btn-danger" title="<?= $this->sitebase()->translate('Ban') ?>" href="<?= $this->sitebase()->getUrl('admin.banip').'?'.http_build_query(['ip' => $row['ip_address']]);?>"><?php $this->sitebase()->drawIcon('slash');?></a></td>
        </tr>
    <?php endforeach;?>
    </tbody>
    </table>
  </div>
</div>
