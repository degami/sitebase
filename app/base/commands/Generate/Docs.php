<?php

/**
 * SiteBase
 * PHP Version 8.5
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Commands\Generate;

use App\Base\Abstracts\Commands\BaseExecCommand;
use App\Base\Exceptions\NotFoundException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use App\App;
use Symfony\Component\Console\Command\Command;

/**
 * Generate Documentation Command
 */
class Docs extends BaseExecCommand
{
    public const BASE_NAMESPACE = "App\\Site\\Cron\\Tasks\\";

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setDescription('Generate documentation with phpdoc');
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     * @throws NotFoundException
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $output->writeln("<info>Generating Documentation</info>");

        if (!$this->commandExist('phpdoc')) {
            throw new NotFoundException('phpdoc command is missing!');
        }

        $docsDir = App::getDir(App::ROOT) . DS . "docs";
        $webDocsLink = App::getDir(App::WEBROOT) . DS . "docs";

        $commandline = "phpdoc -t " . $docsDir . " -d " . App::getDir(App::APP) . " --sourcecode --ignore=vendor/* --template=default --setting=\"graphs.enabled=false\" --force >/dev/null 2>&1";
        $this->executeCommand($commandline);

        if (!file_exists($webDocsLink) && !is_link($webDocsLink)) {
            $question = new ConfirmationQuestion('Do you want to publish docs also on website? ', false);
            if (!$this->getQuestionHelper()->ask($input, $output, $question)) {
                $output->writeln('<info>Not Publishing</info>');
                return Command::SUCCESS;
            }

            symlink($docsDir, $webDocsLink);
        }

        $website = $this->getSiteData()->getCurrentWebsite();
        $host = $website->getDomain();
        $this->rewriteLinksToAbsolute($docsDir, $host, "/docs");

        $this->getIo()->success('Task completed');

        return Command::SUCCESS;
    }

    private function rewriteLinksToAbsolute(string $docsDir, string $host, string $basePath = '/'): void
    {
        $normalizedHost = rtrim(trim($host), '/');
        if (!preg_match('#^(?:https?:)?//#i', $normalizedHost)) {
            $normalizedHost = '//' . $normalizedHost;
        }

        $normalizedBase = trim(str_replace('\\', '/', $basePath), '/');
        $basePrefix = $normalizedBase !== '' ? '/' . $normalizedBase : '';

        $realDocsDir = realpath($docsDir);
        if ($realDocsDir === false) {
            return;
        }

        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($realDocsDir, \FilesystemIterator::SKIP_DOTS)
        );

        foreach ($iterator as $file) {
            /** @var \SplFileInfo $file */
            if (!$file->isFile() || strtolower($file->getExtension()) !== 'html') {
                continue;
            }

            $content = file_get_contents($file->getPathname());
            if ($content === false) {
                continue;
            }

            $updated = preg_replace_callback(
                '/\b(href|src)=([\'"])(?!https?:\/\/|\/\/|#|mailto:|data:)([^\'"#?]+)([#?][^\'"]*)?\2/i',
                function (array $m) use ($normalizedHost, $basePrefix): string {
                    $attr = $m[1];
                    $quote = $m[2];
                    $target = $m[3];
                    $suffix = $m[4] ?? '';

                    if (str_starts_with($target, '/')) {
                        $resolvedPath = $this->resolveRelativePath($target);
                    } else {
                        $resolvedPath = $this->resolveRelativePath($basePrefix . '/' . $target);
                    }

                    return $attr . '=' . $quote . $normalizedHost . $resolvedPath . $suffix . $quote;
                },
                $content
            );

            if ($updated !== null && $updated !== $content) {
                file_put_contents($file->getPathname(), $updated);
            }
        }
    }

    private function resolveRelativePath(string $path): string
    {
        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                array_pop($segments);
                continue;
            }
            $segments[] = $segment;
        }

        return '/' . implode('/', $segments);
    }
}