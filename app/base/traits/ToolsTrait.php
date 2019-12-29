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

/**
 * utils Trait
 */
trait ToolsTrait
{
    /**
     * executes an http request
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

                $client = $this->getContainer()->make(\GuzzleHttp\Client::class, [
                    'base_uri' => $base_uri,
                ]);
                $request->request($method, $request_uri, $options);

                 $response = $request->send();
                 return $response->getBody();
            }
            return false;
        }
        throw new Exception("Error. '".get_class($this)."' is not a ContainerAwareObject", 1);
    }

    /**
     * Take a string_like_this and return a StringLikeThis
     * @param  string
     * @return string
     */
    public static function snakeCaseToPascalCase($input)
    {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $input)));
    }

    /**
     * Take a StringLikeThis and return string_like_this
     * @param  string
     * @return string
     */
    public static function pascalCaseToSnakeCase($input)
    {
        preg_match_all('!([A-Z][A-Z0-9]*(?=$|[A-Z][a-z0-9])|[A-Za-z][a-z0-9]+)!', $input, $matches);
        $ret = $matches[0];
        foreach ($ret as &$match) {
            $match = $match == strtoupper($match) ? strtolower($match) : lcfirst($match);
        }
        return implode('_', $ret);
    }

    /**
     * slugify string
     * @param  string $text
     * @return string
     */
    public function slugify($text)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\pL\d\/]+~u', '-', $text);

        // transliterate
        $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w\/]+~', '', $text);

        // trim
        $text = trim($text, '-');

        // remove duplicate -
        $text = preg_replace('~-+~', '-', $text);

        // lowercase
        $text = strtolower($text);

        if (empty($text)) {
            return 'n-a';
        }

        return $text;
    }

    /**
     * format byte size
     * @param  integer $size size in bytes
     * @return string       formatted size
     */
    public static function formatBytes($size)
    {
        $units = [' B', ' KB', ' MB', ' GB', ' TB'];
        for ($i = 0; $size >= 1024 && $i < 4; $i++) {
            $size /= 1024;
        }
        return round($size, 2).$units[$i];
    }
}
