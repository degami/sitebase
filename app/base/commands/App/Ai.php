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

namespace App\Base\Commands\App;

use App\App;
use App\Base\Abstracts\Commands\BaseCommand;
use App\Base\AI\Manager as AIManager;
use App\Base\AI\Models\ChatGPT;
use App\Base\AI\Models\Claude;
use App\Base\AI\Models\GoogleGemini;
use App\Base\AI\Models\Mistral;
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Terminal;

/**
 * Ai Command
 */
class Ai extends BaseCommand
{
    protected array $interactions = [];

    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setDescription('AI integration')
            ->addOption('enable', null, InputOption::VALUE_NONE, 'Enable AI support')
            ->addOption('disable', null, InputOption::VALUE_NONE, 'Disable AI support');
    }

        /**
     * {@inheritdoc}
     *
     * @return true
     */
    public static function registerCommand(): bool
    {
        return true; // always enabled as this command is used to enable/disable AI support
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
        // Ottieni l'istanza dell'applicazione
        $application = $this->getApplication();

        if ($application === null) {
            $output->writeln('<error>Errors loading Application!</error>');
            return Command::FAILURE;
        }

        $doEnable = boolval($input->getOption('enable'));
        $doDisable = boolval($input->getOption('disable'));

        if ($doDisable && $doEnable) {
            $this->getIo()->error("Do you want to enable or disable AI Support?");
            return Command::FAILURE;
        }

        $isAiAvailable = App::getInstance()->getAI()->isAiAvailable();

        if ($doDisable) {
            if ($isAiAvailable) {
                App::getInstance()->getSiteData()->setConfigValue(GoogleGemini::GEMINI_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(ChatGPT::CHATGPT_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(Claude::CLAUDE_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(Mistral::MISTRAL_TOKEN_PATH, null);
            }

            $this->getIo()->success('Ai support has been disabled');
            return Command::SUCCESS;
        }

        if ($doEnable) {
            $aiType = $this->keepAsking('Which AI do you want to enable? ('.implode(', ', $this->getAI()->getAvailableAIs()).') ', $this->getAI()->getAvailableAIs());
            $apiTokenValue = $this->keepAsking($aiType . ' token value? ');

            switch ($aiType) {
                case 'googlegemini':
                    App::getInstance()->getSiteData()->setConfigValue(GoogleGemini::GEMINI_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'chatgpt':
                    App::getInstance()->getSiteData()->setConfigValue(ChatGPT::CHATGPT_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'claude':
                    App::getInstance()->getSiteData()->setConfigValue(Claude::CLAUDE_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'mistral':
                    App::getInstance()->getSiteData()->setConfigValue(Mistral::MISTRAL_TOKEN_PATH, $apiTokenValue);
                    break;
            }

            $selectModel = $this->keepAsking('Do you want to select a specific model? (y/n) ', ['y', 'n']) == 'y';
            if ($selectModel) {
                $models = $this->getAi()->getAIModel($aiType)?->getAvailableModels(true) ?? [];

                if (count($models) > 0) {
                    $model = $this->selectElementFromList($models, 'Choose model');
                    switch ($aiType) {
                        case 'googlegemini':
                            App::getInstance()->getSiteData()->setConfigValue(GoogleGemini::GEMINI_MODEL_PATH, $model);
                            break;
                        case 'chatgpt':
                            App::getInstance()->getSiteData()->setConfigValue(ChatGPT::CHATGPT_MODEL_PATH, $model);
                            break;
                        case 'claude':
                            App::getInstance()->getSiteData()->setConfigValue(Claude::CLAUDE_MODEL_PATH, $model);
                            break;
                        case 'mistral':
                            App::getInstance()->getSiteData()->setConfigValue(Mistral::MISTRAL_MODEL_PATH, $model);
                            break;
                    }
                }
            }

            $this->getIo()->success('Ai support for '.$aiType.' enabled');
            return Command::SUCCESS;
        }

        if (!$isAiAvailable) {
            $this->getIo()->error('Ai support is disabled');
            return Command::SUCCESS;
        }

        $availableAIs = $this->getAI()->getEnabledAIs();

        if (count($availableAIs) > 1) {
            $aiType = $this->keepAsking('Which AI do you want to use? (' . implode(', ', $availableAIs) . ') ', $availableAIs);
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

        do {
            $prompt = rtrim(trim($this->keepAsking("\nASK " . $aiType . "\n> ")), ';'); 
            switch ($prompt) {
                case 'quit':
                case 'exit':
                    $prompt = 'exit';
                    break;
                case 'history':
                    $terminalWidth = (new Terminal())->getWidth() ?: 80;
                    foreach ($this->getAi()->getHistory($aiType, $terminalWidth) as $historyEntry) {
                        $this->getIo()->write($historyEntry."\n---\n");                        
                    }
                    break;
                default:
                    try {
                        $this->getIo()->write("\n---\n" . $this->getAi()->askAI($aiType, $prompt, $model) . "\n---\n");
                    } catch (Exception $e) {
                        $this->getIo()->error($e->getMessage());
                    }
                    break;                
            }
        } while ($prompt != 'exit');
        $output->writeln('bye.');

        return Command::SUCCESS;
    }
}
