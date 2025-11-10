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

namespace App\Base\Abstracts\Controllers;

use Degami\Basics\Exceptions\BasicException;
use App\Base\Traits\AdminFormTrait;
use Degami\Basics\Html\TagElement;
use DI\DependencyException;
use DI\NotFoundException;
use App\Base\Abstracts\Models\BaseModel;

/**
 * Base for admin page that manages a Frontend Model
 */
abstract class AdminManageFrontendModelsPage extends AdminManageModelsPage
{
    use AdminFormTrait;

    /**
     * gets translation button
     *
     * @param $object
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function getTranslationsButton($object): string
    {
        if (!$object->getRewrite()) {
            return '';
        }
        try {
            $button = $this->containerMake(TagElement::class, ['options' => [
                'tag' => 'a',
                'attributes' => [
                    'class' => 'btn btn-sm btn-success',
                    'href' => $this->getUrl('admin.rewrites') . '?action=translations&rewrite_id=' . $object->getRewrite()->id,
                    'title' => $this->getUtils()->translate('Translations', locale: $this->getCurrentLocale()),
                ],
                'text' => $this->getHtmlRenderer()->getIcon('tag'),
            ]]);

            return (string)$button;
        } catch (BasicException $e) {
        }

        return '';
    }

    protected function getModelRowButtons(BaseModel $object) : array
    {
        return [
            static::FRONTEND_BTN => $this->getFrontendModelButton($object),
            static::TRANSLATIONS_BTN => $this->getTranslationsButton($object),
        ] + parent::getModelRowButtons($object);
    }
}
