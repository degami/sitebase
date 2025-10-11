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
            $versionsTable[] = [
                'id' => $version->id,
                'Date' => $version->created_at,
            ];
        }

        $diffTable = null;
        if ($this->getRequest()->query->get('version_a') && $this->getRequest()->query->get('version_b')) {
            $compare = $object->getVersions()->where(['id' => [
                $this->getRequest()->get('version_a'),
                $this->getRequest()->get('version_b'),
            ]])->getItems();
            /** @var ModelVersion $second */
            $second = reset($compare);
            /** @var ModelVersion $first */
            $first = end($compare);

            $diff = $first->compareWith($second);

            $diffTable = '<table border="1" cellpadding="6" cellspacing="0" style="border-collapse:collapse;width:100%;">';
            $diffTable .= '<thead><tr style="background:#ddd;"><th>Field</th><th>'.$first->getCreatedAt().'</th><th>'.$second->getCreatedAt().'</th></tr></thead><tbody>';
            $diffTable .= $this->renderDiffTable($diff);
            $diffTable .= '</tbody></table>';
        }

        $html = '<div class="container-fluid">
                    <div class="row">
                        <div class="col-3 p-0 border vh-100 overflow-auto">
                            <div class="version-instructions p-2 text-center small text-muted border-bottom">
                                <i class="fa fa-info-circle"></i> Ctrl+Click on two versions to compare them
                            </div>
                            '.$this->getHtmlRenderer()->renderAdminTable($versionsTable, ['id' => [], 'Date' => [], '' => []], table_id: 'versions-table').'
                        </div>
                        <div class="col-9 border vh-100 overflow-auto diff-content">'.($diffTable ?? '').'</div>
                    </div>
                </div>';

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'html' => $html,
            'js' => <<<JS
$(function() {
    let selected = [];

    const \$table = $('#versions-table');
    const \$rows = \$table.find('tbody tr');

    \$rows.on('click', function(e) {
        const \$row = $(this);
        const id = \$row.find('td:first').text().trim();
        if (!id) return;

        if (e.ctrlKey) {
            const index = selected.indexOf(id);
            if (index !== -1) {
                selected.splice(index, 1);
                \$row.css('background', '');
            } else {
                selected.push(id);
                \$row.css('background', '#d0e8ff');
            }

            if (selected.length > 2) {
                const first = selected.shift();
                \$rows.each(function() {
                    const rowId = $(this).find('td:first').text().trim();
                    if (rowId === first) {
                        $(this).css('background', '');
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
});
JS
        ];
    }

    private function renderDiffTable(array $diff, string $prefix = ''): string
    {
        $html = '';

        foreach ($diff as $key => $value) {
            if (is_array($value) && array_keys($value) !== ['current', 'other']) {
                $html .= '<tr><th colspan="3" style="background:#f0f0f0;">' . htmlspecialchars($prefix . $key) . '</th></tr>';
                $html .= $this->renderDiffTable($value, $prefix . $key . '.');
            } else {
                $current = $value['current'] ?? '';
                $other = $value['other'] ?? '';

                $current = $this->formatCellValue($current);
                $other = $this->formatCellValue($other);

                $html .= sprintf(
                    '<tr><td style="width:30%%;"><b>%s</b></td><td style="width:35%%;background:#ffecec;">%s</td><td style="width:35%%;background:#ecffec;">%s</td></tr>',
                    htmlspecialchars($prefix . $key),
                    $current,
                    $other
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
