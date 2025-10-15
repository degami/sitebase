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

use App\App;
use Symfony\Component\HttpFoundation\Request;
use Dotenv\Dotenv;

/**
 * Environment Manager
 */
class Manager
{
    protected ?Request $request = null;

    protected array $envVariables = [];

    public function __construct()
    {
        // preload data
        $this->loadDotEnv()->getRequest();
    }

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
     * Check if the current environment in on docker
     */
    public function isDocker(): bool
    {
        if (getenv('IS_DOCKER') === '1') {
            return true;
        }

        if (file_exists('/.dockerenv')) {
            return true;
        }

        $cgroup = @file_get_contents('/proc/1/cgroup');
        return $cgroup !== false && (
            strpos($cgroup, 'docker') !== false ||
            strpos($cgroup, 'containerd') !== false
        );
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

    /**
     * gets env variable
     *
     * @param string $variable
     * @param mixed $default
     * @return mixed
     */
    public function getVariable(string $variable, mixed $default = null) : mixed
    {
        $env = $this->getVariables();
        return $env[$variable] ?? $default;
    }

    /**
     * puts a new variable into env
     * 
     * @param string $variable
     * @param mixed $value
     * @return self
     */
    public function putVariable(string $variable, mixed $value) : self
    {
        putenv($variable.'='.$value);
        $this->envVariables[$variable] = $value;

        return $this;
    }

    /**
     * get env variables
     * 
     * @return array
     */
    public function getVariables() : array
    {
        return (array) $this->envVariables;
    }

    /**
     * check if debug is active
     * 
     * @return bool
     */
    public function isDebugActive() : bool
    {
        return boolval($this->getVariable('DEBUG'));
    }

    /**
     * check if debug is active and ip address is valid
     * 
     * @return bool
     */
    public function canDebug() : bool
    {
        return $this->isDebugActive() && ($this->isDocker() || in_array($this->getRequest()?->getClientIp(), ['127.0.0.1', '::1', 'localhost']));
    }

    protected function loadDotEnv() : self
    {
        // load environment variables
        $dotenv = Dotenv::create(App::getDir(App::ROOT));
        $dotenv->load();

        if ($dotenv) {
                $this->envVariables = array_combine(
                $dotenv->getEnvironmentVariableNames(),
                array_map(
                    'getenv',
                    $dotenv->getEnvironmentVariableNames()
                )
            );    
        } else {
            $this->envVariables = $_ENV;
        }

        return $this;
    }
}