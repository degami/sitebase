<?php
/**
 * @var \App\Base\Abstracts\Controllers\BaseHtmlPage $controller
 * @var array $links
 */

 $links = $this->sitebase()->getAdminSidebarMenu();

foreach ($links as $sectionName => $sectionLinks) {
    $sectionLinks = array_filter(array_map(function ($link) use ($controller) {
        if (empty($link['permission_name']) || $controller->checkPermission($link['permission_name'])) {
            return $link;
        }
        return false;
    }, $sectionLinks));

    if (empty($sectionLinks)) {
        unset($links[$sectionName]);
    }
}

?>
<ul class="nav flex-column">
<?php foreach ($links as $sectionName => $sectionLinks) :?>
    <?php if (!empty($sectionName)):?>
        <li>
            <h6>&nbsp;<?= ucfirst(strtolower($this->sitebase()->translate($sectionName)));?></h6>
        </li>
    <?php endif;?>
    <?php foreach ($sectionLinks as $key => $link) :?>
        <?php if (empty($link['permission_name']) || $controller->checkPermission($link['permission_name'])) : ?>
        <li class="nav-item">
            <a class="nav-link<?= ($controller->getRouteName() == $link['route_name']) ? ' active' : '';?>" href="<?= $this->sitebase()->getUrl($link['route_name']);?>">
                <?php $this->sitebase()->drawIcon($link['icon']); ?> <span class="text"><?= $this->sitebase()->translate($link['text']);?></span>
            </a>
        </li>
        <?php endif; ?>
    <?php endforeach;?>
<?php endforeach;?>

    <li>
        <a href="#" id="sidebar-minimize-btn">
            <span class="close-arrow"><?php $this->sitebase()->drawIcon('chevrons-left'); ?></span>
            <span class="open-arrow"><?php $this->sitebase()->drawIcon('chevrons-right'); ?></span>
        </a>
    </li>
</ul>
