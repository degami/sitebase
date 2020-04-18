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

use \App\Base\Traits\AdminTrait;

/**
 * Base JSON page on admin
 */
abstract class AdminJsonPage extends BaseJsonPage
{
    use AdminTrait;

    /**
     * {@inheritdocs}
     *
     * @return Response|self
     */
    protected function beforeRender()
    {
        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            return $this->getUtils()->errorPage(403, $this->getRequest());
        }

        return parent::beforeRender();
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract protected function getAccessPermission();
}
