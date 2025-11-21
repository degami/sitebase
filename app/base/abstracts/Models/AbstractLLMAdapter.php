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

namespace App\Base\Abstracts\Models;

use App\App;
use App\Base\Interfaces\AI\AIModelInterface;
use App\Base\Abstracts\ContainerAwareObject;
use Exception;

/**
 * Abstract LLM Adapter
 */
abstract class AbstractLLMAdapter extends ContainerAwareObject implements AIModelInterface
{

    public function sendRaw(array $payload) : array
    {
        $client = $this->getGuzzle();

        $prepared = $this->prepareRequest($payload);

        if (App::getInstance()->getEnvironment()->canDebug()) {
            $this->getApplicationLogger()->debug("Sending LLM request <pre>" . json_encode(['endpoint' => $this->getEndpoint(), 'payload' => $prepared], JSON_PRETTY_PRINT) . "</pre>");
        }

        $resp = $client->post(
            $this->getEndpoint(),
            $prepared
        );

        $responseBody = $resp->getBody()->getContents();

        if (App::getInstance()->getEnvironment()->canDebug()) {
            $this->getApplicationLogger()->debug("Received LLM response <pre>" . json_encode(['response' => $responseBody], JSON_PRETTY_PRINT) . "</pre>");
        }

        if (!isJson($responseBody)) {
            throw new Exception("Invalid response from LLM API");
        }

        return json_decode($responseBody, true) ?? [];
    }

    public function ask(string $prompt, ?string $model = null, ?array $previousMessages = null) : string
    {
        $contents = $this->buildConversation(
            $previousMessages ?? [],
            $prompt
        );

        $raw = $this->sendRaw($contents);
        $norm = $this->normalizeResponse($raw);

        return trim($norm['assistantText'] ?? '');
    }
}
