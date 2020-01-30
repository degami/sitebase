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

use \Exception;
use \Degami\Basics\Traits\ToolsTrait as BasicToolsTrait;

/**
 * utils Trait
 */
trait ToolsTrait
{
    use BasicToolsTrait;

    /**
     * executes an http request
     *
     * @param  string $url
     * @param  string $method
     * @param  array  $options
     * @return string|boolean
     */
    public function requestUrl($url, $method = 'GET', $options = [])
    {
        if ($this instanceof ContainerAwareObject) {
            $parsed = parse_url($url);
            if ($parsed) {
                $base_uri = $parsed['schema'].'://'.$parsed['host'];

                $request_uri = $parsed['path'];
                if (isset($parsed['query'])) {
                    $request_uri .= '?'.$parsed['query'];
                }

                $client = $this->getContainer()->make(
                    \GuzzleHttp\Client::class,
                    [
                    'base_uri' => $base_uri,
                    ]
                );
                $request->request($method, $request_uri, $options);

                $response = $request->send();
                return $response->getBody();
            }
            return false;
        }
        throw new Exception("Error. '".get_class($this)."' is not a ContainerAwareObject", 1);
    }
}
