<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 * @var $websites integer
 * @var $users integer
 * @var $pages integer
 * @var $contact_forms integer
 * @var $contact_submissions integer
 * @var $taxonomy_terms integer
 * @var $news integer
 * @var $links integer
 * @var $blocks integer
 * @var $media integer
 * @var $page_views integer
 * @var $mails_sent integer
 */

$bySection = ($this->sitebase()->getUseruISetting($controller, 'dashboardLayout') ?? 'list') == 'by_section';
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>
<div class="jumbotron p-4 position-relative">
    <div class="row">
        <div class="col-2 text-center">
            <?php echo $this->sitebase()->getGravatar($current_user->email, 100);?>
        </div>
        <div class="col-10 mt-3">
            <h4><?= $this->sitebase()->translate('Welcome home');?>, <?= $current_user->getNickname();?></h4>
            <div class="info"><?= $current_user->getEmail();?> (<?= $this->sitebase()->translate('role');?>: <?= $current_user->getRole()->getName();?>)</div>
        </div>
    </div>

    <div class="dashboard-bysection-switch position-absolute" style="top: 15px; right: 15px;"><?= $this->sitebase()->translate('Group By Section');?> 
        <label class="switch">
            <input type="checkbox" id="dashboard-layout-selector" value="" class="paginator-items-choice" style="width: 50px"<?php if ($bySection) :?> checked="checked"<?php endif;?>>
            <span class="slider"></span>
        </label>
    </div>

</div>

<div class="counters container-fluid">
    <div class="row row-cols-3 justify-content-md-between">
<?php if ($bySection): 
    $bySectionLinks = [];
    foreach ($dashboard_links as $link) {
        $section = $link['section'] ?? 'general';
        if (!isset($bySectionLinks[$section])) {
            $bySectionLinks[$section] = [];
        }
        $bySectionLinks[$section][] = $link;
    }
    ksort($bySectionLinks);
?>
            <?php foreach ($bySectionLinks as $sectionName => $chunk) : ?>
                <div class="col">
                    <div class="h4 font-weight-bolder text-center"><?= $this->sitebase()->translate(ucfirst($sectionName)); ?></div>
                    <div class="mb-2"><hr /></div>
                    <?php foreach ($chunk as $link): ?>
                        <div class="counter mb-2 d-flex justify-content-between align-items-center">
                            <label class="text-left nowrap pl-5 d-inline-flex align-items-center">
                                <a class="d-flex align-items-center" href="<?= $this->sitebase()->getUrl($link['route_name']);?>">
                                    <?php $this->sitebase()->drawIcon($link['icon'])?>&nbsp;<?= $this->sitebase()->translate($link['label']);?>
                                </a>
                            </label> 
                            <?= $link['data'];?>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endforeach;?>

            <div class="col">

                <div class="counter mb-2 d-flex justify-content-between"><hr /></div>
                <div class="counter mb-2 d-flex justify-content-between"><label class="text-left nowrap pl-5 d-inline-flex align-items-center"><?php $this->sitebase()->drawIcon('info')?>&nbsp;<?= $this->sitebase()->translate('Page Views');?></label> <?= $page_views;?></div>
                <div class="counter mb-2 d-flex justify-content-between"><label class="text-left nowrap pl-5 d-inline-flex align-items-center"><?php $this->sitebase()->drawIcon('mail')?>&nbsp;<?= $this->sitebase()->translate('Mails sent');?></label> <?= $mails_sent;?></div>
                <?php if ($controller->checkPermission('administer_logs')) :?>
                    <div class="text-left nowrap pl-5"><a class="btn btn-light d-flex align-items-center justify-content-center" href="<?= $this->sitebase()->getUrl('admin.stats');?>"><?php $this->sitebase()->drawIcon('bar-chart')?>&nbsp;<?= $this->sitebase()->translate('Stats');?></a></div>
                <?php endif; ?>

            </div>

<?php else: ?>

        <?php $chunks = array_chunk($dashboard_links, ceil(count($dashboard_links) / 3 + 2)); ?>
        <?php foreach ($chunks as $chunkIndex => $chunk) : ?>
            <div class="col text-center">
            <?php foreach ($chunk as $link): ?>
                <div class="counter mb-2 d-flex justify-content-between align-items-center">
                    <label class="text-left nowrap pl-5 d-inline-flex align-items-center">
                        <a class="d-flex align-items-center" href="<?= $this->sitebase()->getUrl($link['route_name']);?>">
                            <?php $this->sitebase()->drawIcon($link['icon'])?>&nbsp;<?= $this->sitebase()->translate($link['label']);?>
                        </a>
                    </label> 
                    <?= $link['data'];?>
                </div>
            <?php endforeach; ?>

            <?php if ($chunkIndex == 2) : ?>
                <div class="counter mb-2 d-flex justify-content-between"><hr /></div>
                <div class="counter mb-2 d-flex justify-content-between"><label class="text-left nowrap pl-5 d-inline-flex align-items-center"><?php $this->sitebase()->drawIcon('info')?>&nbsp;<?= $this->sitebase()->translate('Page Views');?></label> <?= $page_views;?></div>
                <div class="counter mb-2 d-flex justify-content-between"><label class="text-left nowrap pl-5 d-inline-flex align-items-center"><?php $this->sitebase()->drawIcon('mail')?>&nbsp;<?= $this->sitebase()->translate('Mails sent');?></label> <?= $mails_sent;?></div>
                <?php if ($controller->checkPermission('administer_logs')) :?>
                    <div class="text-left nowrap pl-5"><a class="btn btn-light d-flex align-items-center justify-content-center" href="<?= $this->sitebase()->getUrl('admin.stats');?>"><?php $this->sitebase()->drawIcon('bar-chart')?>&nbsp;<?= $this->sitebase()->translate('Stats');?></a></div>
                <?php endif; ?>
            <?php endif; ?>

            </div>
        <?php endforeach ?>

<?php endif; ?>
    </div>
</div>