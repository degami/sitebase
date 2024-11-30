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

namespace App\Base\Abstracts\Commands;

use App\Site\Models\Website;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Symfony\Component\Console\Command\Command as SymfonyCommand;
use Psr\Container\ContainerInterface;
use Dotenv\Dotenv;
use App\Base\Traits\ContainerAwareTrait;
use App\App;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Helper\TableCell;
use Symfony\Component\Console\Helper\TableSeparator;

/**
 * Base for cli commands
 */
class BaseCommand extends SymfonyCommand
{
    use ContainerAwareTrait;

    /** @var InputInterface */
    protected $input = null;

    /** @var OutputInterface */
    protected $output = null;

    /** @var SymfonyStyle */
    protected $io = null;

    /**
     * BaseCommand constructor.
     *
     * @param null $name
     * @param ContainerInterface|null $container
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function __construct(
        protected ContainerInterface $container,
        $name = null
    ) {
        parent::__construct($name);
        $this->bootstrap();
    }

    /**
     * boostrap command
     *
     * @return void
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    protected function bootstrap()
    {
        // load environment variables
        $dotenv = Dotenv::create(App::getDir(App::ROOT));
        $dotenv->load();

        $this->getContainer()->set(
            'env',
            array_combine(
                $dotenv->getEnvironmentVariableNames(),
                array_map(
                    'getenv',
                    $dotenv->getEnvironmentVariableNames()
                )
            )
        );

        $this->getContainer()->set(RouteInfo::class, $this->getUtils()->getEmptyRouteInfo());

        if (!$this->getTemplates()->getFolders()->exists('base')) {
            $this->getTemplates()->addFolder('base', App::getDir(App::TEMPLATES));
        }
        if (!$this->getTemplates()->getFolders()->exists('errors')) {
            $this->getTemplates()->addFolder('errors', App::getDir(App::TEMPLATES) . DS . 'errors');
        }
        if (!$this->getTemplates()->getFolders()->exists('mails')) {
            $this->getTemplates()->addFolder('mails', App::getDir(App::TEMPLATES) . DS . 'mails');
        }
    }

    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->output = $output;
        $this->input = $input;

        /** @var Website $website */
        $website = null;

        $existing = false;
        try {
            $existing = $this->getPdo()->query("SELECT 1 FROM website") !== false;
        } catch (\Exception $exception) {
        }

        if ($existing) {
            try {
                if ($this->input->hasOption('website') && is_numeric($this->input->getOption('website'))) {
                    $website = $this->containerCall([Website::class, 'load'], ['id' => $this->input->getOption('website')]);
                }
            } catch (\Exception $e) {
            }

            if (!$website || !$website->isloaded()) {
                // $website = $this->getContainer()->call([Website::class, 'load'], ['id' => 1]);
                $website = Website::getCollection()->where([], ['created_at' => 'ASC'])->getFirst();
            }
        }

        $this->getContainer()->set(Website::class, $website);

        parent::initialize($input, $output);
    }

    /**
     * @return SymfonyStyle|null
     */
    protected function getIo(): ?SymfonyStyle
    {
        if ($this->input == null || $this->output == null) {
            return null;
        }

        if ($this->io == null) {
            $this->io = new SymfonyStyle($this->input, $this->output);
        }

        return $this->io;
    }

    /**
     * @return QuestionHelper
     */
    protected function getQuestionHelper(): QuestionHelper
    {
        return $this->getHelper('question');
    }

    /**
     * @param string $question_message
     * @return mixed
     */
    protected function keepAsking(string $question_message)
    {
        $value = "";
        while (trim((string)$value) == '') {
            $question = new Question($question_message);
            $value = $this->getQuestionHelper()->ask($this->input, $this->output, $question);
        }

        return $value;
    }

    /**
     * @param string $option_name
     * @param string $question_message
     * @param array|null $choices
     * @return mixed
     */
    protected function keepAskingForOption(string $option_name, string $question_message, array $choices = null): mixed
    {
        if ($this->input == null || $this->output == null) {
            return null;
        }

        if (is_array($choices)) {
            $choices = array_filter($choices, function ($el) {
                return stripos(",", $el) === false;
            });

            if (empty($choices)) {
                $choices = null;
            }
        }

        $value = $this->input->getOption($option_name);
        while (trim((string) $value) == '') {
            $question = new Question($question_message);
            $value = $this->getQuestionHelper()->ask($this->input, $this->output, $question);

            if (is_array($choices) && !in_array($value, $choices)) {
                $this->getIo()->error("Can be one of: " . implode(",", $choices));
                $value = '';
            }
        }

        return $value;
    }

    /**
     * @param string $confirmation_message
     * @param $not_confirm_message
     * @return bool
     */
    protected function confirmMessage(string $confirmation_message, $not_confirm_message): bool
    {
        $question = new ConfirmationQuestion($confirmation_message, false);
        if (!$this->getQuestionHelper()->ask($this->input, $this->output, $question)) {
            $this->output->writeln('<info>' . $not_confirm_message . '</info>');
            return false;
        }

        return true;
    }

    /**
     * @param string $confirmation_message
     * @return bool
     */
    protected function confirmSave(string $confirmation_message): bool
    {
        if ($this->input->getOption('no-interaction')) {
            return true;
        }
        return $this->confirmMessage($confirmation_message, 'Not Saving');
    }

    /**
     * @param string $confirmation_message
     * @return bool
     */
    protected function confirmDelete(string $confirmation_message): bool
    {
        return $this->confirmMessage($confirmation_message, 'Not deleted');
    }

    protected function renderTable(array $header, array $rows, $compressed = false)
    {
        $table = new Table($this->output);

        if ($compressed == true) {
            $table->getStyle()->setVerticalBorderChar(' ');
        }

        if (!empty($header)) {
            $table
            ->setHeaders($header);
        }

        $rows = array_filter(array_values($rows), fn ($row) => (is_array($row) || ($row instanceof TableSeparator)));

        $maxCols = 0;
        if (!empty($rows)) {
            $maxCols = max(array_map(fn($row) => is_array($row) ? count($row) : 1, $rows));
        }
        if (!empty($header)) {
            $maxCols = count($header);
        }

        foreach ($rows as $indexRow => $row) {
            $tableRow = null;
            if (!$compressed && ($row instanceof TableSeparator)) {
                $tableRow = $row;
            } else if(is_array($row)) {
                $tableRow = [];
                $colspan = null;

                // avoid non numeric keys
                $row = array_values($row);
    
                if (count($row) < $maxCols) {
                    $colspan = ($maxCols - count($row)) + 1;
                }

                if (count($row) > $maxCols) {
                    $row = array_slice($row, 0, $maxCols);
                }
    
                foreach ($row as $indexCol => $column) {
                    $content = (is_array($column) && isset($column['content'])) ? $column['content'] : $column;
                    if (is_array($column) && isset($column['content'])) {
                        unset($column['content']);
                    }
 
                    if ($indexCol == count($row)-1 && !empty($colspan)) {
                        $tableRow[] = new TableCell(
                            $content,
                            ['colspan' => $colspan] + (is_array($column) ? $column : [])
                        );
                    } else {
                        $tableRow[] = new TableCell(
                            $content,
                            (is_array($column) ? $column : [])
                        );
                    }
                }
            }

            if (!is_null($tableRow)) {
                $table->addRow($tableRow);
            }

            if (!$compressed) {
                // array indexes are always numeric
                if (is_array($tableRow) && $indexRow < (count($rows) -1) && !($rows[$indexRow+1] instanceof TableSeparator)) {
                    $table->addRow(new TableSeparator());
                }
            }
        }

        $table->render();
    }

    protected function renderTitle($title)
    {
        $this->getIo()->block(implode("\n", [$title, str_repeat('=', strlen($title))]), null, 'fg=green;bg=default', '', false, true);
    }
}
