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

namespace App\Base\Tools\AI;

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Interfaces\AI\AIModelInterface;
use App\Base\Tools\Utils\Globals;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;

/**
 * Ai Manager
 */
class Manager extends ContainerAwareObject
{
    public const MAX_INTERACTIONS_HISTORYLENGTH = 200;
    public const MAX_INTERACTIONS_HISTORYLIFETIME = 1800;

    protected array $interactions = [];
    protected ?array $availableAIsCache = null;

    public function getAvailableAIs(bool $fullInfo = false) : array
    {
        if ($this->availableAIsCache !== null) {
            if ($fullInfo) {
                if (!$this->getEnvironment()->isCli()) {
                    foreach ($this->availableAIsCache as $aiCode => &$aiInfo) {
                        $aiInfo['aiURL'] = $this->getAdminRouter()->getUrl('crud.app.base.controllers.admin.json.'.$aiCode);
                    }
                }

                return $this->availableAIsCache;
            }

            return array_keys($this->availableAIsCache);
        }

        $classes = array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_AIMODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::AIMODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
        );

        $classes = array_filter($classes, function($className) {
            return is_subclass_of($className, AIModelInterface::class) && !(new \ReflectionClass($className))->isAbstract();
        });

        $AIs = [];
        foreach ($classes as $className) {
            $code = $this->containerCall([$className, 'getCode']);
            $name = $this->containerCall([$className, 'getName']);
            $AIs[$code] = [
                'code' => $code,
                'name' => $name,
                'class' => $className,
            ];
        }

        if (is_array($AIs)) {
            $this->availableAIsCache = $AIs;
        }

        if ($fullInfo) {
            if (!$this->getEnvironment()->isCli()) {
                foreach ($AIs as $aiCode => &$aiInfo) {
                    $aiInfo['aiURL'] = $this->getAdminRouter()->getUrl('crud.app.base.controllers.admin.json.'.$aiCode);
                }
            }

            return $AIs;
        }

        return array_keys($AIs);
    }

    public function getEnabledAIs(bool $fullInfo = false) : array
    {
        $enabled = array_filter($this->getAvailableAIs($fullInfo), function($aiModel) {
            return $this->isAiAvailable(is_array($aiModel) ? $aiModel['code'] : $aiModel);
        });

        return $enabled;
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
                if (!$this->getAIModel($aiElem)->isEnabled()) {
                    $out &= false;
                }
            }
            return $out;
        } elseif (is_string($ai)) {
            return $this->getAIModel($ai)?->isEnabled() ?? false;
        }

        foreach($this->getAvailableAIs() as $aiElem) {
            if ($this->getAIModel($aiElem)?->isEnabled()) {
                return true;
            }
        }

        return false;
    }

    public function askAI(string $aiType, string $prompt, ?string $model = null) : string
    {
        // get previous interactions for the selected aiType
        $previousInteractions = $this->getInteractions($aiType);
 
        $generatedText = $this->getAIModel($aiType)->ask($prompt, $model, $previousInteractions);
 
        // add prompth and response to interactions to maintain history
        $this->saveInteraction($prompt, $generatedText, $aiType);
 
        return trim($generatedText);
    }

    public function clearInteractions(?string $aiType = null) : self
    {
        if ($this->getRedis()->isEnabled()) {
            // $this->getRedis()->select(intval($this->getEnvironment()->getVariable('REDIS_DATABASE')) + 1);
            $redis_key = $this->getRedisKey($aiType);
            $this->getRedis()->del($redis_key);
        }

        $this->interactions = [];
        return $this;
    }

    public function getInteractions(?string $aiType = null) : array
    {
        if ($this->getRedis()->isEnabled()) {
            // $this->getRedis()->select(intval($this->getEnvironment()->getVariable('REDIS_DATABASE')) + 1);
            $redis_key = $this->getRedisKey($aiType);

            // get last MAX_INTERACTIONS_HISTORYLENGTH elements
            return array_slice(
                array_map(fn($el) => json_decode($el, true), $this->getRedis()->lRange($redis_key, 0, -1)) ?: [], 
                -1 * self::MAX_INTERACTIONS_HISTORYLENGTH
            );
        }

        return array_slice( $this->interactions, -1 * self::MAX_INTERACTIONS_HISTORYLENGTH);
    }

    protected function saveInteraction(string $prompt, string $generatedText, ?string $model = null) : self
    {       
        $modelRole = match($model) {
            'googlegemini', 'claude' => 'model',
            'chatgpt', 'mistral' => 'assistant',
            default => 'model',
        };

        if ($model == 'googlegemini') {
            $userInteraction = [
                'role' => 'user',
                'parts' => [['text' => $prompt]]
            ];
            $modelInteraction = [
                'role' => $modelRole,
                'parts' => [['text' => $generatedText]]
            ];
        } else {
            $userInteraction = [
                'role' => 'user',
                'content' => $prompt,
            ];
            $modelInteraction = [
                'role' => 'assistant',
                'content' => $generatedText,
            ];
        }

        if ($this->getRedis()->isEnabled()) {
            // $this->getRedis()->select(intval($this->getEnvironment()->getVariable('REDIS_DATABASE')) + 1);
            $redis_key = $this->getRedisKey($model);

            $this->getRedis()->rPush($redis_key, json_encode($userInteraction));
            $this->getRedis()->rPush($redis_key, json_encode($modelInteraction));

            // set key expiration
            $this->getRedis()->expire($redis_key, self::MAX_INTERACTIONS_HISTORYLIFETIME);

            return $this;
        }

        $this->interactions[] = $userInteraction;
        $this->interactions[] = $modelInteraction;

        return $this;
    }

    protected function getRedisKey(?string $aiType = null) : string
    {
        return 'ai_interactions:' . $aiType . ':' . ($this->getAuth()->getCurrentUser()?->getId() ?? 0);
    }

    public function getHistory(string $aiType, int $terminalWidth = 80) : array
    {
        $out = [];
        foreach ($this->getInteractions($aiType) as $interaction) {
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

    public function getAIModel(string $aiType) : ?AIModelInterface
    {
        $availableAIs = $this->getAvailableAIs(true);
        if (array_key_exists($aiType, $availableAIs)) {
            return $this->containerMake($availableAIs[$aiType]['class']);
        }

        return null;
    }

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
