<?php
/**
 * @var $title string
 * @var $current_user \App\Base\Abstracts\Models\AccountModel
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 */

$this->layout('base::layout', ['title' => $title] + get_defined_vars());?>

<?php $this->start('head') ?>
<link rel="stylesheet" type="text/css" href="<?php echo $this->sitebase()->assetUrl('/css/admin.css');?>">
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/tinymce/tinymce.js');?>"></script>
<script type="text/javascript" src="<?php echo $this->sitebase()->assetUrl('/js/admin.js');?>"></script>
<?= $this->section('head'); ?>
<?php $this->stop() ?>

<!-- Content -->
<nav class="navbar navbar-dark sticky-top bg-dark flex-md-nowrap p-0">
    <div class="navbar-brand col-sm-3 col-md-2 mr-0<?= ($controller->getSidebarSize() == 'minimized') ? ' collapsed' : ''; ?>">
        <a class="" href="/">
            <img class="img-fluid logo-image" title="<?= $this->sitebase()->env('APPNAME');?>" src="<?php echo $this->sitebase()->assetUrl('/sitebase_logo.png');?>" />
            <img class="img-fluid logo-image-small" title="<?= $this->sitebase()->env('APPNAME');?>" src="<?php echo $this->sitebase()->assetUrl('/sitebase_logo_small.png');?>" />
        </a>
    </div>
    <!-- <input class="form-control form-control-dark w-100" type="text" placeholder="Search" aria-label="Search"> -->

    <h1 class="h3 px-2 nowrap page-title">-&nbsp;<?php $this->sitebase()->drawIcon($icon); ?> <?= ucwords(strtolower($this->sitebase()->translate($title))); ?>&nbsp;-</h1>
    <ul class="navbar-nav navbar-expand px-3 d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center py-1 w-100">
        <li class="nav-item px-1">
            <button type="button" id="sidebarCollapse" class="btn btn-dark d-sm-block d-md-none">
                <span class="navbar-toggler-icon"></span>
            </button>
        </li>
        <li class="nav-item px-1">
            <div class="text-nowrap px-1 d-flex align-items-center">
                <?= $this->sitebase()->getDarkModeSwitch($controller); ?>
                <?= $this->sitebase()->getGravatar($current_user->email, 30, class: 'rounded-circle mr-2');?>
                <?= $this->sitebase()->translate('Hello');?>&nbsp;<a href="<?= $this->sitebase()->getUrl('admin.users'); ?>?action=edit&user_id=<?= $current_user->id; ?>"><?= $current_user->nickname; ?></a>
                <a class="btn btn-light btn-sm ml-2" id="logout-btn" href="<?= $this->sitebase()->getUrl('admin.logout');?>" title="<?= $this->sitebase()->translate('Sign out');?>">
                    <span class="d-none d-md-inline-block"><?= $this->sitebase()->translate('Sign out');?></span> <?php $this->sitebase()->drawIcon('log-out'); ?>
                </a>
            </div>
        </li>
    </ul>
</nav>

<div id="admin" class="container-fluid">
    <div class="row">
        <nav id="sidebar" class="col-md-2 bg-light sidebar<?= ($controller->getSidebarSize() == 'minimized') ? ' collapsed' : ''; ?>">
            <div class="sidebar-sticky">
                <a href="#" class="closebtn d-sm-block d-md-none">&times;</a>
                <?php $this->insert('admin::partials/sidemenu', ['controller' => $controller]); ?>
            </div>
            <a href="#" id="sidebar-minimize-btn">
                <span class="close-arrow"><?php $this->sitebase()->drawIcon('chevrons-left'); ?></span>
                <span class="open-arrow"><?php $this->sitebase()->drawIcon('chevrons-right'); ?></span>
            </a>
        </nav>          

        <main role="main" class="col-md-10 ml-sm-auto col-lg-10 pt-3 px-4">

            <?php $this->sitebase()->renderFlashMessages($controller); ?>

            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-start mb-2 border-bottom justify-content-lg-space-between">
                <nav id="nav-layout-buttons" class="navbar navbar-expand-lg navbar-light m-1 p-0">
                    <?= $this->section('layout_buttons'); ?>
                </nav>

                <nav id="nav-action-buttons" class="navbar navbar-expand-lg navbar-light m-1 p-0">
                    <div class="d-flex align-items-end flex-column">
                        <button type="button" id="actionButtonsCollapse" class="btn btn-sm d-sm-block d-md-none">
                            <span class="navbar-toggler-icon"></span> <?= $this->sitebase()->translate('Actions');?>
                        </button>

                        <?= $this->section('action_buttons'); ?>
                    </div>
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

            <?php if ($this->sitebase()->isAiAvailable()): ?>
            <div id="chatSidebar" class="sideChat position-fixed border-start shadow">
                <div id="chatSidebarResizer" class="position-absolute top-0 start-0 h-100" style="width: 6px; cursor: ew-resize;"></div>

                <div class="p-1 border-bottom d-flex justify-content-between align-items-center">
                    <strong class="ml-3">Chat AI</strong>
                    <select id="chatAISelector" class="select-processed" style="width: 150px;">
                        <?php foreach ($this->sitebase()->getEnabledAIs(true) as $ai => $AIinfo) : ?>
                            <option value="<?= $ai; ?>"><?= ucfirst($AIinfo['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <a href="#" class="closebtn mr-2">&times;</a>
                </div>
                <div id="chatMessages" class="chat-messages px-3 py-2 overflow-auto" style="height: calc(100vh - <?php if ($this->sitebase()->getEnvironment()->canDebug()): ?>155px<?php else: ?>120px<?php endif;?>);">

                </div>
                <div class="d-flex">
                    <input type="text" id="chatInput" class="form-control me-2" placeholder="<?= $this->sitebase()->translate('ask a question');?>" />
                    <button id="chatSendBtn" class="btn btn-primary"><?= $this->sitebase()->translate('Send');?></button>
                </div>
            </div>
            <?php endif; ?>

        </main>
    </div>
</div>
<footer class="version text-right"><?= $this->sitebase()->version();?></footer>
<div id="overlay" class="overlay d-none"></div>
<!-- /Content -->
