<?php
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<nav>
  <div class="nav nav-tabs" id="nav-tab" role="tablist">
    <a class="nav-item nav-link active" id="nav-visitors-tab" data-toggle="tab" href="#nav-visitors" role="tab" aria-controls="nav-visitors" aria-selected="true">Top Visitors</a>
    <a class="nav-item nav-link" id="nav-viewed-tab" data-toggle="tab" href="#nav-viewed" role="tab" aria-controls="nav-viewed" aria-selected="false">Most viewed</a>
    <a class="nav-item nav-link" id="nav-errors-tab" data-toggle="tab" href="#nav-errors" role="tab" aria-controls="nav-errors" aria-selected="false">Top Errors</a>
    <a class="nav-item nav-link" id="nav-scanners-tab" data-toggle="tab" href="#nav-scanners" role="tab" aria-controls="nav-scanners" aria-selected="false">Top Scanners</a>
  </div>
</nav>
<div class="tab-content" id="nav-tabContent">
  <div class="tab-pane fade show active" id="nav-visitors" role="tabpanel" aria-labelledby="nav-visitors-tab">
    <br />
    <h3>Top Visitors</h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th>Ip Address</th>
        <th>Visits</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($top_visitors as $row) : ?>
        <tr>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['ip_address' => $row['ip_address']]]) ?>"><?= $row['ip_address'];?></a></td>
            <td><?= $row['cnt'];?></td>
        </tr>
    <?php endforeach;?>
    </tbody>
    </table>
  </div>
  <div class="tab-pane fade show" id="nav-viewed" role="tabpanel" aria-labelledby="nav-viewed-tab">
    <br />
    <h3>Most viewed</h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th>Url</th>
        <th>Visits</th>
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
    <h3>Top Errors</h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th>Url</th>
        <th>Ip Address</th>
        <th>Response code</th>
        <th>Visits</th>
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
            <td><a class="btn btn-sm btn-danger" href="<?= $this->sitebase()->getUrl('admin.banip').'?'.http_build_query(['ip' => $row['ip_address']]);?>"><?php $this->sitebase()->drawIcon('slash');?></a></td>
        </tr>
    <?php endforeach;?>
    </tbody>
    </table>
  </div>
  <div class="tab-pane fade" id="nav-scanners" role="tabpanel" aria-labelledby="nav-scanners-tab">
    <br />
    <h3>Top Scanners</h3>
    <table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
    <thead>
        <tr>
        <th>Ip Address</th>
        <th>Response codes</th>
        <th>Visits</th>
        <th></th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($top_scanners as $row) : ?>
        <tr>
            <td><a href="<?= $this->sitebase()->getUrl('admin.logs').'?'.http_build_query(['logtype' => 'request', 'search' => ['ip_address' => $row['ip_address']]]) ?>"><?= $row['ip_address'];?></a></td>
            <td><?= $row['codes'];?></td>
            <td><?= $row['cnt'];?></td>
            <td><a class="btn btn-sm btn-danger" href="<?= $this->sitebase()->getUrl('admin.banip').'?'.http_build_query(['ip' => $row['ip_address']]);?>"><?php $this->sitebase()->drawIcon('slash');?></a></td>
        </tr>
    <?php endforeach;?>
    </tbody>
    </table>
  </div>
</div>
