<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.profile');?>">
            <?php $this->sitebase()->drawIcon('home'); ?> <span class="text"><?= $this->sitebase()->translate('Profile');?></span>
        </a>
    </li>

    <?php if ($this->sitebase()->isCommerceAvailable()) : ?>
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.addresses');?>">
            <?php $this->sitebase()->drawIcon('book'); ?> <span class="text"><?= $this->sitebase()->translate('My Addresses');?></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.orders');?>">
            <?php $this->sitebase()->drawIcon('shopping-bag'); ?> <span class="text"><?= $this->sitebase()->translate('My Orders');?></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.downloads');?>">
            <?php $this->sitebase()->drawIcon('download'); ?> <span class="text"><?= $this->sitebase()->translate('My Downloads');?></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.giftcards');?>">
            <?php $this->sitebase()->drawIcon('gift'); ?> <span class="text"><?= $this->sitebase()->translate('My Giftcards');?></span>
        </a>
    </li>
    <?php endif;?>

    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.logout');?>">
            <?php $this->sitebase()->drawIcon('log-out'); ?> <span class="text"><?= $this->sitebase()->translate('Sign out');?></span>
        </a>
    </li>
</ul>
