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

namespace App\Base\AI\Actions;

use App\Base\AI\Flows\BaseFlow;
use App\Base\AI\Models\GoogleGemini;
use App\Base\Interfaces\AI\AIModelInterface;
use Exception;

class Orchestrator
{
    protected AIModelInterface $llm;
    protected array $toolsRegistry = [];

    public function __construct(AIModelInterface $llm)
    {
        $this->llm = $llm;
    }

    /**
     * Registra un tool invocabile dal modello
     */
    public function registerTool(string $name, callable $handler) : void
    {
        $this->toolsRegistry[$name] = $handler;
    }

    /**
     * Avvia il flow completo.
     */
    public function runFlow(BaseFlow $flow, string $userPrompt) : array
    {
        //
        // 1) Costruisco i messaggi iniziali
        //
        $messages = $this->llm->buildFlowInitialMessages($flow, $userPrompt);


        //
        // 2) Prima chiamata al modello
        //
        if ($this->llm instanceof GoogleGemini) {

            // Costruiamo le dichiarazioni dei tool
            $functions = [];
            foreach ($flow->tools() as $name => $schema) {
                $functions[] = [
                    'name' => $name,
                    'description' => $schema['description'] ?? '',
                    'parameters' => $schema['parameters'] ?? ['type' => 'object']
                ];
            }

            $response = $this->llm->sendRaw([
                'contents' => $messages,
                'tools' => [
                    [
                        'function_declarations' => $functions
                    ]
                ]
            ]);

        } else {
            // fallback per altri LLM (OpenAI, Anthropic, ecc.)
            $response = $this->llm->sendRaw([
                'messages' => $messages
            ]);
        }


        //
        // 3) Normalizziamo la risposta
        //
        $normalized = $this->llm->normalizeResponse($response);


        //
        // 4) Ciclo finché il modello continua a chiamare funzioni
        //
        while (!empty($normalized['functionCalls'])) {

            $call = $normalized['functionCalls'][0];
            $functionName = $call['name'] ?? null;
            $args = $call['args'] ?? [];

            if (!$functionName) {
                throw new Exception("FunctionCall senza 'name'");
            }

            if (!isset($this->toolsRegistry[$functionName])) {
                throw new Exception("Tool non registrato: {$functionName}");
            }

            //
            // 4a) Gemini richiede che il function_call originale
            //     sia inserito nella history ESATTAMENTE com'era.
            //
            if (!empty($normalized['rawFunctionMessages'][0])) {
                $messages[] = $normalized['rawFunctionMessages'][0];
            } else {
                // fallback sicuro per altri LLM
                $messages[] = [
                    'role' => 'model',
                    'parts' => [
                        [
                            'function_call' => [
                                'name' => $functionName,
                                'args' => $args
                            ]
                        ]
                    ]
                ];
            }


            //
            // 4b) Eseguiamo il tool
            //
            $handler = $this->toolsRegistry[$functionName];

            if (is_string($args)) {
                $decoded = json_decode($args, true);
                $args = $decoded ?: [];
            }

            $toolResult = $handler($args);


            //
            // 4c) Rispondiamo al modello con function_response
            //     e con TUTTA la history (compreso il function_call originale)
            //
            $response = $this->llm->sendFunctionResponse(
                $functionName,
                $toolResult,
                $messages
            );

            //
            // 4d) Normalizziamo la nuova risposta
            //
            $normalized = $this->llm->normalizeResponse($response);

            //
            // ⚠️ IMPORTANTE:
            // NON aggiungere assistantText alla history qui.
            // I messaggi testuali vanno aggiunti solo quando la chain finisce.
            //
        }


        //
        // 5) Fine del flow: ritorna il risultato finale
        //
        return $normalized;
    }
}
