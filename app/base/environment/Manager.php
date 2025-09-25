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

namespace App\Base\Environment;

use Symfony\Component\HttpFoundation\Request;

/**
 * Environment Manager
 */
class Manager
{
    protected ?Request $request = null;

    /**
     * Check if the current environment is CLI
     * 
     * @return bool
     */
    public function isCli(): bool
    {
        return (php_sapi_name() === 'cli' || defined('STDIN'));
    }

    /**
     * Check if the current environment is CLI server
     * 
     * @return bool
     */
    public function isCliServer(): bool
    {
        return (php_sapi_name() === 'cli-server');
    }

    /**
     * Check if the current environment is web
     * 
     * @return bool
     */
    public function isWeb(): bool
    {
        return !$this->isCli() && !$this->isCliServer();
    }

    /**
     * Get the current request
     * 
     * @return Request|null
     */
    public function getRequest() : ?Request
    {
        if ($this->isCli()) {
            return null;
        }

        if ($this->request !== null) {
            return $this->request;
        }

        return $this->request = \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    }
}