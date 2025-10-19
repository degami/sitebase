<?php

/**
 * SiteBase
 * PHP Version 8.3
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Controllers\Admin\Json;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Models\ModelVersion;

/**
 * versions JSON
 */
class Versions extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/versions/{class:.+}/{key:.+}';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getJsonData(): array
    {
        $route_data = $this->getRouteData();
        $class = base64_decode($route_data['class']);
        $key = base64_decode($route_data['key']);

        /** @var BaseModel $object */
        $object = $this->containerCall([$class, 'load'], ['id' => $key]);

        $versions = $object->getVersions();
        $versionsTable = [];
        foreach ($versions as $version) {
            /** @var ModelVersion $version */
            $versionsTable[] = [
                'id' => $version->id,
                'Date' => $version->created_at,
                'User' => $version->getOwner()?->getUsername() ?? 'System',
            ];
        }

        $diffTable = null;
        if ($this->getRequest()->query->get('version_a') && $this->getRequest()->query->get('version_b')) {
            $compare = $object->getVersions()->where(['id' => [
                $this->getRequest()->query->get('version_a'),
                $this->getRequest()->query->get('version_b'),
            ]])->getItems();
            /** @var ModelVersion $second */
            $second = reset($compare);
            /** @var ModelVersion $first */
            $first = end($compare);

            $diff = $first->compareWith($second);

            $diffTable = '<table class="compare_versions" border="0" cellpadding="6" cellspacing="0">';
            $diffTable .= '<thead><tr style="background:#ddd;"><th>Field</th><th>'.$first->getCreatedAt().'</th><th>'.$second->getCreatedAt().'</th></tr></thead><tbody>';
            $diffTable .= $this->renderDiffTable($diff);
            $diffTable .= '</tbody></table>';
        }

        $html = '<div class="container-fluid">
                    <div class="row">
                        <div class="col-3 p-0 border vh-100 overflow-auto" style="border-right: 0 !important;">
                            <div class="version-instructions p-2 text-center small text-muted border-bottom">
                                <i class="fa fa-info-circle"></i> '.$this->getUtils()->translate('Ctrl+Click on two versions to compare them').'
                            </div>
                            '.$this->getHtmlRenderer()->renderAdminTable($versionsTable, ['id' => [], 'Date' => [], 'User' => []], table_id: 'versions-table').'
                        </div>
                        <div class="col-9 border vh-100 overflow-auto diff-content">'.($diffTable ?? '').'</div>
                    </div>
                </div>';

        $preselect = '';
        if ($diffTable !== null) {
            $preselect = <<<JS
\$rows.each(function() {
    const \$row = $(this);
    const id = \$row.find('td:first').text().trim();
    if (id === '{$this->getRequest()->query->get('version_a')}'
        || id === '{$this->getRequest()->query->get('version_b')}') {
        \$row.addClass('selected');
    }
});
JS;
        }

        $restoreStr = App::getInstance()->getUtils()->translate('Restore');
        $removeStr = App::getInstance()->getUtils()->translate('Remove');

        $versionControllerUrl = $this->getUrl('admin.versions');
        $versionDeleteUrl = $versionControllerUrl . '?action=delete&version_id=';
        $versionRestoreUrl = $versionControllerUrl . '?action=restore&version_id=';

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'html' => $html,
            'js' => <<<JS
$(function() {
    let selected = [];

    const \$table = $('#versions-table');
    const \$rows = \$table.find('tbody tr');

    $preselect

    \$rows.on('click', function(e) {
        const \$row = $(this);
        const id = \$row.find('td:first').text().trim();
        if (!id) return;

        if (e.ctrlKey) {

            if (selected.length === 0) {
                \$rows.css('background', '');
            }

            const index = selected.indexOf(id);
            if (index !== -1) {
                selected.splice(index, 1);
                \$row.css('background', '');
            } else {
                selected.push(id);
                \$row.addClass('selected');
            }

            if (selected.length > 2) {
                const first = selected.shift();
                \$rows.each(function() {
                    const rowId = $(this).find('td:first').text().trim();
                    if (rowId === first) {
                        $(this).removeClass('selected');
                    }
                });
            }

            if (selected.length === 2) {
                const [versionA, versionB] = selected;
                const url = new URL($('#versions-btn').attr('href'));
                url.searchParams.set('version_a', versionA);
                url.searchParams.set('version_b', versionB);

                $('.sidepanel', that).find('.diff-content').html('<span class="spinner-border spinner-border-sm me-2 m-2" style="width: 3rem; height: 3rem;" role="status" aria-hidden="true"></span>');
                $(that).appAdmin(
                    'loadPanelContent',
                    $(that).attr('title') || '',
                    url.toString(),
                    false
                );
            }
        }
    });

    let \$contextMenu = null;

    function hideContextMenu() {
        if (\$contextMenu) {
            \$contextMenu.remove();
            \$contextMenu = null;
        }
    }

    $(document)
    .off('.hideContextMenu')
    .on('click.hideContextMenu contextmenu.hideContextMenu scroll.hideContextMenu', function (ev) {
        if (!$(ev.target).closest('#versionsContextMenu').length) {
            hideContextMenu();
        }
    });

    let unbinders = $(that).data('sidePanelUnbinders') || [];
    unbinders.push(() => {
        $(document).off('.hideContextMenu');
        console.log('Context menu listener removed');
    });
    $(that).data('sidePanelUnbinders', unbinders);

    \$rows.on('contextmenu', function(e) {
        e.preventDefault();
        e.stopPropagation();
        hideContextMenu();

        const \$row = $(this);
        const id = \$row.find('td:first').text().trim();
        if (!id) return;

        \$contextMenu = $(`
            <div class="card shadow border bg-white" id="versionsContextMenu"
                style="z-index:99999; position:fixed; width:180px;">
                <div class="list-group list-group-flush">
                    <a href="#" class="list-group-item list-group-item-action text-danger" data-action="remove">
                        <i class="fa fa-trash me-2"></i> $removeStr
                    </a>
                    <a href="#" class="list-group-item list-group-item-action text-success" data-action="restore">
                        <i class="fa fa-undo me-2"></i> $restoreStr
                    </a>
                </div>
            </div>
        `);

        $('body').append(\$contextMenu);

        const menuWidth = 180;
        const menuHeight = \$contextMenu.outerHeight() || 90;
        let posX = e.clientX;
        let posY = e.clientY;

        if (posX + menuWidth > window.innerWidth) posX = window.innerWidth - menuWidth - 10;
        if (posY + menuHeight > window.innerHeight) posY = window.innerHeight - menuHeight - 10;

        \$contextMenu.css({
            top: posY + 'px',
            left: posX + 'px'
        });

        \$contextMenu.find('[data-action]').on('click', function(ev) {
            ev.preventDefault();
            const action = $(this).data('action');
            hideContextMenu();

            if (action === 'remove') {
                let currentRoute = $(that).appAdmin('getSettings').currentRoute;
                let form = $('<form action="{$versionDeleteUrl}'+id+'" method="post">').appendTo('body');
                $('<input type="hidden" value="'+encodeURIComponent(currentRoute)+'" name="return_route" />').appendTo(form);
                form.submit();
            } else if (action === 'restore') {
                let currentRoute = $(that).appAdmin('getSettings').currentRoute;
                let form = $('<form action="{$versionRestoreUrl}'+id+'" method="post">').appendTo('body');
                $('<input type="hidden" value="'+encodeURIComponent(currentRoute)+'" name="return_route" />').appendTo(form);
                form.submit();
            }
        });
    });
});
JS
        ];
    }

    private function renderDiffTable(array $diff, string $prefix = ''): string
    {
        $html = '';

        foreach ($diff as $key => $value) {
            if (is_array($value) && array_keys($value) !== ['current', 'other']) {
                $html .= '<tr><th colspan="3" class="prefix">' . htmlspecialchars($prefix . $key) . '</th></tr>';
                $html .= $this->renderDiffTable($value, $prefix . $key . '.');
            } else {
                $current = $value['current'] ?? '';
                $other = $value['other'] ?? '';

                $current = $this->formatCellValue($current);
                $other = $this->formatCellValue($other);

                $html .= sprintf(
                    '<tr><td><b>%s</b></td><td class="version_a">%s</td><td class="version_b">%s</td></tr>',
                    htmlspecialchars($prefix . $key),
                    $current ?? 'no-value',
                    $other ?? 'no-value'
                );
            }
        }

        return $html;
    }

    private function formatCellValue($value): string
    {
        if (is_array($value) && isset($value['__class']) && isset($value['__primaryKey'])) {
            return $value['__class'].'#'.$value['__primaryKey'];
        }

        if (is_array($value) || is_object($value)) {
            return '<pre style="margin:0;white-space:pre-wrap;font-size:12px;">' .
                htmlspecialchars(json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)) .
                '</pre>';
        }

        if (is_string($value) && strlen($value) > 200) {
            return '<details><summary>Show more</summary><pre style="white-space:pre-wrap;">' .
                htmlspecialchars($value) .
                '</pre></details>';
        }

        return htmlspecialchars((string)$value);
    }

}
