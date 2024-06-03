<?php
/**
 * @var \App\Base\Abstracts\Controllers\AdminPage $controller
 * @var array $links
 */

 $links = $this->sitebase()->getAdminSidebarVisibleLinks($controller);

?>
<ul class="nav flex-column">
<?php foreach ($links as $sectionName => $sectionLinks) :?>
    <?php if (!empty($sectionName)):?>
        <li>
            <h6>&nbsp;<?= ucfirst(strtolower($this->sitebase()->translate($sectionName)));?></h6>
        </li>
    <?php endif;?>
    <?php foreach ($sectionLinks as $key => $link) :?>
        <li class="nav-item">
            <a class="nav-link<?= ($controller->getRouteName() == $link['route_name']) ? ' active' : '';?>" href="<?= $this->sitebase()->getUrl($link['route_name']);?>">
                <?php $this->sitebase()->drawIcon($link['icon']); ?> <span class="text"><?= $this->sitebase()->translate($link['text']);?></span>
            </a>
        </li>
    <?php endforeach;?>
<?php endforeach;?>

    <li>
        <a href="#" id="sidebar-minimize-btn">
            <span class="close-arrow"><?php $this->sitebase()->drawIcon('chevrons-left'); ?></span>
            <span class="open-arrow"><?php $this->sitebase()->drawIcon('chevrons-right'); ?></span>
        </a>
    </li>
</ul>
