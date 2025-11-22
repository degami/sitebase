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

namespace App\Base\Commands\Media;

use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Abstracts\Models\BaseCollection;
use App\BAse\Controllers\Admin\Cms\Media;
use App\Base\Models\MediaElement;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;

/**
 * Generate Thumbnails Command
 */
class GenerateThumbs extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate missing Media Thumbnails')
        ->addOption(
            'size',
            's',
            InputOption::VALUE_OPTIONAL,
            'Thumbnail size (e.g. 100x100) or "original" for original size'
        );
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
        $thumbSize = $this->keepAskingForOption('size', 'Enter thumbnail size (e.g. 100x100) or "original" for original size: ');

        if (!preg_match("/^([0-9]+)x([0-9]+)$/i", $thumbSize) && $thumbSize !== 'original') {
            $this->getIo()->error('Invalid size format. Use WIDTHxHEIGHT or "original"');
            return Command::FAILURE;
        }

        /** @var BaseCollection $medias */
        MediaElement::getCollection()->map(function($item) use ($thumbSize) {
            /** @var MediaElement $item */
            if ($thumbSize === 'original') {
                $thumbSize = MediaElement::ORIGINAL_SIZE;
            }
            return $item->isImage() ? $item->getThumb($thumbSize, null, null, ['for_admin' => '']) : '';
        });

        return Command::SUCCESS;
    }
}
