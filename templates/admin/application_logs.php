<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $listing string
 * @var $before_listing string
 * @var $paginator string
 * @var $logHtml string
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
    <link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/highlight.css');?>">
    <?= $this->section('head'); ?>
<?php $this->stop() ?>

<?php if ($action == 'list') : ?>
   <?= $before_listing ?? ''; ?>
    <div class="table-responsive">
        <?= $listing; ?>
    </div>
    <?= $paginator ?? ''; ?>
<?php elseif ($action == 'details') : ?>
    <script type="text/javascript">
        (function ($) {
            $(document).ready(function(){
                hljs.highlightBlock($('.code > code')[0]);
            })
        })(jQuery);
    </script>
    <?= $logHtml;?>
<?php else : ?>
    <?= $form; ?>
<?php endif;