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
namespace App\Site\Cron\Tasks;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\ContainerAwareObject;
use \App\App;

/**
 * Cron HeartBeat
 */
class HeartBeat extends ContainerAwareObject
{
    const DEFAULT_SCHEDULE = '*/5 * * * *';
    
    /**
     * class constructor
     *
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container)
    {
        parent::__construct($container);
    }

    /**
     * pulse method
     *
     * @return string
     */
    public function pulse()
    {
        return 'beat';
    }
}
