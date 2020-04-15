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
<?php foreach ($top_visitors as $row) : ?>
    <tr>
        <td><?= $row['ip_address'];?></td>
        <td><?= $row['cnt'];?></td>
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
<?php foreach ($most_viewed as $row) : ?>
    <tr>
        <td><?= $row['url'];?></td>
        <td><?= $row['cnt'];?></td>
    </tr>
<?php endforeach;?>
</tbody>
</table>


<h3>Top Errors</h3>
<table class="table table-striped" width="100%" cellpadding="0" cellspacing="0">
<thead>
    <tr>
    <th>Url</th>
    <th>Ip Address</th>
    <th>Response code</th>
    <th>Visits</th>
    </tr>
</thead>
<tbody>
<?php foreach ($top_errors as $row) : ?>
    <tr>
        <td><?= $row['url'];?></td>
        <td><?= $row['ip_address'];?></td>
        <td><?= $row['response_code'];?></td>
        <td><?= $row['cnt'];?></td>
    </tr>
<?php endforeach;?>
</tbody>
</table>

