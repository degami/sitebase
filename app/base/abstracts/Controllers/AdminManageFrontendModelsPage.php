<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Base\Abstracts\Controllers;

use \Psr\Container\ContainerInterface;
use \Symfony\Component\HttpFoundation\Response;
use \App\Base\Abstracts\Models\FrontendModel;
use \App\Base\Traits\AdminFormTrait;
use \Degami\PHPFormsApi as FAPI;
use \Degami\Basics\Html\TagElement;
use \App\App;

/**
 * Base for admin page that manages a Frontend Model
 */
abstract class AdminManageFrontendModelsPage extends AdminManageModelsPage
{

    use AdminFormTrait;

    protected function getTranslationsButton($object)
    {
        if (!$object->getRewrite()) {
            return '';
        }
        $button = new TagElement(
            [
            'tag' => 'a',
            'attributes' => [
                'class' => 'btn btn-sm btn-success',
                'href' => $this->getUrl('admin.rewrites') .'?action=translations&rewrite_id='.$object->getRewrite()->id,
                'title' => $this->getUtils()->translate('Translations', $this->getCurrentLocale()),
            ],
            'text' => $this->getUtils()->getIcon('tag'),
            ]
        );

        return (string) $button;
    }
}
