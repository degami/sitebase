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
namespace App\Base\Abstracts\Commands;

use \Symfony\Component\Console\Command\Command as SymfonyCommand;
use \Psr\Container\ContainerInterface;
use \Dotenv\Dotenv;
use \App\Base\Traits\ContainerAwareTrait;
use \App\App;

/**
 * Base for cli commands
 */
class BaseCommand extends SymfonyCommand
{
    use ContainerAwareTrait;

    /**
     * BaseCommand constructor.
     *
     * @param null $name
     * @param ContainerInterface|null $container
     */
    public function __construct($name = null, ContainerInterface $container = null)
    {
        parent::__construct($name);
        $this->container = $container;
        $this->bootstrap();
    }

    /**
     * boostrap command
     *
     * @return void
     */
    protected function bootstrap()
    {
        // load environment variables
        $dotenv = Dotenv::create(App::getDir(App::ROOT));
        $dotenv->load();

        $this->getContainer()->set(
            'env',
            array_combine(
                $dotenv->getEnvironmentVariableNames(),
                array_map(
                    'getenv',
                    $dotenv->getEnvironmentVariableNames()
                )
            )
        );
    }
}
