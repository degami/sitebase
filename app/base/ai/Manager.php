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

namespace App\Base\AI;

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
use Exception;

/**
 * Ai Manager
 */
class Manager extends ContainerAwareObject
{
    public const GEMINI_TOKEN_PATH = 'app/gemini/token';

    public const CHATGPT_MODEL = 'gpt-3.5-turbo';
    public const CHATGPT_TOKEN_PATH = 'app/chatgpt/token';
    public const CHATGPT_REMAINING_TOKENS_PATH = 'app/chatgpt/remaining_tokens';
    public const CHATGPT_MAX_TOKENS = 50;

    public const CLAUDE_MODEL = 'claude-3-5-sonnet-20241022';
    public const CLAUDE_TOKEN_PATH = 'app/claude/token';
    public const CLAUDE_REMAINING_TOKENS_PATH = 'app/claude/remaining_tokens';
    public const CLAUDE_MAX_TOKENS = 1000;

    public const MISTRAL_MODEL = 'mistral-medium';
    public const MISTRAL_TOKEN_PATH = 'app/mistral/token';
    public const MISTRAL_REMAINING_TOKENS_PATH = 'app/mistral/remaining_tokens';
    public const MISTRAL_MAX_TOKENS = 1000;

    protected array $interactions = [];


    public function getInteractions() : array
    {
        return $this->interactions;
    }

    public function getAvailableAIs(bool $withNames = false) : array
    {
        $AIs = [
            'googlegemini' => 'Google Gemini', 
            'chatgpt' => 'ChatGPT', 
            'claude' => 'Claude', 
            'mistral' => 'Mistral',
        ];

        if ($withNames) {
            return $AIs;
        }

        return array_keys($AIs);
    }

    public static function isChatGPTEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH));
    }

    public static function isGoogleGeminiEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH));
    }

    public static function isClaudeEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH));
    }

    public static function isMistralEnabled() : bool 
    {
        return !empty(App::getInstance()->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH));
    }

    public function isAiAvailable(string|array|null $ai = null): bool
    {
        if (is_array($ai)) {
            $out = true;
            $ai = array_intersect(array_map('strtolower', $ai), $this->getAvailableAIs());
            if (empty($ai)) {
                return false;
            }

            foreach($ai as $aiElem) {
                if ($aiElem == 'googlegemini' && !static::isGoogleGeminiEnabled()) {
                    $out &= false;
                }
                if ($aiElem == 'chatgpt' && !static::isChatGPTEnabled()) {
                    $out &= false;
                }
                if ($aiElem == 'claude' && !static::isClaudeEnabled()) {
                    $out &= false;
                }
                if ($aiElem == 'mistral' && !static::isMistralEnabled()) {
                    $out &= false;
                }
            }
            return $out;
        } elseif (is_string($ai)) {
            if ($ai == 'googlegemini') {
                return static::isGoogleGeminiEnabled();
            } elseif ($ai == 'chatgpt') {
                return static::isChatGPTEnabled();
            } elseif ($ai == 'claude') {
                return static::isClaudeEnabled();
            } elseif ($ai == 'mistral') {
                return static::isMistralEnabled();
            }
            return false;
        }

        return static::isGoogleGeminiEnabled() || static::isChatGPTEnabled() || static::isClaudeEnabled() || static::isMistralEnabled();
    }

    public function askChatGPT(string $prompt) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::CHATGPT_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing ChatGPT Token");
        }

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::CHATGPT_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::CHATGPT_MAX_TOKENS;

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
                'model' => self::CHATGPT_MODEL,
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

        // update remaining tokens configuration
        // $this->getSiteData()->setConfigValue(self::CHATGPT_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return trim($generatedText);
    }

    public function askGoogleGemini(string $prompt) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::GEMINI_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Gemini Token");
        }

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

    public function askClaude(string $prompt) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::CLAUDE_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Claude Token");
        }

        $endPoint = "https://api.anthropic.com/v1/messages";

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::CLAUDE_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::CLAUDE_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::CLAUDE_MAX_TOKENS;

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
                'model' => self::CLAUDE_MODEL,
                'max_tokens' => $maxTokens,
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

        // update remaining tokens configuration
        // $this->getSiteData()->setConfigValue(self::CLAUDE_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return trim($generatedText);
    }

    public function askMistral(string $prompt) : string
    {
        $client = $this->getGuzzle();
        $apiKey = $this->getSiteData()->getConfigValue(self::MISTRAL_TOKEN_PATH);

        if (empty($apiKey)) {
            throw new Exception("Missing Mistral Token");
        }

        $endPoint = "https://api.mistral.ai/v1/chat/completions";

        // $remainingTokens = intval($this->getSiteData()->getConfigValue(self::MISTRAL_REMAINING_TOKENS_PATH));
        // $maxTokens = min(self::MISTRAL_MAX_TOKENS, $remainingTokens);

        $maxTokens = self::MISTRAL_MAX_TOKENS;

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
                'model' => self::MISTRAL_MODEL,
                'max_tokens' => $maxTokens,
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

        // update remaining tokens configuration
        // $this->getSiteData()->setConfigValue(self::MISTRAL_REMAINING_TOKENS_PATH, max($remainingTokens - $maxTokens, 0));

        return trim($generatedText);
    }

    public function getHistory(string $aiType, int $terminalWidth = 80) : array
    {
        $out = [];
        foreach ($this->getInteractions() as $interaction) {
            $out[] = $this->parseInteraction($interaction, $aiType, $terminalWidth);
        }

        return $out;
    }

    protected function parseInteraction(array $interaction, string $aiType, int $terminalWidth = 80) : string 
    {
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
