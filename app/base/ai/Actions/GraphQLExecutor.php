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

use GuzzleHttp\Client;
use Exception;

class GraphQLExecutor
{
    protected string $endpoint;
    protected ?string $authHeader; // Bearer token or null
    protected Client $http;

    public function __construct(string $endpoint, ?string $authHeader = null)
    {
        $this->endpoint = $endpoint;
        $this->authHeader = $authHeader;
        $this->http = new Client(['timeout' => 60, 'verify' => false]);
    }

    /**
     * Execute query/mutation and return decoded JSON (data or errors)
     *
     * @param string $query
     * @param array $variables
     * @return array
     * @throws Exception
     */
    public function execute(string $query, array $variables = []): array
    {
        $headers = ['Content-Type' => 'application/json'];
        if (!empty($this->authHeader)) {
            $headers['Authorization'] = $this->authHeader;
        }

        $resp = $this->http->post($this->endpoint, [
            'headers' => $headers,
            'json' => ['query' => $query, 'variables' => $variables]
        ]);

        $body = json_decode($resp->getBody()->getContents(), true);
        if ($body === null) {
            throw new Exception("Invalid JSON response from GraphQL endpoint");
        }
        return $body;
    }
}
