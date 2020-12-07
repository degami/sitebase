<?php
/**
 * @var array $search_result
 * @var string $search_query
 * @var string $paginator
 */
$this->layout('frontend::layout', ['title' => $this->sitebase()->translate('Search')] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="page-title"><?= $this->sitebase()->translate('Search');?></h1>
<?php if($search_query) :?>
<h2><?= sprintf($this->sitebase()->translate("Results for: <em>%s</em>"), $search_query); ?></h2>
<?php endif;?>
<?php if(count($search_result)):?>
<div class="page-content">
    <ul>
        <?php foreach ($search_result as $item) :?>
            <li>
                <div class="title"><a href="<?= $item["frontend_url"];?>"><?= $item["title"];?></a></div>
                <div class="excerpt">
                    <?= $item["excerpt"];?>
                    <a class="small showmore" href="<?= $item["frontend_url"];?>"><?= $this->sitebase()->translate('show more');?></a>
                </div>
            </li>
        <?php endforeach;?>
    </ul>
    <?= $paginator; ?>
</div>
<?php else:?>
<h3><?= $this->sitebase()->translate('No elements found !');?></h3>
<?php endif;?>
<?= $this->section('content'); ?>
