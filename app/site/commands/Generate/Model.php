<?php
/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */
namespace App\Site\Commands\Generate;

use \App\Base\Abstracts\Commands\CodeGeneratorCommand;
use \Symfony\Component\Console\Input\InputInterface;
use \Symfony\Component\Console\Input\InputDefinition;
use \Symfony\Component\Console\Input\InputOption;
use \Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\Question;
use \Symfony\Component\Console\Helper\Table;
use \Psr\Container\ContainerInterface;
use \App\App;

/**
 * Generate Model Command
 */
class Model extends CodeGeneratorCommand
{
    const BASE_MODEL_NAMESPACE = "App\\Site\\Models\\";
    const BASE_MIGRATION_NAMESPACE = "App\\Site\\Migrations\\";

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
     * {@inheritdocs}
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $helper = $this->getHelper('question');

        $modelClassName = $input->getOption('classname');
        while (trim($modelClassName) == '') {
            $question = new Question('Class Name (starting from '.static::BASE_MODEL_NAMESPACE.')? ');
            $modelClassName = $helper->ask($input, $output, $question);
        }

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
            $column_info = $this->askColumnInfo($input, $output);
            $this->columns[$column_info['col_name']] = $column_info;

            $question = new ConfirmationQuestion('Add another field? ', false);
        } while ($helper->ask($input, $output, $question) == true);

        $migrationClassName = 'Create'.$modelClassName.'TableMigration';

        $this->addClass(static::BASE_MODEL_NAMESPACE.$modelClassName, $this->getModelFileContents($modelClassName));
        $this->addClass(static::BASE_MIGRATION_NAMESPACE.$migrationClassName, $this->getMigrationFileContents($migrationClassName, $modelClassName, $migration_order));

        $question = new ConfirmationQuestion('Save File(s) in '.implode(", ", array_keys($this->filesToDump)).'? ', false);
        if (!$helper->ask($input, $output, $question)) {
            $output->writeln('<info>Not Saving</info>');
            return;
        }

        list($files_written, $errors) = array_values($this->doWrite());
        if (!empty($errors)) {
            foreach ($errors as $error) {
                $output->writeln("<error>\n\n ".$error."\n</error>");
            }
        } else {
            $output->writeln('<info>File(s) saved</info>');
        }
    }

    /**
     * ask colum informations
     *
     * @param  InputInterface  $input
     * @param  OutputInterface $output
     * @return array
     */
    protected function askColumnInfo(InputInterface $input, OutputInterface $output)
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

        $output->writeln('<info>Add a new column</info>');

        while (trim($column_info['col_name']) == '') {
            $question = new Question('Column name: ');
            $column_info['col_name'] = trim($helper->ask($input, $output, $question));
        }

        $types = [
            'TINYINT' => 'integer',
            'INT' => 'integer',
            'BIGINT' => 'integer',
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
        $column_type = $helper->ask($input, $output, $question);

        $column_info['mysql_type'] = $column_type;
        $column_info['php_type'] = $types[$column_type];

        switch ($column_type) {
            case 'VARCHAR':
            case 'DECIMAL':
                $parameters = '';
                while (trim($parameters) == '') {
                    $question = new Question('Column parameters: ');
                    $parameters = $helper->ask($input, $output, $question);
                    if (!is_numeric($parameters) && !empty($paramenters)) {
                        $parameters = "'".$parameters."'";
                    }
                }
                $column_info['col_parameters'] = [$parameters];

                break;
        }

        $question = new Question('Column options (comma separated): ');
        $options = array_map("strtoupper", array_filter(array_map("trim", explode(",", $helper->ask($input, $output, $question)))));
        foreach ($options as $k => $option) {
            if (!is_numeric($option) && !empty($option) && $option != 'NULL') {
                $options[$k] = "'".$option."'";
            }
        }
        $column_info['col_options'] = $options;

        $question = new ConfirmationQuestion('Column is nullable? ', true);
        $column_info['nullable'] = $helper->ask($input, $output, $question);

        $question = new Question('Default value (defaults to null)? ', null);
        $column_info['default_value'] = $helper->ask($input, $output, $question);

        return $column_info;
    }

    /**
     * gets model file contents
     *
     * @param  string $className
     * @return string
     */
    protected function getModelFileContents($className)
    {
        $comment = '';
        foreach ($this->columns as $name => $column_info) {
            $comment .= " * @method ".$column_info['php_type']. " get".$this->getUtils()->snakeCaseToPascalCase($name)."()\n";
        }
        $comment .= " * @method \\DateTime getCreatedAt()\n";
        $comment .= " * @method \\DateTime getUpdatedAt()\n";

        return "<?php

namespace App\\Site\\Models;

use \\App\\Base\\Abstracts\\Model;

/**\n".$comment." */
class ".$className." extends BaseModel
{
}
";
    }

    /**
     * gets migration file contents
     *
     * @param  string  $className
     * @param  string  $modelClassName
     * @param  integer $migration_order
     * @return string
     */
    protected function getMigrationFileContents($className, $modelClassName, $migration_order = 100)
    {
        $colums = '';
        foreach ($this->columns as $name => $column_info) {
            $colums .= "             ->addColumn(".
                        "'".$name."', ".
                        "'".$column_info['mysql_type']. "', ".
                        ((!empty($column_info['col_parameters'])) ? '['.implode(',', $column_info['col_parameters']).']':'null').", ".
                        "[".implode(',', $column_info['col_options'])."], ".
                        ((boolval($column_info['nullable'])) ? 'true':'false').", ".
                        (!empty($column_info['default_value']) ? "'".$column_info['default_value']."'": "null").
                        ")\n";
        }

        $migration_table = $this->getUtils()->pascalCaseToSnakeCase($modelClassName);

        return "<?php

namespace App\\Site\\Migrations;

use \\App\\Base\Abstracts\\DBMigration;
use \\Psr\\Container\\ContainerInterface;
use \\Degami\\SqlSchema\\Index;
use \\Degami\\SqlSchema\\Table;

class ".$className." extends DBMigration
{
    protected \$tableName = '".$migration_table."';

    public function getName()
    {
        return '".$migration_order."_'.parent::getName();
    }

    public function addDBTableDefinition(Table \$table)
    {
        \$table->addColumn('id', 'INT', null, ['UNSIGNED'])
            ".trim($colums)."
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
