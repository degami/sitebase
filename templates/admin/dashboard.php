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
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>
<div class="jumbotron p-4">
    <div class="row">
        <div class="col-2 text-center">
            <?php echo $this->sitebase()->getGravatar($current_user->email, 100);?>
        </div>
        <div class="col-10 mt-3">
            <h4><?= $this->sitebase()->translate('Welcome home');?>, <?= $current_user->getNickname();?></h4>
            <div class="info"><?= $current_user->getEmail();?> (<?= $this->sitebase()->translate('role');?>: <?= $current_user->getRole()->getName();?>)</div>
        </div>
    </div>
</div>

<div class="counters">
    <div class="row">
        <div class="col-6">
            <div class="counter"><label><?= $this->sitebase()->translate('Websites');?></label> <?= $websites;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Users');?></label> <?= $users;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Pages');?></label> <?= $pages;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Contact Forms');?></label> <?= $contact_forms;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Contact Submissions');?></label> <?= $contact_submissions;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Taxonomy Terms');?></label> <?= $taxonomy_terms;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('News');?></label> <?= $news;?></div>
        </div>
        <div class="col-6">
            <div class="counter"><label><?= $this->sitebase()->translate('Links');?></label> <?= $links;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Blocks');?></label> <?= $blocks;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Media');?></label> <?= $media;?></div>
            <div class="counter"><hr /></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Page Views');?></label> <?= $page_views;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Mails sent');?></label> <?= $mails_sent;?></div>
            <div><a href="<?= $this->sitebase()->getUrl('admin.stats');?>"><?= $this->sitebase()->translate('Stats');?></a></div>
        </div>
    </div>
</div>
