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
 * Compare two versions of a model
 */
class Compare extends BaseCommand
{
    protected function configure(): void
    {
        $this
            ->setDescription('Compare two model versions')
            ->addArgument('id1', InputArgument::REQUIRED, 'First version ID')
            ->addArgument('id2', InputArgument::REQUIRED, 'Second version ID');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $id1 = (int) $input->getArgument('id1');
        $id2 = (int) $input->getArgument('id2');

        /** @var ModelVersion $v1 */
        $v1 = ModelVersion::load($id1);

        /** @var ModelVersion $v2 */
        $v2 = ModelVersion::load($id2);

        if (!$v1 || !$v2) {
            $output->writeln('<error>Missing version object(s)</error>');
            return self::FAILURE;
        }

        $diff = $v1->compareWith($v2);

        $rows = $this->flattenDiffForTable($diff);

        $this->renderTitle("Compare Version $id1 vs $id2");
        $this->renderTable(
            [
                'Field', 
                "Version $id1 " . $v1->getCreatedAt() . ' - ' . ($v1->getOwner()?->getUsername() ?? 'System'), 
                "Version $id2 " . $v2->getCreatedAt() . ' - ' . ($v2->getOwner()?->getUsername() ?? 'System')
            ],
            $rows
        );

        return self::SUCCESS;
    }

    private function flattenDiffForTable($diff, string $prefix = ''): array
    {
        $rows = [];
        if (!is_array($diff)) {
            return $rows;
        }
        foreach ($diff as $key => $value) {
            if (is_null($value)) {
                continue;
            }

            $field = $prefix . $key;

            if (array_key_exists('current', $value) && array_key_exists('other', $value) && $value['current'] != $value['other']) {
                $current = $this->formatValue($value['current']);
                $other   = $this->formatValue($value['other']);

                if ($value['current'] == $value['other']) {
                    continue;
                }

                $rows[] = [
                    $this->wrapCliText($field),
                    $this->wrapCliText($current),
                    $this->wrapCliText($other),
                ];
                
            } else {
                $rows = array_merge($rows, $this->flattenDiffForTable($value, $field . '.'));
            }
        }
        return $rows;
    }

    private function formatValue($value): string
    {
        if (is_array($value) && isset($value['__class'], $value['__primaryKey'])) {
            return $value['__class'] . '#' . $value['__primaryKey'];
        }

        if (is_array($value) || is_object($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        return (string) $value;
    }

    private function wrapCliText(string $text, int $width = 50): string
    {
        if (mb_strlen($text) <= $width) {
            return $text;
        }

        $wrapped = wordwrap($text, $width, "\n", true);

        if (str_contains($text, '<fg=red>')) {
            $wrapped = "<fg=red>$wrapped</>";
        } elseif (str_contains($text, '<fg=green>')) {
            $wrapped = "<fg=green>$wrapped</>";
        }

        return $wrapped;
    }
}
