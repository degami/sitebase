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

namespace App\Site\Commands\Media;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\MediaElement;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;

/**
 * Clear Thumbnails Command
 */
class ClearThumbs extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Clear All Media Thumbnails');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {

        $medias = MediaElement::getCollection();
        $confirm = $this->confirmDelete('Confirm thumbnail deletion for medias?');
        if ($confirm) {
            foreach($medias as $media) {
                /** @var MediaElement $media */
                $media->clearThumbs();
            }

            $this->getIo()->success('Thumbnails Cleared');
        }

        return Command::SUCCESS;
    }
}
