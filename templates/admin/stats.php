<?php
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>


<h3>Top Visitors</h3>
<table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
<thead>
    <tr>
    <th>Ip Address</th>
    <th>Visits</th>
    </tr>
</thead>
<tbody>
<?php foreach ($top_visitors as $visitor) : ?>
    <tr>
        <td><?= $visitor['ip_address'];?></td>
        <td><?= $visitor['cnt'];?></td>
    </tr>
<?php endforeach;?>
</tbody>
</table>


<h3>Most viewed</h3>
<table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
<thead>
    <tr>
    <th>Url</th>
    <th>Visits</th>
    </tr>
</thead>
<tbody>
<?php foreach ($most_viewed as $view) : ?>
    <tr>
        <td><?= $view['url'];?></td>
        <td><?= $view['cnt'];?></td>
    </tr>
<?php endforeach;?>
</tbody>
</table>
