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
        return true;
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
                App::getInstance()->getSiteData()->setConfigValue(AIManager::GEMINI_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(AIManager::CHATGPT_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(AIManager::CLAUDE_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(AIManager::MISTRAL_TOKEN_PATH, null);
            }

            $this->getIo()->success('Ai support has been disabled');
            return Command::SUCCESS;
        }

        if ($doEnable) {
            $aiType = $this->keepAsking('Which AI do you want to enable? ('.implode(', ', $this->getAI()->getAvailableAIs()).') ', $this->getAI()->getAvailableAIs());
            $apiTokenValue = $this->keepAsking($aiType . ' token value? ');

            switch ($aiType) {
                case 'googlegemini':
                    App::getInstance()->getSiteData()->setConfigValue(AIManager::GEMINI_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'chatgpt':
                    App::getInstance()->getSiteData()->setConfigValue(AIManager::CHATGPT_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'claude':
                    App::getInstance()->getSiteData()->setConfigValue(AIManager::CLAUDE_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'mistral':
                    App::getInstance()->getSiteData()->setConfigValue(AIManager::MISTRAL_TOKEN_PATH, $apiTokenValue);
                    break;
            }

            $this->getIo()->success('Ai support for '.$aiType.' enabled');
            return Command::SUCCESS;
        }

        if (!$isAiAvailable) {
            $this->getIo()->error('Ai support is disabled');
            return Command::SUCCESS;
        }

        $availableAIs = array_filter($this->getAi()->getAvailableAIs(), fn($el) => App::getInstance()->getAi()->isAiAvailable($el));

        if (count($availableAIs) > 1) {
            $aiType = $this->keepAsking('Which AI do you want to use? (' . implode(', ', $availableAIs) . ') ', $availableAIs);
        } else {
            $aiType = reset($availableAIs);
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
                        switch ($aiType) {
                            case 'googlegemini':
                                $this->getIo()->write("\n---\n" . $this->getAi()->askGoogleGemini($prompt) . "\n---\n");
                                break;
                            case 'chatgpt':
                                $this->getIo()->write("\n---\n" . $this->getAi()->askChatGPT($prompt) . "\n---\n");
                                break;
                            case 'claude':
                                $this->getIo()->write("\n---\n" . $this->getAi()->askClaude($prompt) . "\n---\n");
                                break;
                            case 'mistral':
                                $this->getIo()->write("\n---\n" . $this->getAi()->askMistral($prompt) . "\n---\n");
                                break;
                        }
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
