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

namespace App\Site\Commands\Queue;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\QueueMessage;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

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
        $this->setDescription('Queue Statistics');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $queueNames = $this->containerCall([QueueMessage::class, 'getQueueNames']);

        $tableContents = [];
        foreach ($queueNames as $queueName) {
            /** @var \App\Base\Abstracts\Models\BaseCollection $collection */
            $collection = $this->containerCall([QueueMessage::class, 'getCollection'])->addCondition(['queue_name' => $queueName]);
            $collectionProcessed = (clone $collection)->addCondition(['status' => QueueMessage::STATUS_PROCESSED]);
            $collectionPending = (clone $collection)->addCondition(['status' => QueueMessage::STATUS_PENDING]);
            $tableContents[] = ['<info>' . $queueName . '</info>', $collectionProcessed->count(), $collectionPending->count()];
        }

        $this->renderTitle('Queues stats');
        $this->renderTable([$this->getUtils()->translate('Queue name'), $this->getUtils()->translate('Processed'), $this->getUtils()->translate('Pending')], $tableContents);

        return Command::SUCCESS;
    }
}
