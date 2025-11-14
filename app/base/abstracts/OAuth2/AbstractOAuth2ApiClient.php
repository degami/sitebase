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

namespace App\Base\Abstracts\OAuth2;

use Psr\Container\ContainerInterface;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Models\OAuth2AccessToken;
use Degami\Basics\Exceptions\BasicException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Base Class for OAuth2 Clients
 */
abstract class AbstractOAuth2ApiClient extends ContainerAwareObject
{
    protected ?OAuth2AccessToken $token = null;

    public function __construct(       
         protected ContainerInterface $container, 
         protected string $clientId, 
         protected string $clientSecret, 
         protected string $scope,
         protected string $authUrl, 
         protected string $apiBaseUrl
    ) { }

    /**
     * Get the API base URL
     *
     * @return string
     */
    protected function getApiBaseUrl(): string
    {
        return rtrim($this->apiBaseUrl, '/');
    }

    /**
     * Get the Auth URL
     *
     * @return string
     */
    protected function getAuthUrl(): string
    {
        return rtrim($this->authUrl, '/');
    }

    /**
     * Get the OAuth2 token
     *
     * @return OAuth2AccessToken
     * @throws BasicException
     * @throws GuzzleException
     */
    protected function getToken(): OAuth2AccessToken
    {
        if ($this->token && $this->token->getExpitesAt() > new \DateTime()) {
            return $this->token;
        }

        $response = $this->getUtils()->httpRequest($this->getAuthUrl(), 'POST', [
            'form_params' => [
                'grant_type' => 'client_credentials',
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => $this->scope,
            ],
        ]);

        if (!isset($response->access_token)) {
            throw new BasicException("Can't gain OAuth2 Token from {$this->getAuthUrl()}");
        }

        return $this->token = OAuth2AccessToken::createFromResponse($this->getApiBaseUrl(), (array)$response);
    }

    /**
     * Refresh the OAuth2 token
     *
     * @return OAuth2AccessToken
     * @throws BasicException
     * @throws GuzzleException
     */
    protected function refreshToken() : OAuth2AccessToken
    {
        if (!$this->token || !$this->token->getRefreshToken()) {
            throw new BasicException('No refresh token available');
        }

        $response = $this->getUtils()->httpRequest($this->getAuthUrl(), 'POST', [
            'form_params' => [
                'grant_type' => 'refresh_token',
                'refresh_token' => $this->token->getRefreshToken(),
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope' => $this->scope,
            ],
        ]);

        return $this->token = OAuth2AccessToken::createFromResponse($this->getApiBaseUrl(), (array)$response);
    }

    /**
     * Make an API request
     *
     * @param string $endpoint
     * @param string $method
     * @param array $options
     * @return mixed
     * @throws BasicException
     * @throws GuzzleException
     */
    protected function apiRequest(string $endpoint, string $method = 'GET', array $options = []): mixed
    {
        $token = $this->getToken();
        $options['headers']['Authorization'] = "{$token->getType()} {$token->getAccessToken()}";
        $options['headers']['Accept'] = 'application/json';

        return $this->getUtils()->httpRequest($this->getApiBaseUrl() . '/' . ltrim($endpoint, '/'), $method, $options);
    }
}
