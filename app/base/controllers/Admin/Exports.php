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

namespace App\Base\Controllers\Admin;

use App\App;
use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminFormPage;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Models\BaseCollection;

/**
 * "Exports" Admin Page
 */
class Exports extends AdminFormPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'form_admin_page';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_configuration';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public Function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => static::getAccessPermission(),
            'route_name' => static::getPageRouteName(),
            'icon' => 'archive',
            'text' => 'Exports',
            'section' => 'tools',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return FAPI\Form
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getFormDefinition(FAPI\Form $form, array &$form_state): FAPI\Form
    {
        $exportableClasses = [];
        $modelClasses = ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE);
        foreach ($modelClasses as $modelClass) {
            if (is_callable([$modelClass, 'isExportable']) && $this->containerCall([$modelClass, 'isExportable']) == true) {
                $className = str_replace("App\\Site\\Models\\", "", $modelClass);
                $exportableClasses[$modelClass] = $className;
            }
        }

        $form->setAttribute('target', '_blank')->addField('action', [
            'type' => 'value',
            'value' => 'export',
        ]);

        $form->addField('className', [
            'type' => 'select',
            'title' => 'Export Type',
            'default_value' => '',
            'options' => $exportableClasses,
            'validate' => ['required'],
        ]);

        $this->addSubmitButton($form);

        return $form;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return bool|string
     */
    public function formValidate(FAPI\Form $form, &$form_state): bool|string
    {
        //$values = $form->values();
        return true;
    }

    /**
     * {@inheritdoc}
     *
     * @param FAPI\Form $form
     * @param array     &$form_state
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function formSubmitted(FAPI\Form $form, &$form_state): mixed
    {
        $values = $form->values();

        $csvContent = '';

        $className = $values['className'];
        $classKey = strtolower(trim(str_replace("App\\Site\\Models\\", "", $className)));
        $csvHeader = array_map(fn($el) => $el['column_name'], $this->containerCall([$className, 'getExportHeader']));

        /** @var BaseCollection $collection */
        $collection = $this->containerCall([$className, 'getCollection']);
        $csvData = array_map(fn($model) => $model->getExportRowData(), $collection->getItems());

        $csvContent = $this->getUtils()->array2csv($csvData, $csvHeader);

        $response = new Response($csvContent);
        $response->headers->set('Content-Encoding', 'UTF-8');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename=export_'.$classKey.'_'.date('Ymd_His').'.csv');
        return $response;
    }
}
