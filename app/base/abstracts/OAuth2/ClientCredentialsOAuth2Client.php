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

use App\Base\Models\OAuth2AccessToken;
use Degami\Basics\Exceptions\BasicException;
use GuzzleHttp\Exception\GuzzleException;

/**
 * Client Credentials OAuth2 Client
 */
abstract class ClientCredentialsOAuth2Client extends AbstractOAuth2ApiClient
{
    /**
     * Get the OAuth2 token
     *
     * @return OAuth2AccessToken
     * @throws BasicException
     * @throws GuzzleException
     */
    protected function getToken(): OAuth2AccessToken
    {
        if ($this->token && new \DateTime("".$this->token->getExpitesAt()) > new \DateTime()) {
            return $this->token;
        }

        /** @var OAuth2AccessToken $stored */
        $stored = OAuth2AccessToken::getCollection()->where(['api_base_url' => $this->getApiBaseUrl()])->getFirst();

        if ($stored && new \DateTime("".$stored->getExpitesAt()) > new \DateTime()) {
            return $this->token = $stored;
        }

        $response = $this->getUtils()->httpRequest($this->authUrl, 'POST', [
            'form_params' => [
                'grant_type'    => 'client_credentials',
                'client_id'     => $this->clientId,
                'client_secret' => $this->clientSecret,
                'scope'         => $this->scope,
            ],
        ]);

        if (!isset($response->access_token)) {
            throw new BasicException("Can't gain OAuth2 Token from {$this->authUrl}");
        }

        $token = OAuth2AccessToken::createFromResponse($this->getApiBaseUrl(), (array)$response);
        $token->persist();

        return $this->token = $token;
    }
}
