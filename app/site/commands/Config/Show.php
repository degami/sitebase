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
namespace App\Site\Commands\Config;

use \App\Base\Abstracts\Command;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\Table;
use \Psr\Container\ContainerInterface;

/**
 * Show Config Command
 */
class Show extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Show Config')
        ->setDefinition(
            new InputDefinition([
                new InputOption('website', null, InputOption::VALUE_OPTIONAL),
            ])
        );
    }

    /**
     * {@inheritdocs}
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);
        $table
            ->setHeaders(['Id', 'Website', 'Path', 'Value','System']);

        $website = $input->getOption('website');
        $query = $this->getDb()->table('configuration');
        if (is_numeric($website)) {
            $query = $query->where(['website_id' => $website]);
        }

        $results = $query->fetchAll();
        foreach ($results as $row) {
            $table->addRow([$row['id'], $row['website_id'], $row['path'], $row['value'], $row['is_system'] ? 'true' : 'false']);
        }

        $table->render();
    }
}
