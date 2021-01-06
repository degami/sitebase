<?php
/**
 * @var $title string
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 */
$this->layout('base::page', ['title' => $title] + get_defined_vars());?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/admin.css');?>">
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/tinymce/tinymce.min.js');?>"></script>
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/js/admin.js');?>"></script>
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<!-- Content -->
<nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
    <div class="navbar-brand col-sm-3 col-md-2 mr-0">
        <a class="" href="/">
            <img class="img-fluid logo-image" title="<?= $this->sitebase()->env('APPNAME');?>" src="<?php echo $this->sitebase()->assetUrl('/sitebase_logo.png');?>" />
        </a>
    </div>
    <!-- <input class="form-control form-control-dark w-100" type="text" placeholder="Search" aria-label="Search"> -->

    <h1 class="h3 px-2 nowrap">-&nbsp;<?= ucwords(strtolower($this->sitebase()->translate($title))); ?>&nbsp;-</h1>
    <ul class="navbar-nav navbar-expand px-3 d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center py-1 w-100">
        <li class="nav-item px-1">
            <button type="button" id="sidebarCollapse" class="btn btn-dark d-sm-block d-md-none">
                <span class="navbar-toggler-icon"></span>
            </button>
        </li>
        <li class="nav-item px-1">
            <div class="text-nowrap px-1">
                <?php echo $this->sitebase()->getGravatar($current_user->email, 30);?>
                <?= $this->sitebase()->translate('Hello');?>  <?= $current_user->username; ?>
                <a class="btn btn-light btn-sm ml-2" href="<?= $this->sitebase()->getUrl('admin.logout');?>" title="<?= $this->sitebase()->translate('Sign out');?>">
                    <span class="d-none d-md-inline-block"><?= $this->sitebase()->translate('Sign out');?></span> <?php $this->sitebase()->drawIcon('log-out'); ?>
                </a>
            </div>
        </li>
    </ul>
</nav>

<div id="admin" class="container-fluid">
    <div class="row">
        <nav id="sidebar" class="col-md-2 bg-light sidebar<?= ($controller->getRequest()->cookies->get('sidebar_size') == 'minimized') ? ' collapsed' : ''; ?>">
            <div class="sidebar-sticky">
                <a href="#" class="closebtn d-sm-block d-md-none">&times;</a>
                <?php $this->insert('admin::partials/sidemenu', ['controller' => $controller]); ?>
            </div>
        </nav>            

        <main role="main" class="col-md-10 ml-sm-auto col-lg-10 pt-3 px-4">

            <?php $this->sitebase()->renderFlashMessages($controller); ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center mb-2 border-bottom">
                <span>&nbsp;</span>
                <nav class="navbar navbar-expand-md navbar-light m-1 p-0">
                    <?= $this->section('action_buttons'); ?>                    
                </nav>
            </div>

            <?=$this->section('content');?>

            <div id="toolsSidePanel" class="sidepanel">
                <a href="#" class="closebtn">&times;</a>
                <div class="card card-inverse m-2">
                    <div class="card-header"> <strong class="card-title">Panel title</strong> </div>
                    <div class="card-block p-2">
                        panel content
                    </div>
                </div>
            </div>
        </main>
    </div>
</div>
<footer class="version text-right"><?= $this->sitebase()->version();?></footer>
<div id="overlay" class="d-none"></div>
<!-- /Content -->
