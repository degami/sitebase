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

namespace App\Base\Interfaces\AI;
use App\Base\AI\Flows\BaseFlow;


interface AIModelInterface 
{
    public static function getCode() : string;
    public static function getName() : string;
    public static function isEnabled() : bool;
    public function getEndpoint(?string $model = null) : string;
    public function prepareRequest(array $payload) : array;
    public function formatUserMessage(string $prompt): array;
    public function formatAssistantMessage(mixed $message, ?string $messageType = null): array;
    public function formatAssistantFunctionCallMessage(string $functionName, array $args, ?string $id = null): ?array;
    public function buildConversation(array $previousMessages, string $prompt, ?string $model = null): array;
    public function sendRaw(array $payload) : array;
    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string;
    public function normalizeResponse(array $raw) : array;
    public function sendFunctionResponse(string $functionName, array $result, array &$history = [], ?string $id = null): array;
    public function buildFlowInitialRequest(BaseFlow $flow, string $userPrompt, ?string $model = null): array;
    public function getAvailableModels(bool $reset = false) : array;
    public function getVersion() : string;
    public function getModel(?string $model = null) : string;
    public function getDefaultModel() : string;
}