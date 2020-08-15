<?php
/**
 * @var $controller \App\Base\Abstracts\Controllers\BaseHtmlPage
 * @var $action string
 * @var $menus array
 * @var $form \Degami\PHPFormsApi\Form
 */
$this->layout('admin::layout', ['title' => $controller->getPageTitle()] + get_defined_vars()) ?>

<?php $this->start('head') ?>
<script type="text/javascript" src="/js/jquery-nestable/jquery.nestable.js"></script>
<style>
    .dd-item.level-0 > .dd-panel{
        visibility: hidden;
        height: 0;
    }
    .dd-item > button[data-action="collapse"]:before {
        display: none;
    }
    .dd-item.level-0 > .dd-list {
        padding-left: 0;
    }
    .dd-handle::before {
        top: 50% !important;
        margin-top: -50%;
    }

</style>
<?php $this->stop() ?>

<?php if ($action == 'list') : ?>
    <div class="table-responsive">
    <table width="100%" style="width: 100%;" class="table table-striped">
        <thead class="thead-dark">
            <tr>
                <th scope="col"><?= $this->sitebase()->translate('Menu Name');?></th>
                <th scope="col"></th>
            </tr>
        </thead>
        <tbody>
        <?php foreach ($menus as $key => $menu) :?>
            <tr class="<?= $key % 2 == 0 ? 'odd' : 'even';?>">
                <td scope="row"><?= $menu->menu_name; ?></td>
                <td class="text-right nowrap">
                    <a class="btn btn-primary btn-sm" href="<?= $controller->getControllerUrl();?>?action=view-menu-name&menu_name=<?= $menu->menu_name; ?>"><?php $this->sitebase()->drawIcon('zoom-in'); ?></a>
                </td>
            </tr>
        <?php endforeach;?>
        </tbody>
    </table>
    </div>
    <div class="clear"></div>
<?php else : ?>
    <?= $form; ?>
<?php endif; ?>
