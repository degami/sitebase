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

namespace App\Base\Models;

use App\Base\Abstracts\Models\BaseModel;


/**
 * OAuth2 Access Token Model
 * 
 * @method int getId()
 * @method string getApiBaseUrl()
 * @method string getAccessToken()
 * @method string getRefreshToken()
 * @method string getType()
 * @method \DateTime getExpitesAt()
 * @method string getScope()
 * @method \DateTime getCreatedAt()
 * @method \DateTime getUpdatedAt()
 * @method self setId(int $id)
 * @method self setApiBaseUrl(string $apiBaseUrl)
 * @method self setAccessToken(string $access_token)
 * @method self setRefreshToken(string $refresh_token)
 * @method self setType(string $type)
 * @method self setExpitesAt(\DateTime $expites_at)
 * @method self setScope(string $scope)
 * @method self setCreatedAt(\DateTime $created_at)
 * @method self setUpdatedAt(\DateTime $updated_at)
 */
class OAuth2AccessToken extends BaseModel
{
    public const DEFAULT_DURATION_SECONDS = 3600; // 1 hour

    public ?string $table_name = 'oauth2_access_token';

    public static function createFromResponse(string $apiBaseUrl, array $data): self
    {
        $token = new self();
        $token
            ->setApiBaseUrl($apiBaseUrl)
            ->setAccessToken($data['access_token'] ?? null)
            ->setRefreshToken($data['refresh_token'] ?? null)
            ->setType($data['token_type'] ?? 'Bearer')
            ->setScope($data['scope'] ?? null)
            ->setExpitesAt((new \DateTime())->modify('+' . ($data['expires_in'] ?? self::DEFAULT_DURATION_SECONDS) . ' seconds'));
        return $token;
    }
}
