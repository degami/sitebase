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
    <h2><?= $this->sitebase()->translate("Results for: <em>%s</em>", params: [$search_query]); ?></h2>
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
<?php else:?>
    <form action="" method="get">
        <div class="form-group">
        <div class="searchbar input-group">
            <input type="text" name="q" value="" class="form-control" />
            <div class="input-group-append">
                <button type="submit" value="<?= $this->sitebase()->translate('Search');?>" class="btn searchbtn">
                    <?php $this->sitebase()->drawIcon('search');?>
                </button>
            </div>
            <div class="input-group pt-2">
                <div class="pretty p-icon p-round">
                    <input id="use-ai" class="form-check-input" type="checkbox" role="switch" name="ai" value=1 />
                    <div class="state">
                        <i class="icon fa fa-check"></i>
                        <label class="form-check-label" for="use-ai"><?= $this->sitebase()->translate('Use AI Search'); ?></label>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </form>
<?php endif;?>

<?= $this->section('content'); ?>
