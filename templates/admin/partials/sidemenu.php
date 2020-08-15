<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 */
?><ul class="nav flex-column">

<?php
$links = [
    [
        'permission_name' => '',
        'route_name' => 'admin.dashboard',
        'icon' => 'home',
        'text' => 'Dashboard',
    ],
    [
        'permission_name' => 'administer_blocks',
        'route_name' => 'admin.blocks',
        'icon' => 'box',
        'text' => 'Blocks',
    ],
    [
        'permission_name' => 'administer_pages',
        'route_name' => 'admin.pages',
        'icon' => 'file-text',
        'text' => 'Pages',
    ],
    [
        'permission_name' => 'administer_news',
        'route_name' => 'admin.news',
        'icon' => 'file-text',
        'text' => 'News',
    ],
    [
        'permission_name' => 'administer_taxonomy',
        'route_name' => 'admin.taxonomy',
        'icon' => 'list',
        'text' => 'Taxonomy',
    ],
    [
        'permission_name' => 'administer_medias',
        'route_name' => 'admin.media',
        'icon' => 'image',
        'text' => 'Media',
    ],
    [
        'permission_name' => 'administer_medias',
        'route_name' => 'admin.mediarewrites',
        'icon' => 'layers',
        'text' => 'Rewrites Media',
    ],
    [
        'permission_name' => 'administer_contact',
        'route_name' => 'admin.contactforms',
        'icon' => 'file-text',
        'text' => 'Contact Forms',
    ],
    [
        'permission_name' => 'administer_links',
        'route_name' => 'admin.links',
        'icon' => 'link',
        'text' => 'Links',
    ],
    [
        'permission_name' => 'administer_menu',
        'route_name' => 'admin.menus',
        'icon' => 'menu',
        'text' => 'Menu',
    ],
    [
        'permission_name' => 'administer_languages',
        'route_name' => 'admin.languages',
        'icon' => 'flag',
        'text' => 'Languages',
    ],
    [
        'permission_name' => 'administer_users',
        'route_name' => 'admin.roles',
        'icon' => 'award',
        'text' => 'Roles',
    ],
    [
        'permission_name' => 'administer_permissions',
        'route_name' => 'admin.permissions',
        'icon' => 'star',
        'text' => 'Permissions',
    ],
    [
        'permission_name' => 'administer_users',
        'route_name' => 'admin.users',
        'icon' => 'user',
        'text' => 'Users',
    ],
    [
        'permission_name' => 'administer_rewrites',
        'route_name' => 'admin.rewrites',
        'icon' => 'globe',
        'text' => 'Rewrites',
    ],
    [
        'permission_name' => 'administer_sitemaps',
        'route_name' => 'admin.sitemaps',
        'icon' => 'link',
        'text' => 'Sitemaps',
    ],
    [
        'permission_name' => 'administer_logs',
        'route_name' => 'admin.logs',
        'icon' => 'info',
        'text' => 'Logs',
    ],
    [
        'permission_name' => 'administer_site',
        'route_name' => 'admin.config',
        'icon' => 'sliders',
        'text' => 'Config',
    ],
    [
        'permission_name' => 'administer_site',
        'route_name' => 'admin.websites',
        'icon' => 'globe',
        'text' => 'Websites',
    ],
    [
        'permission_name' => 'administer_cron',
        'route_name' => 'admin.cron',
        'icon' => 'watch',
        'text' => 'Cron Tasks',
    ],
    [
        'permission_name' => 'administer_queue',
        'route_name' => 'admin.queue',
        'icon' => 'truck',
        'text' => 'Queue',
    ],
//    [
//        'permission_name' => '',
//        'route_name' => '',
//        'icon' => '',
//        'text' => '',
//    ],
];

?>

<?php foreach ($links as $key => $link) :?>
    <?php if (empty($link['permission_name']) || $controller->checkPermission($link['permission_name'])) : ?>
    <li class="nav-item">
        <a class="nav-link<?= ($controller->getRouteName() == $link['route_name']) ? ' active' : '';?>" href="<?= $this->sitebase()->getUrl($link['route_name']);?>">
            <?php $this->sitebase()->drawIcon($link['icon']); ?> <span class="text"><?= $this->sitebase()->translate($link['text']);?></span>
        </a>
    </li>
    <?php endif; ?>
<?php endforeach;?>


    <li>
        <a href="#" id="sidebar-minimize-btn">
            <span class="close-arrow"><?php $this->sitebase()->drawIcon('chevrons-left'); ?></span>
            <span class="open-arrow"><?php $this->sitebase()->drawIcon('chevrons-right'); ?></span>
        </a>
    </li>
</ul>
