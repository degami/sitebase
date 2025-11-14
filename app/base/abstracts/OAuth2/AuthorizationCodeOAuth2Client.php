<?php

namespace App\Base\Abstracts\OAuth2;

use Psr\Container\ContainerInterface;
use App\Base\Models\OAuth2AccessToken;
use Degami\Basics\Exceptions\BasicException;
use GuzzleHttp\Exception\GuzzleException;

abstract class AuthorizationCodeOAuth2Client extends AbstractOAuth2ApiClient
{
    public function __construct(
         protected ContainerInterface $container, 
         protected string $clientId, 
         protected string $clientSecret, 
         protected string $scope,
         protected string $authUrl, 
         protected string $apiBaseUrl,
         protected string $redirectUri
    ) {
        parent::__construct($container, $clientId, $clientSecret, $scope, $authUrl, $apiBaseUrl);
    }

    /**
     * Get the authorization URL
     *
     * @return string
     */
    public function getAuthorizationUrl(): string
    {
        $params = http_build_query([
            'response_type' => 'code',
            'client_id'     => $this->clientId,
            'redirect_uri'  => $this->redirectUri,
            'scope'         => $this->scope,
        ]);

        return "{$this->getAuthUrl()}/authorize?{$params}";
    }

    /**
     * Exchange authorization code for access token
     *
     * @param string $code
     * @return OAuth2AccessToken
     * @throws BasicException
     * @throws GuzzleException
     */
    public function exchangeCodeForToken(string $code): OAuth2AccessToken
    {
        $response = $this->getUtils()->httpRequest($this->getAuthUrl() . '/token', 'POST', [
            'form_params' => [
                'grant_type'    => 'authorization_code',
                'code'          => $code,
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri'  => $this->redirectUri,
            ],
        ]);

        if (!isset($response->access_token)) {
            throw new BasicException("Error exchanging code for token");
        }

        $token = OAuth2AccessToken::createFromResponse($this->getApiBaseUrl(), (array)$response);
        $token->persist();
        $this->token = $token;

        return $token;
    }

    /**
     * Refresh the OAuth2 token
     *
     * @return OAuth2AccessToken
     * @throws BasicException
     * @throws GuzzleException
     */
    protected function refreshToken(): OAuth2AccessToken
    {
        if (!$this->token || !$this->token->getRefreshToken()) {
            throw new BasicException('Cannot refresh OAuth2 token without refresh token.');
        }

        $response = $this->getUtils()->httpRequest($this->getAuthUrl() . '/token', 'POST', [
            'form_params' => [
                'grant_type'    => 'refresh_token',
                'refresh_token' => $this->token->getRefreshToken(),
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
            ],
        ]);

        $newToken = OAuth2AccessToken::createFromResponse($this->getApiBaseUrl(), (array)$response);
        $newToken->persist();

        return $this->token = $newToken;
    }
}
