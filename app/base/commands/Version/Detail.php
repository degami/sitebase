<?php

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

        // Intestazione
        $this->renderTitle("Model Version #$id");

        // 🟢 Metadati principali
        $metadata = [
            ['Class Name', $version->getClassName()],
            ['Primary Key', $version->getPrimaryKey()],
            ['Created At', $version->getCreatedAt()],
            ['Updated At', $version->getUpdatedAt()],
        ];

        $this->renderTable(['Field', 'Value'], $metadata);

        // 🟢 Dati serializzati
        $data = json_decode($version->getVersionData(), true);

        if (!is_array($data)) {
            $output->writeln("<comment>No structured version data available.</comment>");
            return self::SUCCESS;
        }

        // Appiattisci i dati per tabella
        $rows = $this->flattenArrayForTable($data);

        $this->renderTitle('Version Data');
        $this->renderTable(['Key', 'Value'], $rows);

        return self::SUCCESS;
    }

    /**
     * Appiattisce un array multidimensionale per visualizzarlo in tabella
     */
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

                // 🧩 Evita overflow con wrapping
                $text = $this->wrapCliText($text);
                $rows[] = [$field, $text];
            }
        }

        return $rows;
    }

    /**
     * Wrappa testo lungo per non sfasciare la tabella
     */
    private function wrapCliText(string $text, int $width = 80): string
    {
        $plain = strip_tags(preg_replace('/<[^>]+>/', '', $text));
        if (mb_strlen($plain) <= $width) {
            return $text;
        }
        return wordwrap($plain, $width, "\n", true);
    }
}
