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

namespace App\Base\Commands\Generate;

use App\Base\Abstracts\Commands\CodeGeneratorCommand;
use Degami\Basics\Exceptions\BasicException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Command\Command;

/**
 * Generate Model Command
 */
class Model extends CodeGeneratorCommand
{
    public const BASE_MODEL_NAMESPACE = "App\\Site\\Models\\";
    public const BASE_MIGRATION_NAMESPACE = "App\\Site\\Migrations\\";

    /**
     * @var array columns
     */
    protected $columns = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate a new Model class')
            ->setDefinition(
                new InputDefinition(
                    [
                        new InputOption('classname', 'c', InputOption::VALUE_OPTIONAL),
                        new InputOption('migration_order', 'm', InputOption::VALUE_OPTIONAL),
                    ]
                )
            );
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
        $modelClassName = $this->keepAskingForOption('classname', 'Class Name (starting from ' . static::BASE_MODEL_NAMESPACE . ')? ');

        $migration_order = $input->getOption('migration_order');
        if (empty($migration_order)) {
            $last_migration_id = 0;

            $last_migration = $this->getDb()->table('migrations')->orderBy("id", "DESC")->limit(1)->fetch();
            if ($last_migration) {
                $last_migration_id = $last_migration->id;
            }

            $migration_order = 100 + $last_migration_id;
        }

        do {
            $column_info = $this->askColumnInfo();
            $this->columns[$column_info['col_name']] = $column_info;

            $question = new ConfirmationQuestion('Add another field? ', false);
        } while ($this->getQuestionHelper()->ask($input, $output, $question) == true);

        $migrationClassName = 'Create' . $modelClassName . 'TableMigration';

        $this->addClass(static::BASE_MODEL_NAMESPACE . $modelClassName, $this->getModelFileContents($modelClassName));
        $this->addClass(static::BASE_MIGRATION_NAMESPACE . $migrationClassName, $this->getMigrationFileContents($migrationClassName, $modelClassName, $migration_order));

        if (!$this->confirmSave('Save File(s) in ' . implode(", ", array_keys($this->filesToDump)) . '? ')) {
            return Command::SUCCESS;
        }

        list($files_written, $errors) = array_values($this->doWrite());
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $this->getIo()->error($error);
            }
        } else {
            $this->getIo()->success(count($files_written) . ' File(s) saved');
        }

        return Command::SUCCESS;
    }

    /**
     * ask column information
     *
     * @return array
     */
    protected function askColumnInfo(): array
    {
        $helper = $this->getHelper('question');

        $column_info = [
            'col_name' => '',
            'php_type' => '',
            'mysql_type' => '',
            'col_parameters' => null,
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ];

        $this->output->writeln('<info>Add a new column</info>');

        while (trim($column_info['col_name']) == '') {
            $question = new Question('Column name: ');
            $column_info['col_name'] = trim($helper->ask($this->input, $this->output, $question));
        }

        $types = [
            'TINYINT' => 'int',
            'INT' => 'int',
            'BIGINT' => 'int',
            'DECIMAL' => 'float',
            'FLOAT' => 'float',
            'DOUBLE' => 'float',
            'BOOLEAN' => 'boolean',
            'VARCHAR' => 'string',
            'BLOB' => 'string',
            'TEXT' => 'string',
            'ENUM' => 'string',
            'DATETIME' => '\\DateTime',
        ];

        $question = new ChoiceQuestion(
            'Column Type? ',
            array_keys($types),
            0
        );
        $question->setErrorMessage('Invalid type %s.');
        $column_type = $helper->ask($this->input, $this->output, $question);

        $column_info['mysql_type'] = $column_type;
        $column_info['php_type'] = $types[$column_type];

        switch ($column_type) {
            case 'VARCHAR':
            case 'DECIMAL':
                $parameters = '';
                while (trim($parameters) == '') {
                    $question = new Question('Column parameters: ');
                    $parameters = $helper->ask($this->input, $this->output, $question);
                    if (!is_numeric($parameters) && !empty($paramenters)) {
                        $parameters = "'" . $parameters . "'";
                    }
                }
                $column_info['col_parameters'] = [$parameters];

                break;
        }

        $question = new Question('Column options (comma separated): ');
        $options = array_map("strtoupper", array_filter(array_map("trim", explode(",", $helper->ask($this->input, $this->output, $question)))));
        foreach ($options as $k => $option) {
            if (!is_numeric($option) && !empty($option) && $option != 'NULL') {
                $options[$k] = "'" . $option . "'";
            }
        }
        $column_info['col_options'] = $options;

        $question = new ConfirmationQuestion('Column is nullable? ', true);
        $column_info['nullable'] = $helper->ask($this->input, $this->output, $question);

        $question = new Question('Default value (defaults to null)? ', null);
        $column_info['default_value'] = $helper->ask($this->input, $this->output, $question);

        return $column_info;
    }

    /**
     * gets model file contents
     *
     * @param string $className
     * @return string
     * @throws BasicException
     */
    protected function getModelFileContents($className): string
    {
        $comment = '';
        foreach ($this->columns as $name => $column_info) {
            $comment .= " * @method " . $column_info['php_type'] . " get" . $this->getUtils()->snakeCaseToPascalCase($name) . "()\n";
        }
        $comment .= " * @method \\DateTime getCreatedAt()\n";
        $comment .= " * @method \\DateTime getUpdatedAt()\n";

        foreach ($this->columns as $name => $column_info) {
            $comment .= " * @method self set" . $this->getUtils()->snakeCaseToPascalCase($name) . "(" . $column_info['php_type'] . " \${$name})\n";
        }
        $comment .= " * @method self setCreatedAt(\\DateTime \$created_at)\n";
        $comment .= " * @method self setUpdatedAt(\\DateTime \$updated_at)\n";

        return "<?php

namespace App\\Site\\Models;

use App\\Base\\Abstracts\\Models\\BaseModel;


/**\n" . $comment . " */
class " . $className . " extends BaseModel
{
}
";
    }

    /**
     * gets migration file contents
     *
     * @param string $className
     * @param string $modelClassName
     * @param int $migration_order
     * @return string
     * @throws BasicException
     */
    protected function getMigrationFileContents($className, $modelClassName, $migration_order = 100): string
    {
        $colums = '';
        foreach ($this->columns as $name => $column_info) {
            $colums .= "             ->addColumn(" .
                "'" . $name . "', " .
                "'" . $column_info['mysql_type'] . "', " .
                ((!empty($column_info['col_parameters'])) ? '[' . implode(',', $column_info['col_parameters']) . ']' : 'null') . ", " .
                "[" . implode(',', $column_info['col_options']) . "], " .
                ((boolval($column_info['nullable'])) ? 'true' : 'false') . ", " .
                (!empty($column_info['default_value']) ? "'" . $column_info['default_value'] . "'" : "null") .
                ")\n";
        }

        $migration_table = $this->getUtils()->pascalCaseToSnakeCase($modelClassName);

        return "<?php

namespace App\\Site\\Migrations;

use App\\Base\\Abstracts\\Migrations\\DBMigration;
use Psr\\Container\\ContainerInterface;
use Degami\\SqlSchema\\Index;
use Degami\\SqlSchema\\Table;

class " . $className . " extends DBMigration
{
    protected \$tableName = '" . $migration_table . "';

    public function getName(): string
    {
        return '" . $migration_order . "_'.parent::getName();
    }

    public function addDBTableDefinition(Table \$table): Table
    {
        \$table->addColumn('id', 'INT', null, ['UNSIGNED'])
            " . trim($colums) . "
            ->addColumn('created_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addColumn('updated_at', 'TIMESTAMP', null, [], false, 'CURRENT_TIMESTAMP()')
            ->addIndex(null, 'id', Index::TYPE_PRIMARY)
            ->setAutoIncrementColumn('id');

        return \$table;
    }
}
";
    }
}
