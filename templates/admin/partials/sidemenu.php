<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.dashboard');?>">
            <?php $this->sitebase()->drawIcon('home'); ?> <span class="text"><?= $this->sitebase()->translate('Dashboard');?></span>
        </a>
    </li>

    <?php if ($controller->checkPermission('administer_blocks')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.blocks');?>">
            <?php $this->sitebase()->drawIcon('box'); ?> <span class="text"><?= $this->sitebase()->translate('Blocks');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_pages')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.pages');?>">
            <?php $this->sitebase()->drawIcon('file-text'); ?> <span class="text"><?= $this->sitebase()->translate('Pages');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_news')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.news');?>">
            <?php $this->sitebase()->drawIcon('file-text'); ?> <span class="text"><?= $this->sitebase()->translate('News');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_taxonomy')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.taxonomy');?>">
            <?php $this->sitebase()->drawIcon('list'); ?> <span class="text"><?= $this->sitebase()->translate('Taxonomy');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_medias')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.media');?>">
            <?php $this->sitebase()->drawIcon('image'); ?> <span class="text"><?= $this->sitebase()->translate('Media');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_medias')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.mediarewrites');?>">
            <?php $this->sitebase()->drawIcon('layers'); ?> <span class="text"><?= $this->sitebase()->translate('Rewrites Media');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_contact')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.contactforms');?>">
            <?php $this->sitebase()->drawIcon('file-text'); ?> <span class="text"><?= $this->sitebase()->translate('Contact Forms');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_links')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.links');?>">
            <?php $this->sitebase()->drawIcon('link'); ?> <span class="text"><?= $this->sitebase()->translate('Links');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_menu')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.menus');?>">
            <?php $this->sitebase()->drawIcon('menu'); ?> <span class="text"><?= $this->sitebase()->translate('Menu');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_languages')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.languages');?>">
            <?php $this->sitebase()->drawIcon('flag'); ?> <span class="text"><?= $this->sitebase()->translate('Languages');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_users')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $controller->getUrl('admin.roles');?>">
            <?php $this->sitebase()->drawIcon('award'); ?> <span class="text"><?= $this->sitebase()->translate('Roles');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_permissions')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.permissions');?>">
            <?php $this->sitebase()->drawIcon('star'); ?> <span class="text"><?= $this->sitebase()->translate('Permissions');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_users')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.users');?>">
            <?php $this->sitebase()->drawIcon('user'); ?> <span class="text"><?= $this->sitebase()->translate('Users');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_rewrites')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.rewrites');?>">
            <?php $this->sitebase()->drawIcon('globe'); ?> <span class="text"><?= $this->sitebase()->translate('Rewrites');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_sitemaps')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.sitemaps');?>">
            <?php $this->sitebase()->drawIcon('link'); ?> <span class="text"><?= $this->sitebase()->translate('Sitemaps');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_logs')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.logs');?>">
            <?php $this->sitebase()->drawIcon('info'); ?> <span class="text"><?= $this->sitebase()->translate('Logs');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_site')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.config');?>">
            <?php $this->sitebase()->drawIcon('sliders'); ?> <span class="text"><?= $this->sitebase()->translate('Config');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_site')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.websites');?>">
            <?php $this->sitebase()->drawIcon('globe'); ?> <span class="text"><?= $this->sitebase()->translate('Websites');?></span>
        </a>
    </li>
    <?php endif; ?>
    
    <?php if ($controller->checkPermission('administer_cron')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.cron');?>">
            <?php $this->sitebase()->drawIcon('watch'); ?> <span class="text"><?= $this->sitebase()->translate('Cron Tasks');?></span>
        </a>
    </li>
    <?php endif; ?>

    <?php if ($controller->checkPermission('administer_queue')) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('admin.queue');?>">
            <?php $this->sitebase()->drawIcon('truck'); ?> <span class="text"><?= $this->sitebase()->translate('Queue');?></span>
        </a>
    </li>
    <?php endif; ?>


    <li>
        <a href="#" id="sidebar-minimize-btn">
            <span class="close-arrow"><?php $this->sitebase()->drawIcon('chevrons-left'); ?></span>
            <span class="open-arrow"><?php $this->sitebase()->drawIcon('chevrons-right'); ?></span>
        </a>
    </li>
</ul>
