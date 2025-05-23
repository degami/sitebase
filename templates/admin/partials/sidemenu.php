<?php
/**
 * @var \App\Base\Abstracts\Controllers\AdminPage $controller
 * @var array $links
 */

 $links = $this->sitebase()->getAdminSidebarVisibleLinks($controller);

?>
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
                    <?php $this->sitebase()->drawIcon($sectionKey, [], true); ?> <span class="text"><?= $this->sitebase()->translate(ucfirst(strtolower($sectionName)));?></span>
                </button>
            </h5>
        </div>

        <div id="collapse<?= $sectionKey; ?>" class="<?= $collapsed; ?>" aria-labelledby="heading<?= $sectionKey; ?>" data-parent="#accordion">
            <div class="card-body">
                <?php foreach ($sectionLinks as $key => $link) :?>
                    <div class="nav-item">
                        <a class="nav-link<?= ($controller->getRouteName() == $link['route_name']) ? ' active' : '';?> <?= $link['route_name'];?>" href="<?= $this->sitebase()->getUrl($link['route_name']);?>" title="<?= $this->sitebase()->translate($link['text']);?>">
                            <?php $this->sitebase()->drawIcon($link['icon']); ?> <span class="text"><?= $this->sitebase()->translate($link['text']);?></span>
                        </a>
                    </div>
                <?php endforeach;?>
            </div>
        </div>
    </div>
    <?php $collapsed = 'collapse'; ?>    
<?php endforeach;?>
<?php if ($this->sitebase()->isAiAvailable()): ?>
    <div class="card-header" id="heading ia-chat mt-5">
        <div class="nav-item">
            <a class="nav-link ai-chat" href="#" onclick="$('#admin').appAdmin('openAIChat')" title="<?= $this->sitebase()->translate('AI Chat');?>">
                <?php $this->sitebase()->drawIcon('message-square'); ?> <span class="text"><?= $this->sitebase()->translate('AI Chat');?></span>
            </a>
        </div>
    </div>
<?php endif;?>
</div>

<a href="#" id="sidebar-minimize-btn">
    <span class="close-arrow"><?php $this->sitebase()->drawIcon('chevrons-left'); ?></span>
    <span class="open-arrow"><?php $this->sitebase()->drawIcon('chevrons-right'); ?></span>
</a>