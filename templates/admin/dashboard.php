<?php
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>
<div class="jumbotron">
    <h4><?= $this->sitebase()->translate('Welcome home');?>, <?= $current_user->getNickname();?></h4>
    <div class="info"><?= $current_user->getEmail();?> (<?= $this->sitebase()->translate('role');?>: <?= $current_user->getRole()->getName();?>)</div>
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
        </div>
        <div class="col-6">
            <div class="counter"><label><?= $this->sitebase()->translate('News');?></label> <?= $news;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Links');?></label> <?= $links;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Blocks');?></label> <?= $blocks;?></div>
            <div class="counter"><label><?= $this->sitebase()->translate('Media');?></label> <?= $media;?></div> 
            <div class="counter"><hr /></div> 
            <div class="counter"><label><?= $this->sitebase()->translate('Page Views');?></label> <?= $page_views;?></div> 
            <div class="counter"><label><?= $this->sitebase()->translate('Mails sent');?></label> <?= $mails_sent;?></div> 
        </div>
    </div>
</div>