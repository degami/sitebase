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

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use App\Site\Models\MediaElement;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use InvalidArgumentException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use SplFileInfo;
use Symfony\Component\Console\Input\InputArgument;

class FetchFromUrl extends BaseCommand
{
    protected function configure()
    {
        $this->setDescription('Fetch Media Element from url')
            ->addArgument('url', InputArgument::REQUIRED, 'Url to fetch')
            ->addArgument('target', InputArgument::OPTIONAL, 'Target path');
    }

    /**
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws \Throwable
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $media_path = App::getDir(App::MEDIA);
        $url = $input->getArgument('url');
        $target_path = $input->getArgument('target');
        if (is_null($target_path)) {
            $parsed = parse_url($url);
            $file_name = basename($parsed['path']);
            $target_path = $media_path . DS . $file_name;
        } else {
            try {
                /** @var MediaElement $element */
                $element = MediaElement::loadBy('path', $target_path);
                if ($element->isDirectory()) {
                    $parsed = parse_url($url);
                    $file_name = basename($parsed['path']);
                    $target_path = $element->getPath() . DS . $file_name;
                } else {
                    $this->getIo()->error("$target_path is existing and is not a folder");
                    return Command::FAILURE;
                }
            } catch (Exception $e) {
                if (!($e instanceof \App\Base\Exceptions\NotFoundException)) {
                    throw $e;
                }
                // path is not existing
            }
        }
        if (!str_starts_with($target_path, $media_path)) {
            throw new Exception("Target path must be under ".$media_path);
        }

        $this->getIo()->info("Downloading $url to $target_path");
        $mediaElement = MediaElement::createFromUrl($url, $target_path);
        $mediaElement->persist();

        $this->getIo()->success("Media Element downloaded to ".$mediaElement->getPath());

        return Command::SUCCESS;
    }
}
