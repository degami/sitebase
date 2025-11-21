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

    public function sendRaw(array $payload, ?string $model = null, string $endpoint = self::COMPLETIONS_ENDPOINT) : array
    {
        $client = $this->getGuzzle();

        $endopointUrl = match ($endpoint) {
            self::COMPLETIONS_ENDPOINT => $this->getCompletionsEndpoint($model),
            self::EMBEDDINGS_ENDPOINT => $this->getEmbeddingsEndpoint($model),
            default => throw new Exception("Invalid endpoint type: " . $endpoint),
        };
        $prepared = $this->prepareRequest($payload);

        if (App::getInstance()->getEnvironment()->canDebug()) {
            $this->getApplicationLogger()->debug("Sending LLM request <pre>" . json_encode(['endpoint' => $endopointUrl, 'payload' => $prepared], JSON_PRETTY_PRINT) . "</pre>");
        }

        $resp = $client->post(
            $endopointUrl,
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

        $raw = $this->sendRaw($contents, $model, self::COMPLETIONS_ENDPOINT);
        $norm = $this->normalizeResponse($raw);

        return trim($norm['assistantText'] ?? '');
    }

    public function embed(string $input, ?string $model = null) : array
    {
        $payload = $this->buildEmbeddingRequest($input);

        $raw = $this->sendRaw($payload, $model, self::EMBEDDINGS_ENDPOINT);

        if (!empty($raw['data'][0]['embedding']) && is_array($raw['data'][0]['embedding'])) {
            return $raw['data'][0]['embedding'];
        }

        return [];
    }
}
