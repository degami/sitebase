<?php
/**
 * @var $page_title string
 * @var $links array
 * @var $paginator string
 * @var $locale string
 * @var $form \Degami\PHPFormsApi\Form
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
            <h2><?= __('Add your link', $locale); ?></h2>
            <?php echo $form;?>
        </div>
    </div>
</div>
