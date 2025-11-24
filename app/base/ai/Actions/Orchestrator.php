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
    protected AIModelInterface $llm;
    protected array $toolsRegistry = [];

    public function __construct(AIModelInterface $llm)
    {
        $this->llm = $llm;
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
    public function runFlow(BaseFlow $flow, string $userPrompt) : array
    {
        $payload = $this->llm->buildFlowInitialRequest($flow, $userPrompt);

        // extract messages for history tracking
        $messages = $payload['messages'] ?? $payload['contents'] ?? [];

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


        return $normalized;
    }
}
