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

namespace App\Site\Controllers\Admin\Json;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminJsonPage;
use App\Base\Exceptions\InvalidValueException;
use App\Site\Models\MediaElement;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * media paste JSON
 */
class MediaPaste extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/media/paste';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_medias';
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

        $data = $this->getRequest()->request->all();

        $action = $data['action'];
        $ids = $data['ids'];
        $parent_id = $data['parent_id'];

        /** @var MediaElement $parentMedia */
        $parentMedia = MediaElement::load($parent_id);
        if (!$parentMedia->isDirectory()) {
            throw new InvalidValueException("Target Element is not a directory");
        }

        $destinationPath = $parentMedia->getPath();

        $result = array_map(function ($el) use ($destinationPath, $action) {
            /** @var MediaElement $element */
            $element = MediaElement::load($el['id']);
            return match($action) {
                'copy' => $element->copy($destinationPath),
                'move' => $element->move($destinationPath)
            };
        }, $ids);

        return [
            'success' => true,
            'params' => $data,
            'result_paths' => array_map(fn (MediaElement $el) => $el->getPath(), $result),
            'html' => "",
            'js' => "",
        ];
    }
}
