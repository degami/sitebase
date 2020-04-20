<ul class="nav flex-column">
    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.profile');?>">
            <?php $this->sitebase()->drawIcon('home'); ?> <span class="text"><?= $this->sitebase()->translate('Profile');?></span>
        </a>
    </li>

    <li class="nav-item">
        <a class="nav-link" href="<?= $this->sitebase()->getUrl('frontend.users.logout');?>">
            <?php $this->sitebase()->drawIcon('log-out'); ?> <span class="text"><?= $this->sitebase()->translate('Sign out');?></span>
        </a>
    </li>
</ul>
