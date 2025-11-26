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
use App\Base\Interfaces\AI\AIModelInterface;
use Exception;

class Orchestrator
{
    protected array $toolsRegistry = [];

    public function __construct(
        protected AIModelInterface $llm, 
        protected BaseFlow $flow
    ) { 
        // auto register tool handlers.
        foreach ($flow->toolHandlers() as $toolName => $toolHandler) {
            if (is_callable($toolHandler)) {
                $this->registerTool($toolName, $toolHandler);
            }
        }
    }

    /**
     * Register a tool handler
     */
    public function registerTool(string $name, callable $handler) : void
    {
        $this->toolsRegistry[$name] = $handler;
    }

    /**
     * Run a flow
     */
    public function runFlow(string $userPrompt, array &$history = []) : array
    {
        $payload = $this->llm->buildFlowInitialRequest($this->flow, $userPrompt, $history);

        // extract messages for history tracking
        $initialMessages = $payload['messages'] ?? $payload['contents'] ?? [];

        $messages = [];
        foreach ($initialMessages as $msg) {
            $messages[] = $msg;
        }

        $response = $this->llm->sendRaw($payload);

        $normalized = $this->llm->normalizeCompletionsResponse($response);

        while (!empty($normalized['functionCalls'])) {
            $call = $normalized['functionCalls'][0];
            $functionName = $call['name'] ?? null;
            $args = $call['args'] ?? [];
            $id = $call['id'] ?? null;

            if (!$functionName) {
                throw new Exception("FunctionCall senza 'name'");
            }

            if (!isset($this->toolsRegistry[$functionName])) {
                throw new Exception("Tool non registrato: {$functionName}");
            }

            if (!empty($normalized['rawFunctionMessages'][0])) {
                $messages[] = $normalized['rawFunctionMessages'][0];
            } else {
                $assistantMessage = $this->llm->formatAssistantFunctionCallMessage($functionName, $args);
                if (!is_null($assistantMessage)) {
                    $messages[] = $assistantMessage;
                }
            }

            $handler = $this->toolsRegistry[$functionName];

            if (is_string($args)) {
                $decoded = json_decode($args, true);
                $args = $decoded ?: [];
            }

            $toolResult = $handler($args);

            $response = $this->llm->sendFunctionResponse(
                $functionName,
                $toolResult,
                $payload['tools'] ?? null,
                $messages
            );

            $normalized = $this->llm->normalizeCompletionsResponse($response);
        }

        // as user prompt is already in initialMessages and is probably not reported into history, add it.
        $history[] = $this->llm->formatUserMessage($userPrompt);

        // save flow messages to history, we can skip technical messages and previous history
        $history = array_merge($history, array_slice($this->filterTechMessages($messages), count($initialMessages)));

        // add assistant final message to history
        $history[] = $this->llm->formatAssistantMessage($normalized['assistantText'] ?? '');

        return $normalized;
    }

    protected function filterTechMessages(array $messages) : array
    {
        return array_filter($messages, function ($msg) {
            $msg = json_decode(json_encode($msg), true); // force array
            if ($msg['role'] == 'model' && isset($msg['parts'][0]['functionCall'])) {
                return false;
            }
            return !in_array($msg['role'] ?? '', ['system', 'tool']);
        });
    }
}
