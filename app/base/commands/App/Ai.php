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

        $isAiAvailable = App::getInstance()->getSiteData()->isAiAvailable();

        if ($doDisable) {
            if ($isAiAvailable) {
                App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\GoogleGemini::GEMINI_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\ChatGPT::CHATGPT_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\Claude::CLAUDE_TOKEN_PATH, null);
                App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\Mistral::MISTRAL_TOKEN_PATH, null);
            }

            $this->getIo()->success('Ai support has been disabled');
            return Command::SUCCESS;
        }

        if ($doEnable) {
            $aiType = $this->keepAsking('Which AI do you want to enable? ('.implode(', ', $this->getSiteData()->getAvailableAIs()).') ', $this->getSiteData()->getAvailableAIs());
            $apiTokenValue = $this->keepAsking($aiType . ' token value? ');

            switch ($aiType) {
                case 'googlegemini':
                    App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\GoogleGemini::GEMINI_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'chatgpt':
                    App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\ChatGPT::CHATGPT_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'claude':
                    App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\Claude::CLAUDE_TOKEN_PATH, $apiTokenValue);
                    break;
                case 'mistral':
                    App::getInstance()->getSiteData()->setConfigValue(\App\Base\Controllers\Admin\Json\Mistral::MISTRAL_TOKEN_PATH, $apiTokenValue);
                    break;
            }

            $this->getIo()->success('Ai support for '.$aiType.' enabled');
            return Command::SUCCESS;
        }

        if (!$isAiAvailable) {
            $this->getIo()->error('Ai support is disabled');
            return Command::SUCCESS;
        }

        $availableAIs = array_filter($this->getSiteData()->getAvailableAIs(), fn($el) => App::getInstance()->getSiteData()->isAiAvailable($el));

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
                    foreach ($this->interactions as $interaction) {
                        $this->getIo()->write($this->parseInteraction($interaction, $aiType)."\n---\n");
                    } 
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
                            case 'claude':
                                $this->getIo()->write("\n---\n" . $this->askClaude($prompt) . "\n---\n");
                                break;
                            case 'mistral':
                                $this->getIo()->write("\n---\n" . $this->askMistral($prompt) . "\n---\n");
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
        $endPoint = "https://api.openai.com/v1/chat/completions";

        $messages = $this->interactions;
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Authorization' => "Bearer ".$apiKey,
            ],
            'json' => [
                'model' => 'gpt-3.5-turbo',
                'messages' => $messages,
                'max_tokens' => $maxTokens, // Adjust the max tokens as needed
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['choices'][0]['text'];

        // add prompth and response to interactions to maintain history
        $this->interactions[] = [
            'role' => 'user',
            'content' => $prompt,
        ];
        $this->interactions[] = [
            'role' => 'assistant',
            'content' => $generatedText,
        ];

        return trim($generatedText);
    }

    protected function askGoogleGemini(string $prompt) : string
    {
        $client = App::getInstance()->getGuzzle();
        $apiKey = App::getInstance()->getSiteData()->getConfigValue(\App\Base\Controllers\Admin\Json\GoogleGemini::GEMINI_TOKEN_PATH);
        $endPoint = "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash-latest:generateContent?key={$apiKey}";

        $contents = $this->interactions;
        $contents[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Content-Type' => "application/json",
            ],
            'json' => [
                'contents' => $contents,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['candidates'][0]['content']['parts'][0]['text'];

        // add prompth and response to interactions to maintain history
        $this->interactions[] = [
            'role' => 'user',
            'parts' => [['text' => $prompt]]
        ];
        $this->interactions[] = [
            'role' => 'model',
            'parts' => [['text' => $generatedText]]
        ];

        return trim($generatedText);
    }

    protected function askClaude(string $prompt) : string
    {
        $client = App::getInstance()->getGuzzle();
        $apiKey = App::getInstance()->getSiteData()->getConfigValue(\App\Base\Controllers\Admin\Json\Claude::CLAUDE_TOKEN_PATH);
        $endPoint = "https://api.anthropic.com/v1/messages";

        $messages = $this->interactions;
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Content-Type: application/json',
                'x-api-key: ' . $apiKey,
                'anthropic-version: 2023-06-01'
            ],
            'json' => [
                'model' => 'claude-3-5-sonnet-20241022',
                'max_tokens' => 1000,
                'messages' => $messages,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['content'][0]['text'];

        // add prompth and response to interactions to maintain history
        $this->interactions[] = [
            'role' => 'user',
            'content' => $prompt,
        ];
        $this->interactions[] = [
            'role' => 'model',
            'content' => $generatedText,
        ];

        return trim($generatedText);
    }

    protected function askMistral(string $prompt) : string
    {
        $client = App::getInstance()->getGuzzle();
        $apiKey = App::getInstance()->getSiteData()->getConfigValue(\App\Base\Controllers\Admin\Json\Mistral::MISTRAL_TOKEN_PATH);
        $endPoint = "https://api.mistral.ai/v1/chat/completions";

        $messages = $this->interactions;
        $messages[] = [
            'role' => 'user',
            'content' => $prompt,
        ];

        $response = $client->post($endPoint, [
            'headers' => [
                'Content-Type: application/json',
                'Authorization' => 'Bearer ' . $apiKey,
            ],
            'json' => [
                'model' => 'mistral-medium',
                'max_tokens' => 1000,
                "temperature" => 0.7,
                'messages' => $messages,
            ],
        ]);
        $data = json_decode($response->getBody(), true);
        $generatedText = $data['choices'][0]['message']['content'] ?? null;

        // add prompth and response to interactions to maintain history
        $this->interactions[] = [
            'role' => 'user',
            'content' => $prompt,
        ];
        $this->interactions[] = [
            'role' => 'model',
            'content' => $generatedText,
        ];

        return trim($generatedText);
    }

    protected function parseInteraction(array $interaction, string $aiType) : string 
    {
        $terminalWidth = (new Terminal())->getWidth() ?: 80;

        // ANSI per evidenziare il ruolo
        $role = strtoupper($interaction['role'] ?? 'unknown');
        $roleColored = match($role) {
            'USER' => "\033[1;34m$role\033[0m",                  // blu
            'ASSISTANT', 'MODEL' => "\033[1;32m$role\033[0m",    // verde
            default => "\033[1;31m$role\033[0m",                 // rosso
        };

        // Testo del messaggio
        $text = match($aiType) {
            'mistral','claude','chatgpt' => $interaction['content'] ?? 'n/a',
            'googlegemini' => $interaction['parts'][0]['text'] ?? 'n/a',
            default => 'Unknown ' . $aiType .' model',
        };

        if ($role === 'USER') {
            // Allinea a sinistra
            return $roleColored . "\n" . $text;
        }

        // --- Allineamento a destra (role + testo), rispettando ANSI ---
        $roleLine = $this->padLeftVisible($roleColored, $terminalWidth);

        // spezza il testo in base alla larghezza del terminale
        $wrapped = wordwrap($text, $terminalWidth, "\n", true);
        $lines = explode("\n", $wrapped);

        $alignedText = implode("\n", array_map(
            fn($line) => $this->padLeftVisible($line, $terminalWidth),
            $lines
        ));

        return $roleLine . "\n" . $alignedText;
    }

    /** --- Helper --- */

    /** Rimuove le sequenze ANSI (colori, ecc.) */
    private function stripAnsi(string $s): string
    {
        // pattern generico per escape ANSI (CSI)
        return preg_replace('/\x1B\[[0-9;]*[ -\/]*[@-~]/', '', $s) ?? $s;
    }

    /** Larghezza visibile (senza ANSI), con supporto multibyte */
    private function visibleWidth(string $s): int
    {
        return mb_strwidth($this->stripAnsi($s), 'UTF-8');
    }

    /** Pad a sinistra calcolato sulla larghezza visibile */
    private function padLeftVisible(string $s, int $width): string
    {
        $pad = max(0, $width - $this->visibleWidth($s));
        return str_repeat(' ', $pad) . $s;
    }
}
