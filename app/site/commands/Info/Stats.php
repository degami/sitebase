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

namespace App\Site\Commands\Info;

use \App\Base\Abstracts\Commands\BaseCommand;
use Degami\Basics\Exceptions\BasicException;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Output\OutputInterface;
use \Symfony\Component\Console\Helper\Table;
use \Symfony\Component\Console\Helper\TableCell;
use \Symfony\Component\Console\Helper\TableSeparator;

/**
 * Information Statistics Command
 */
class Stats extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Show Statistics');
    }

    /**
     * {@inheritdocs}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $table = new Table($output);

        $tables = [
            'website' => 'Websites',
            'user' => 'Users',
            'page' => 'Pages',
            'contact' => 'Contact Forms',
            'contact_submission' => 'Contact Forms Submissions',
            'taxonomy' => 'Taxonomy Terms',
            'block' => 'Blocks',
            'media_element' => 'Media Elements',
            'request_log' => 'Request Logs',
            'mail_log' => 'Mail Logs',
            'link_exchange' => 'Link Exchange',
            'news' => 'News',
        ];

        foreach ($tables as $table_name => $label) {
            $table
                ->addRow(['<info>' . $label . '</info>', count($this->getDb()->table($table_name)->fetchAll())])
                ->addRow(new TableSeparator());
        }

        $table
            ->addRow(
                [new TableCell(
                    $this->getCache()->getStats()->getInfo() . "\n" .
                    "Cache size: " . $this->getCache()->getStats()->getSize() . "\n" .
                    "Cache Lifetime: " . $this->getCache()->getCacheLifetime(),
                    ['colspan' => 2]
                )]
            );

        $table->render();
    }
}
