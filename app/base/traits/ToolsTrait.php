<?php

/**
 * SiteBase
 * PHP Version 7.0
 *
 * @category CMS / Framework
 * @package  Degami\Sitebase
 * @author   Mirko De Grandis <degami@github.com>
 * @license  MIT https://opensource.org/licenses/mit-license.php
 * @link     https://github.com/degami/sitebase
 */

namespace App\Base\Traits;

use App\Base\Abstracts\ContainerAwareObject;
use Exception;
use Degami\Basics\Traits\ToolsTrait as BasicToolsTrait;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use GuzzleHttp\Psr7\Response;

/**
 * utils Trait
 */
trait ToolsTrait
{
    use BasicToolsTrait;

    /**
     * executes an http request
     *
     * @param string $url
     * @param string $method
     * @param array $options
     * @return string|bool
     * @throws Exception
     * @throws GuzzleException
     */
    public function requestUrl(string $url, $method = 'GET', $options = [])
    {
        if ($this instanceof ContainerAwareObject) {
            $parsed = parse_url($url);
            if ($parsed) {
                $base_uri = $parsed['schema'] . '://' . $parsed['host'];

                $request_uri = $parsed['path'];
                if (isset($parsed['query'])) {
                    $request_uri .= '?' . $parsed['query'];
                }

                /** @var Client $client */
                $client = $this->getContainer()->make(
                    Client::class,
                    [
                        'base_uri' => $base_uri,
                    ]
                );
                /** @var Response $request */
                $response = $client->request($method, $request_uri, $options);

                return $response->getBody();
            }
            return false;
        }
        throw new Exception("Error. '" . get_class($this) . "' is not a ContainerAwareObject", 1);
    }
}
