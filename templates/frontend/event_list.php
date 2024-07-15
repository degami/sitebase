<?php
/**
 * @var $paginator array
 * @var $events array
 * @var $page_title string
 */
$this->layout('frontend::layout', ['title' => $this->sitebase()->translate('Events')] + get_defined_vars()) ?>

<h1 class="page-title"><?php echo $page_title;?></h1>
<div class="row">
    <div class="col-md-12">
        <ul class="event-list">
            <?php foreach ($events as $key => $event) :?>
                <li>
                    <div>
                        <a href="<?= $event->getFrontendUrl(); ?>" class="news-detail">
                            <span class="event-title"><?= $event->getTitle(); ?></span>
                        </a>
                        <span class="event-date"><?= $event->getDate(); ?></span>
                    </div>
                    <div class="event-description"><?= $this->sitebase()->summarize($event->getContent(), 20); ?></div>
                </li>
            <?php endforeach;?>
        </ul>
        <?= $paginator; ?>        
    </div>
</div>
