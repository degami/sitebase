<?php
/**
 * @var string $page_title
 * @var array $links
 * @var string $paginator
 * @var string $locale
 * @var \Degami\PHPFormsApi\Form $form
 */
$this->layout('frontend::layout', ['title' => 'Links'] + get_defined_vars()) ?>

<h1 class="page-title"><?php echo $page_title;?></h1>
<div class="row">
    <div class="col-md-8">
        <ul class="links_exchange">
            <?php foreach ($links as $key => $link_exchange) :?>
                <li>
                    <a href="<?= $link_exchange->getUrl(); ?>" rel="nofollow" target="_blank" class="link-url">
                        <img style="max-width: 1em; vertical-align: baseline;" src="<?= $link_exchange->getDomain(); ?>/favicon.ico" />
                        <?= $link_exchange->getUrl(); ?>
                    </a>
                    <span class="link-title"><?= $link_exchange->getTitle(); ?></span>
                    <span class="link-description"><?= $link_exchange->getDescription(); ?></span>
                </li>
            <?php endforeach;?>
        </ul>
        <?= $paginator; ?>        
    </div>
    <div class="col-md-4">
        <div class="contact-form">
            <h2><?= $this->sitebase()->translate('Add your link'); ?></h2>
            <?php echo $form;?>
        </div>
    </div>
</div>
