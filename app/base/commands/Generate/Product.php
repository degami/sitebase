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
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\QuestionHelper;

/**
 * Generate Ecommerce Product Command
 */
class Product extends CodeGeneratorCommand
{
    public const BASE_MODEL_NAMESPACE = "App\\Site\\Models\\";
    public const BASE_MIGRATION_NAMESPACE = "App\\Site\\Migrations\\";

    /**
     * @var array columns
     */
    protected $columns = [
        'sku' => [
            'col_name' => 'sku',
            'php_type' => 'string',
            'mysql_type' => 'VARCHAR',
            'col_parameters' => [255],
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'title' => [
            'col_name' => 'title',
            'php_type' => 'string',
            'mysql_type' => 'VARCHAR',
            'col_parameters' => [255],
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'content' => [
            'col_name' => 'content',
            'php_type' => 'string',
            'mysql_type' => 'TEXT',
            'col_parameters' => null,
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'tax_class_id' => [
            'col_name' => 'tax_class_id',
            'php_type' => 'int',
            'mysql_type' => 'INT',
            'col_parameters' => null,
            'col_options' => ['\'UNSIGNED\''],
            'nullable' => true,
            'default_value' => null,
        ],
        'website_id' => [
            'col_name' => 'website_id',
            'php_type' => 'int',
            'mysql_type' => 'INT',
            'col_parameters' => null,
            'col_options' => ['\'UNSIGNED\''],
            'nullable' => true,
            'default_value' => null,
        ],
        'user_id' => [
            'col_name' => 'user_id',
            'php_type' => 'int',
            'mysql_type' => 'INT',
            'col_parameters' => null,
            'col_options' => ['\'UNSIGNED\''],
            'nullable' => true,
            'default_value' => null,
        ],
        'price' => [
            'col_name' => 'price',
            'php_type' => 'float',
            'mysql_type' => 'FLOAT',
            'col_parameters' => null,
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'url' => [
            'col_name' => 'url',
            'php_type' => 'string',
            'mysql_type' => 'VARCHAR',
            'col_parameters' => [255],
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'locale' => [
            'col_name' => 'locale',
            'php_type' => 'string',
            'mysql_type' => 'VARCHAR',
            'col_parameters' => [10],
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'meta_keywords' => [
            'col_name' => 'meta_keywords',
            'php_type' => 'string',
            'mysql_type' => 'VARCHAR',
            'col_parameters' => [1024],
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'meta_description' => [
            'col_name' => 'meta_description',
            'php_type' => 'string',
            'mysql_type' => 'VARCHAR',
            'col_parameters' => [1024],
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
        'html_title' => [
            'col_name' => 'html_title',
            'php_type' => 'string',
            'mysql_type' => 'VARCHAR',
            'col_parameters' => [1024],
            'col_options' => [],
            'nullable' => true,
            'default_value' => null,
        ],
    ];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate a new Ecommerce Product class')
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
            if ($column_info) {
                $this->columns[$column_info['col_name']] = $column_info;
            }

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
    protected function askColumnInfo(): ?array
    {
        /** @var QuestionHelper $helper */
        $helper = $this->getQuestionHelper();

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

        if (in_array($column_info['col_name'], ['id','created_at','updated_at'])) {
            $this->getIo()->error("Cannot overwrite column ".$column_info['col_name']);
            return null;
        }

        if (in_array($column_info['col_name'], array_keys($this->columns))) {
            $confirmation = $this->confirmMessage('Column '.$column_info['col_name'].' is already defined. Do you confirm overwrite existing definition?', 'Aborting');
            if (!$confirmation) {
                return null;
            }
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

        $column_type = $this->selectElementFromList(array_keys($types), 'Column Type? ');

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
        $options = array_map("strtoupper", array_filter(array_map("trim", explode(",", (string) $helper->ask($this->input, $this->output, $question)))));
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

use App\\Base\\Abstracts\\Models\\FrontendModel;
use App\Base\Interfaces\Model\ProductInterface;

/**\n" . $comment . " */
class " . $className . " extends FrontendModel implements ProductInterface
{
    public function isPhysical(): bool
    {
        return true;
    }

    public function getId(): int
    {
        return \$this->getData('id');
    }

    public function getPrice(): float
    {
        return \$this->getData('price') ?? 0.0;
    }

    public function getTaxClassId(): ?int
    {
        return \$this->getData('tax_class_id');
    }

    public function getName() : ?string
    {
        return \$this->getData('title');
    }

    public function getSku(): string
    {
        return \$this->getData('sku')?? '".$this->getUtils()->pascalCaseToSnakeCase($className)."_' . \$this->getId();
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getRewritePrefix(): string
    {
        return '".$this->getUtils()->pascalCaseToSnakeCase($className)."';
    }
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
    protected string \$tableName = '" . $migration_table . "';

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
            ->addForeignKey('fk_".$migration_table."_website_id', ['website_id'], 'website', ['id'])
            ->addForeignKey('fk_".$migration_table."_owner_id', ['user_id'], 'user', ['id'])
            ->addForeignKey('fk_".$migration_table."_language_locale', ['locale'], 'language', ['locale'])
            ->setAutoIncrementColumn('id');

        return \$table;
    }
}
";
    }
}
