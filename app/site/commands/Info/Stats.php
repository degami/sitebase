<?php

/**
 * SiteBase
 * PHP Version 8.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Site\Commands\Info;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\Block;
use App\Site\Models\Contact;
use App\Site\Models\ContactSubmission;
use App\Site\Models\LinkExchange;
use App\Site\Models\MailLog;
use App\Site\Models\MediaElement;
use App\Site\Models\RequestLog;
use App\Site\Models\User;
use App\Site\Models\Website;
use App\Site\Models\Page;
use App\Site\Models\News;
use App\Site\Models\Taxonomy;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

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
            Website::class => 'Websites',
            User::class => 'Users',
            Page::class => 'Pages',
            Contact::class => 'Contact Forms',
            ContactSubmission::class => 'Contact Forms Submissions',
            Taxonomy::class => 'Taxonomy Terms',
            Block::class => 'Blocks',
            MediaElement::class => 'Media Elements',
            RequestLog::class => 'Request Logs',
            MailLog::class => 'Mail Logs',
            LinkExchange::class => 'Link Exchange',
            News::class => 'News',
        ];

        foreach ($tables as $class_name => $label) {
            /** @var \App\Base\Abstracts\Models\BaseCollection $collection */
            $collection = $this->getContainer()->call([$class_name, 'getCollection']);
            $table
                ->addRow(['<info>' . $label . '</info>', $collection->count()])
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
