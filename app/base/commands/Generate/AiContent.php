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

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\Abstracts\Models\FrontendModel;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use HaydenPierce\ClassFinder\ClassFinder;
use Degami\SqlSchema\Column;

/**
 * Generate Contents with AI Command
 */
class AiContent extends BaseCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setDescription('Generate content using AI')
        ->addOption('contenttype', 't', InputOption::VALUE_OPTIONAL, 'Content Type')
        ->addOption('locale', 'l', InputOption::VALUE_OPTIONAL, 'Locale');
    }

    /**
     * defines if the command is enabled
     * 
     * @return bool
     */
    public static function registerCommand(): bool
    {
        return App::getInstance()->getAI()->isAiAvailable();
    }

    /**
     * {@inheritdoc}
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output) : int
    {
        $contentTypes = array_filter(array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE)
        ), function ($class) {
            return is_subclass_of($class, FrontendModel::class) && !(new \ReflectionClass($class))->isAbstract();
        });

        $contentTypes = array_combine(
            $contentTypes,
            array_map(fn($el) => strtolower(basename(str_replace("\\", '/', $el))), $contentTypes)
        );

        $contentType = $this->selectElementFromList(array_values($contentTypes), 'Choose content type'); // $this->keepAskingForOption('contenttype', 'Content Type ('.implode(', ', array_values($contentTypes)).')? ', array_values($contentTypes));

        $class = array_search($contentType, $contentTypes);

        if (is_array($class) || $class === false) {
            $output->writeln('<error>Invalid content type</error>');
            return Command::FAILURE;
        }

        $table = $this->containerCall([$class, 'defaultTableName']);
        $tableInfo = $this->getSchema()->getTable($table);
        $primaryKey = $this->containerCall([$class, 'getKeyField']);
        $fields = array_filter($tableInfo->getColumns(), function(Column $col) use ($primaryKey) {
            $skipCols = ['id', 'website_id', 'user_id', 'url', 'url_key', 'created_at', 'updated_at'];
            if (is_array($primaryKey)) {
                $skipCols = array_merge($skipCols, $primaryKey);
            } else {
                $skipCols[] = $primaryKey;
            }
            return !$col->isAutoIncrement() && !in_array($col->getName(), $skipCols);
        });

        $fieldDefinition = array_map(function($field) {
            return [
                'name' => $field->getName(), 
                'type' => $field->getType(), 
                'parameters' => $field->getParameters(),
                'options' => $field->getOptions(),
                'render' => $field->render()
            ];
        }, $fields);

        $fieldDefinition = implode(', ', array_map(function($item) {
            $length = null;
            foreach ((array) $item['parameters'] as $param) {
                if (is_numeric($param)) {
                    $length = $param;
                }
            }
            return '"'.$item['name'].'" type: "'.$item['type'].'"' . ($length ? ' max '.$length : '');
        }, $fieldDefinition));


        $availableLocales = $this->getSiteData()->getSiteLocales();
        $locale = $this->selectElementFromList($availableLocales, 'Select locale'); // $this->keepAskingForOption('locale', "Locale (".implode(', ', $availableLocales).")", $availableLocales);

        $availableAIs = $this->getAI()->getEnabledAIs();

        if (count($availableAIs) > 1) {
            $aiType = $this->selectElementFromList($availableAIs, 'Select which AI'); // $this->keepAsking('Which AI do you want to use? (' . implode(', ', $availableAIs) . ') ', $availableAIs);
        } else {
            $aiType = reset($availableAIs);
        }

        $selectModel = $this->keepAsking('Do you want to select a specific model? (y/n) ', ['y', 'n']) == 'y';
        $model = null;
        if ($selectModel) {
            $models = $this->getAi()->getAIModel($aiType)?->getAvailableModels(true) ?? [];
            if (count($models) > 0) {
                $model = $this->selectElementFromList($models, 'Choose model');
            }
        }

        $subject = $this->keepAsking("write your subject:");

        $promptText = $this->getUtils()->translate("Generate a json with data, no comments for a single model of type %s containing the following fields ando %s using language \"%s\" (the json structure must be a single object containing only the specified fields) for the subject: \\n%s" , [
            $contentType,
            $fieldDefinition,
            $locale,
            $subject,
        ]);

        $response = $this->getAi()->askAI($aiType, $promptText, $model);

        if (preg_match('/```json(.*?)```/is', $response, $matches)) {
            $json = $matches[1];
        } else {
            $json = $response;
        };
        $json = preg_replace('/```(json)?/i', '', $json);
        $json = trim($json);

        $data = json_decode($json, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            $output->writeln('<error>Invalid json response from AI: ' . json_last_error_msg() . '</error>');
            $output->writeln('Full response: ' . $response);
            return Command::FAILURE;
        }

        $data['locale'] = $locale;
        $data['website_id'] = $this->getAppWebsite()?->getId();
        $data['user_id'] = $this->getAuth()->getCurrentUser()?->getId() ?? $this->getSiteData()->getDefaultAdminUser()?->getId() ?: 1;

        $object = $this->containerCall([$class, 'new'], ['initial_data' => $data]);

        print_r($object->getData());
        $save = $this->confirmSave('Do you want to save this content?');
        if ($save) {
            $object->persist();
            $output->writeln('<info>Content saved!</info>');
        }

        return Command::SUCCESS;
    }
}