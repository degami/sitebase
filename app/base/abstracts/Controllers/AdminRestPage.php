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

use Degami\Basics\Exceptions\BasicException;
use \Symfony\Component\HttpFoundation\Response;
use \App\Base\Traits\AdminTrait;
use \App\Base\Exceptions\PermissionDeniedException;

/**
 * Base for rest endpoints
 */
abstract class AdminRestPage extends BaseRestPage
{

    use AdminTrait;


    /**
     * before render hook
     *
     * @return Response|self
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender()
    {
        if (!$this->checkCredentials() || !$this->checkPermission($this->getAccessPermission())) {
            throw new PermissionDeniedException();
        }

        return parent::beforeRender();
    }

    /**
     * gets access permission name
     *
     * @return string
     */
    abstract protected function getAccessPermission(): string;
}
