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
use Exception;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputOption;

/**
 * Ai Command
 */
class Ai extends BaseCommand
{
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

        $isAiAvailable = App::getInstance()->getSiteData()->isAiAvailable();

        if ($doDisable) {
            if ($isAiAvailable) {
                App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\GoogleGemini::GEMINI_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\ChatGPT::CHATGPT_TOKEN_PATH, null);
            }

            $this->getIo()->error('Ai support has been disabled');
            return Command::SUCCESS;
        }

        if ($doEnable) {
            $aiType = $this->keepAsking('Which AI do you want to enable? (googlegemini, chatgpt) ', ['googlegemini', 'chatgpt']);
            $apiTokenValue = $this->keepAsking($aiType . ' token value? ');

            switch ($aiType) {
                case 'googlegemini':
                    App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\GoogleGemini::GEMINI_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'chatgpt':
                    App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\ChatGPT::CHATGPT_TOKEN_PATH, $apiTokenValue);
                    break;
            }

            $this->getIo()->error('Ai support for '.$aiType.' enabled');
            return Command::SUCCESS;
        }

        if (!$isAiAvailable) {
            $this->getIo()->error('Ai support is disabled');
            return Command::SUCCESS;
        }


        $availableAIs = array_filter(['googlegemini', 'chatgpt'], fn($el) => App::getInstance()->getSiteData()->isAiAvailable($el));

        if (count($availableAIs) > 1) {
            $aiType = $this->keepAsking('Which AI do you want to use? (googlegemini, chatgpt) ', $availableAIs);
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
                default:
                    try {
                        switch ($aiType) {
                            case 'googlegemini':
                                $this->getIo()->write("\n---\n" .$this->askGoogleGemini($prompt) . "\n---\n");
                                break;
                            case 'chatgpt':
                                $this->getIo()->write("\n---\n" . $this->askChatGPT($prompt) . "\n---\n");
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

    protected function askChatGPT(string $prompt) : string
    {
        $client = App::getInstance()->getGuzzle();

        $maxTokens = \App\Base\Controllers\Admin\Json\ChatGPT::CHATGPT_MAX_TOKENS;

        $apiKey = App::getInstance()->getSiteData()->getConfigValue(\App\Base\Controllers\Admin\Json\ChatGPT::CHATGPT_TOKEN_PATH);
        $endPoint = "https://api.openai.com/v1/engines/gpt-3.5-turbo/completions";

        $response = $client->post($endPoint, [
            'headers' => [
                'Authorization' => "Bearer ".$apiKey,
            ],
            'json' => [
                'prompt' => $prompt,
                'max_tokens' => $maxTokens, // Adjust the max tokens as needed
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['choices'][0]['text'];

        return trim($generatedText);
    }

    protected function askGoogleGemini(string $prompt) : string
    {
        $client = App::getInstance()->getGuzzle();
        $apiKey = App::getInstance()->getSiteData()->getConfigValue(\App\Base\Controllers\Admin\Json\GoogleGemini::GEMINI_TOKEN_PATH);
        $endPoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$apiKey}";

        $response = $client->post($endPoint, [
            'headers' => [
                'Content-Type' => "application/json",
            ],
            'json' => [
                'contents' => [
                    [
                        'role' => 'user',
                        'parts' => [
                            [
                                'text' => $prompt,
                            ]
                        ]
                    ]
                ],
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];

        return trim($generatedText);
    }
}
