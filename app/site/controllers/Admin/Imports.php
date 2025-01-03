<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Controllers\Admin;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Abstracts\Controllers\AdminFormPage;
use Degami\PHPFormsApi as FAPI;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use HaydenPierce\ClassFinder\ClassFinder;
use Symfony\Component\HttpFoundation\Response;
use App\Base\Abstracts\Models\BaseCollection;
use App\Base\Abstracts\Models\BaseModel;
use App\App;

/**
 * "Imports" Admin Page
 */
class Imports extends AdminFormPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    protected function getTemplateName(): string
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
            'icon' => 'arrow-down-circle',
            'text' => 'Imports',
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
    public function getFormDefinition(FAPI\Form $form, &$form_state): FAPI\Form
    {
        $exportableClasses = [];
        $modelClasses = ClassFinder::getClassesInNamespace('App\Site\Models');
        foreach ($modelClasses as $modelClass) {
            if (is_callable([$modelClass, 'isExportable']) && $this->containerCall([$modelClass, 'isExportable']) == true) {
                $className = str_replace("App\\Site\\Models\\", "", $modelClass);
                $exportableClasses[$modelClass] = $className;
            }
        }

        $form->addField('action', [
            'type' => 'value',
            'value' => 'import',
        ]);

        $form->addField('className', [
            'type' => 'select',
            'title' => 'Import Type',
            'default_value' => '',
            'options' => $exportableClasses,
            'validate' => ['required'],
        ]);

        $form->addField('csvfile', [
            'type' => 'file',
            'title' => 'Upload CSV',
            'default_value' => '',
            'destination' => $this->getApp()->getDir(App::TMP),
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
        $values = $form->values();

        $className = $values['className'];
        $modelHeader = array_map(fn($el) => $el['column_name'], $this->containerCall([$className, 'getExportHeader']));

        $csvFile = $values->csvfile->filepath;
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            if (($firstLine = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if (empty(array_diff($firstLine, $modelHeader))) {
                    return true;
                }
            }
            fclose($handle);
        }

        $form->addError("Invalid import file", __CLASS__.'::'.__FUNCTION__);
        return false;
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

        $className = $values['className'];
        $csvFile = $values->csvfile->filepath;

        $csvData = $this->getUtils()->csv2array($csvFile);
        $primaryKey = $this->containerCall([$className, 'getKeyField']);
        if (!is_array($primaryKey)) {
            $primaryKey = [$primaryKey];
        }

        foreach($csvData as $item) {
            if (empty(array_diff($primaryKey, array_keys($item)))) {
                // key values are present - loadByObjectIdentifier

                $id = [];
                foreach($primaryKey as $kname) {
                    $id[] = $item[$kname] ?? null;
                }

                // if only 1 column is present, get value
                if (count($id) == 1) {
                    $id = reset($id);
                }

                // load or create object
                try {
                    $object = $this->containerCall([$className, 'load'], ['id' => $id]);
                } catch (\Exception $e) {
                    $object = $this->containerCall([$className, 'new']);
                }
            } else {
                // key value is not present, create a new object
                $object = $this->containerCall([$className, 'new']);
            }

            /** @var BaseModel $object */
            foreach ($item as $key => $value) {
                $object->{$key} = $value;
            }

            $object->persist();
        }

        unlink($csvFile);

        return $this->refreshPage();
    }
}
