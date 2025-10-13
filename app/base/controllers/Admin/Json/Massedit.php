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
use Degami\PHPFormsApi as FAPI;
use Exception;
use App\Base\Abstracts\Models\BaseModel;

/**
 * mass edit JSON
 */
class Massedit extends AdminJsonPage
{
    /**
     * return route path
     *
     * @return string
     */
    public static function getRoutePath(): string
    {
        return 'json/massedit/form';
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
        $modelClassName = $this->getRequest()->query->get('model_class_name');
        $controllerClassName = $this->getRequest()->query->get('controller_class_name');

        if (strtoupper($this->getRequest()->getMethod()) == 'POST') {
            $data = $this->getRequest()->request->all()['data'];

            // rename well-known variables
            foreach ($data as $k => $v) {
                if (preg_match("/.*?\_(latitude|longitude)/", $k, $matches)) {
                    $data[$matches[1]] = $v;
                    unset($data[$k]);
                }
            }

            $updated = 0;
            foreach ($this->getRequest()->request->all()['items'] as $item) {
                $id = json_decode(urldecode($item), true);
                if (!$id) {
                    continue;
                }

                try {
                    /** @var BaseModel $object */
                    $object = $this->containerCall([$modelClassName, 'load'], ['id' => $id]);
                    $object->setData($data);
                    $object->persist();

                    $updated++;
                } catch (Exception $e) {}
            }

            return ['updated' => $updated];
        }

        $cacheKey = 'massedit_form_' . md5($modelClassName . '|' . $controllerClassName);
        if ($this->getCache()) {
            $formHtml = $this->getCache()->get($cacheKey);
            if ($formHtml !== null) {
                return [
                    'success' => true,
                    'params' => $this->getRequest()->query->all(),
                    'html' => $formHtml,
                    'js' => "",
                ];
            }
        }

        // set action to new to get empty values form
        $this->getRequest()->query->set('action', 'new');

        $controller = $this->containerMake($controllerClassName);
        /** @var FAPI\Form $form */
        $form = $controller->getForm();

        $form->removeField('frontend')->removeField('seo');

        $formHtml = $form->render();

        $this->getCache()?->set($cacheKey, $formHtml, 3600);

        return [
            'success' => true,
            'params' => $this->getRequest()->query->all(),
            'html' => $formHtml,
            'js' => "",
        ];
    }
}
