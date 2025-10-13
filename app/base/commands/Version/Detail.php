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

namespace App\Base\Commands\Version;

use App\Base\Abstracts\Commands\BaseCommand;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use App\Base\Models\ModelVersion;

/**
 * Show details of a single model version
 */
class Detail extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setDescription('Show details of a specific model version')
            ->addArgument('id', InputArgument::REQUIRED, 'Version ID to display');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id = (int) $input->getArgument('id');

        /** @var ModelVersion|null $version */
        $version = ModelVersion::load($id);

        if (!$version) {
            $output->writeln("<error>ModelVersion #$id not found</error>");
            return self::FAILURE;
        }

        $this->renderTitle("Model Version #$id");

        $metadata = [
            ['Class Name', $version->getClassName()],
            ['Primary Key', $version->getPrimaryKey()],
            ['Created At', $version->getCreatedAt()],
            ['Updated At', $version->getUpdatedAt()],
            ['Created by', $version->getOwner()?->getUsername() ?? 'System'],
        ];

        $this->renderTable(['Field', 'Value'], $metadata);

        $data = json_decode($version->getVersionData(), true);

        if (!is_array($data)) {
            $output->writeln("<comment>No structured version data available.</comment>");
            return self::SUCCESS;
        }

        $rows = $this->flattenArrayForTable($data);
        $this->getIo()->writeln("");

        $this->renderTitle('Version Data');
        $this->renderTable(['Key', 'Value'], $rows);

        return self::SUCCESS;
    }

    private function flattenArrayForTable(array $data, string $prefix = ''): array
    {
        $rows = [];

        foreach ($data as $key => $value) {
            $field = $prefix . $key;

            if (is_array($value)) {
                $rows = array_merge($rows, $this->flattenArrayForTable($value, $field . '.'));
            } else {
                $text = is_bool($value)
                    ? ($value ? 'true' : 'false')
                    : (string) $value;

                $text = $this->wrapCliText($text);
                $rows[] = [$field, $text];
            }
        }

        return $rows;
    }

    private function wrapCliText(string $text, int $width = 80): string
    {
        if (mb_strlen($text) <= $width) {
            return $text;
        }
        return wordwrap($text, $width, "\n", true);
    }
}
