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

use App\Base\Traits\AdminTrait;
use App\Base\Exceptions\PermissionDeniedException;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\HttpFoundation\Response;

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
     * @throws PermissionDeniedException
     * @throws BasicException
     */
    protected function beforeRender(): BasePage|Response
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
