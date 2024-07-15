<?php
/**
 * @var $object \App\Base\Abstracts\Models\FrontendModel
 */
define('KILOMETER', 1000);
$this->layout('frontend::layout', ['title' => $object->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<link rel="canonical" href="<?= $object->getFrontendUrl();?>" />
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<h1 class="event-title"><?php echo $object->getTitle();?></h1>
<div class="event-date"><?php echo $object->getDate();?></div>
<div class="event-location">lat: <?php echo $object->getLocation()['latitude'];?>, lon: <?php echo $object->getLocation()['longitude'];?></div>
<div class="event-content"><?php echo $object->getContent();?></div>

<?= $this->section('content'); ?>


<h3 class="nearby"><?= $this->sitebase()->translate('Near by Events');?></h3>
<div class="row">
    <div class="col-md-12">
        <ul class="event-list">
            <?php foreach ($object->nearBy(10000 * KILOMETER) as $key => $event) :?>
                <li>
                    <div>
                        <a href="<?= $event->getFrontendUrl(); ?>" class="event-detail">
                            <span class="event-title"><?= $event->getTitle(); ?></span>
                        </a>
                        <span class="event-date"><?= $event->getDate(); ?></span>
                        <span class="event-distance"><?= $this->sitebase()->translate('distance');?>: <?= round($event->distance($object) / KILOMETER, 2); ?> km </span>
                    </div>
                    <div class="event-description"><?= $this->sitebase()->summarize($event->getContent(), 20); ?></div>
                </li>
            <?php endforeach;?>
        </ul>
    </div>
</div>
