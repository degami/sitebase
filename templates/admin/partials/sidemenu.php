<?php
/**
 * @var \App\Base\Abstracts\Controllers\AdminPage $controller
 * @var array $links
 */

 $links = $this->sitebase()->getAdminSidebarVisibleLinks($controller);

?>
<!--
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
-->


<div class="nav flex-column" id="accordion">
<?php $collapsed = 'collapse'; ?>
<?php foreach ($links as $sectionName => $sectionLinks) :?>
    <?php $sectionKey = str_replace(" ", "_", strtolower(trim($sectionName))); ?>
    <?php 
        if (in_array(true, array_filter(array_map(function($link) use ($controller) {
            return $controller->getRouteName() == $link['route_name'];
        }, $sectionLinks))) ) {
            $collapsed = 'collapse show';
        }
    ?>
    <div class="card">
        <div class="card-header" id="heading<?= $sectionKey; ?>">
            <h5 class="mb-0">
                <button class="btn d-block text-left pl-0 shadow-none" data-toggle="collapse" data-target="#collapse<?= $sectionKey; ?>" aria-expanded="true" aria-controls="collapseOne">
                    <?php $this->sitebase()->drawIcon($sectionKey, [], true); ?> <span class="text"><?= ucfirst(strtolower($this->sitebase()->translate($sectionName)));?></span>
                </button>
            </h5>
        </div>

        <div id="collapse<?= $sectionKey; ?>" class="<?= $collapsed; ?>" aria-labelledby="heading<?= $sectionKey; ?>" data-parent="#accordion">
            <div class="card-body">
                <?php foreach ($sectionLinks as $key => $link) :?>
                    <div class="nav-item">
                        <a class="nav-link<?= ($controller->getRouteName() == $link['route_name']) ? ' active' : '';?>" href="<?= $this->sitebase()->getUrl($link['route_name']);?>">
                            <?php $this->sitebase()->drawIcon($link['icon']); ?> <span class="text"><?= $this->sitebase()->translate($link['text']);?></span>
                        </a>
                </div>
                <?php endforeach;?>
            </div>
        </div>
    </div>
    <?php $collapsed = 'collapse'; ?>    
<?php endforeach;?>
</div>

<a href="#" id="sidebar-minimize-btn">
    <span class="close-arrow"><?php $this->sitebase()->drawIcon('chevrons-left'); ?></span>
    <span class="open-arrow"><?php $this->sitebase()->drawIcon('chevrons-right'); ?></span>
</a>