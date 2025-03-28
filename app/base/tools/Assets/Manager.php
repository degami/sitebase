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

namespace App\Base\Tools\Assets;

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
use Degami\Basics\Exceptions\BasicException;
use Degami\PHPFormsApi\Abstracts\Base\Field;
use Degami\PHPFormsApi\Abstracts\Base\FieldsContainer;
use Degami\PHPFormsApi\Form;
use DI\DependencyException;
use DI\NotFoundException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Degami\Basics\Html\TagElement;

/**
 * Assets manager
 */
class Manager extends ContainerAwareObject
{
    public const ASSETS_DOMAIN_PATH = 'app/frontend/assets_domain';

    /**
     * @var bool js already generated flag
     */
    protected bool $js_generated = false;

    /**
     * @var bool head js already generated flag
     */
    protected bool $head_js_generated = false;

    /**
     * @var array head js
     */
    protected array $head_js = ['encapsulated' => [], 'direct' => []];

    /**
     * @var array js
     */
    protected array $js = ['encapsulated' => [], 'direct' => []];

    /**
     * @var array head js scripts
     */
    protected array $head_js_scripts = [];

    /**
     * @var array head css
     */
    protected $head_css = [];

    /**
     * @var array css
     */
    protected array $css = [];

    /**
     * gets js elements
     *
     * @return array
     */
    public function getJs(): array
    {
        return $this->js;
    }

    /**
     * gets head js
     *
     * @return array
     */
    public function getHeadJs(): array
    {
        return $this->head_js;
    }

    public function getHeadJsScripts() : array
    {
        return $this->head_js_scripts;
    }

    /**
     * add js
     *
     * @param string|array $js javascript to add
     * @param bool $as_is no "minification"
     * @param string|null $position
     * @param bool $on_ready
     * @return self
     */
    public function addJs(array|string $js, bool $as_is = false, ?string $position = null, bool $on_ready = true): Manager
    {
        if (!$as_is) {
            if (is_array($js)) {
                $js = array_filter(array_map(['minifyJs', $this], $js));
            } elseif (is_string($js) && trim($js) != '') {
                $js = $this->minifyJs($js);
            }
        }

        $target = 'js';
        if (strtolower(trim((string) $position)) == 'head') {
            $target = 'head_js';
        }

        $section = 'encapsulated';
        if (!$on_ready) {
            $section = 'direct';
        }

        if (is_array($js)) {
            $this->{$target}[$section] = array_merge($js, $this->{$target}[$section]);
        } elseif (is_string($js) && trim($js) != '') {
            $this->{$target}[$section][] = $js;
        }

        return $this;
    }

    /**
     * add head js
     * 
     * @param string $url javascript url to add
     * @param array $tagAttributes tag attributes
     * @return self
     */
    public function addHeadJs(string $url, array $tagAttributes = []): Manager
    {
        $this->head_js_scripts[] = [
            'src' => $url,
            'attributes' => $tagAttributes,
        ];

        return $this;
    }

    /**
     * generate the js string
     *
     * @param string|null $position
     * @return string the js into a jquery sandbox
     */
    protected function generateJs(?string $position = null): string
    {
        if ($position == 'head') {
            $position = 'head_';
            $js = $this->getHeadJs();
        } else {
            $js = $this->getJs();
            $position = '';
        }

        if (!empty($js) && !$this->{trim($position) . "js_generated"}) {
            foreach (['encapsulated', 'direct'] as $section) {
                $js[$section] = array_filter(array_map('trim', $js[$section]));
                foreach ($js[$section] as &$js_string) {
                    if ($js_string[strlen($js_string) - 1] == ';') {
                        $js_string = substr($js_string, 0, strlen($js_string) - 1);
                    }
                }
            }

            $this->{trim($position) . "js_generated"} = true;
            return $this->encapsulateJs($js['encapsulated']) . implode(";\n", array_filter(array_map("trim", $js['direct'])));
        }
        return "";
    }


    /**
     * generate the js string
     *
     * @return string the js into a jquery sandbox
     */
    protected function generateHeadJs(): string
    {
        return $this->generateJs('head');
    }


    /**
     * generate the js string
     *
     * @return string the js into a jquery sandbox
     */
    protected function generatePageJs(): string
    {
        return $this->generateJs();
    }

    /**
     * encapsulate js into jquery ready function
     *
     * @param array $js_array
     * @param string $jquery_var_name
     * @return string
     */
    protected function encapsulateJs(array $js_array, $jquery_var_name = 'jQuery'): string
    {
        if (!is_array($js_array)) {
            $js_array = [$js_array];
        }

        $js_array = array_filter(array_map('trim', $js_array));

        return count($js_array) ? "(function(\$){\n" .
            "\t$(document).ready(function(){\n" .
            "\t\t" . implode(";\n\t\t", $js_array) . ";\n" .
            "\t});\n" .
            "})({$jquery_var_name});" : '';
    }

    /**
     * minify js string
     *
     * @param string $js javascript minify
     * @return string
     */
    protected function minifyJs(string $js): string
    {
        if (is_string($js) && trim($js) != '') {
            $js = trim(preg_replace("/\s+/", " ", str_replace("\n", "", "" . $js)));
        }

        return $js;
    }

    /**
     * gets inline js
     *
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderPageInlineJS(): string
    {
        $js = $this->generatePageJs();
        if (!empty($js)) {
            $options = [
                'tag' => 'script',
                'type' => 'text/javascript',
                'attributes' => [
                    'class' => '',
                ],
                'text' => $js,
            ];

            return $this->containerMake(TagElement::class, ['options' => $options]);
        }

        return '';
    }

    /**
     * gets inline js for head
     *
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderHeadInlineJS(): string
    {
        $js = $this->generateHeadJs();
        if (!empty($js)) {
            $options = [
                'tag' => 'script',
                'type' => 'text/javascript',
                'attributes' => [
                    'class' => '',
                ],
                'text' => $js,
            ];

            return $this->containerMake(TagElement::class, ['options' => $options]);
        }

        return '';
    }

    public function renderHeadJsScripts() : string
    {
        if (empty($this->head_js_scripts)) {
            return '';
        }


        $out = '';
        foreach ($this->head_js_scripts as $headScript) {
            if (isset($headScript['src']) && $this->isUrl($headScript['src'])) {
                $options = [
                    'tag' => 'script',
                    'type' => 'text/javascript',
                    'attributes' => [
                        'class' => '',
                        'src' => $headScript['src'],
                    ] + ($headScript['attributes'] ?? []),
                ];

                $out .= $this->containerMake(TagElement::class, ['options' => $options]);
            }
        }
        
        return $out;
    }

    /**
     * Add css to element
     *
     * @param string|array $css css to add
     * @return self
     */
    public function addCss(array|string $css): Manager
    {
        if (is_array($css)) {
            $css = array_filter(array_map('trim', $css));
            $this->css = array_merge($css, $this->css);
        } elseif (is_string($css) && trim($css) != '') {
            $this->css[] = trim($css);
        }

        return $this;
    }

    /**
     * add head css
     * 
     * @param string $url css url to add
     * @param array $tagAttributes tag attributes
     * @return self
     */
    public function addHeadCss(string $url, array $tagAttributes = []): Manager
    {
        $this->head_css[] = [
            'href' => $url,
            'attributes' => $tagAttributes,
        ];

        return $this;
    }

    /**
     * Get the element's css array
     *
     * @return array element's css array
     */
    public function getCss(): array
    {
        if ($this instanceof FieldsContainer || $this instanceof Form) {
            $css = array_filter(array_map('trim', $this->css));
            foreach ($this->getFields() as $field) {
                /**
                 * @var Field $field
                 */
                $css = array_merge($css, $field->getCss());
            }
            return $css;
        }
        return $this->css;
    }

    /**
     * gets inline css
     *
     * @return string
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function renderPageInlineCSS(): string
    {
        if (count($this->getCss()) > 0) {
            $options = [
                'tag' => 'style',
                'text' => implode("\n", $this->getCss()),
            ];

            return $this->containerMake(TagElement::class, ['options' => $options]);
        }

        return '';
    }

    public function renderHeadCSS(): string
    {
        if (empty($this->head_css)) {
            return '';
        }


        $out = '';
        foreach ($this->head_css as $headStyle) {
            if (isset($headStyle['href']) && $this->isUrl($headStyle['href'])) {
                $options = [
                    'tag' => 'link',
                    'attributes' => [
                        'class' => '',
                        'rel' => 'stylesheet',
                        'href' => $headStyle['href'],
                    ] + ($headStyle['attributes'] ?? []),
                ];

                $out .= $this->containerMake(TagElement::class, ['options' => $options]);
            }
        }
        
        return $out;
    }

    /**
     * gets url for asset
     *
     * @param string $asset_path
     * @param int|null $website_id
     * @param string|null $locale
     * @return string
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function assetUrl(string $asset_path, ?int $website_id = null, ?string $locale = null): string
    {
        static $domain_prefix = null;

        if ($this->getAppRouteInfo()->isAdminRoute()) {
            $domain_prefix = $this->getWebRouter()->getUrl('frontend.root');
        } elseif (empty($domain_prefix)) {
            $domain_prefix = $this->getSiteData()->getConfigValue(self::ASSETS_DOMAIN_PATH, $website_id, $locale);
            if (empty($domain_prefix)) {
                $domain_prefix = $this->getWebRouter()->getUrl('frontend.root');
            }
        }

        // check if file is found, add query string to avoid browser caching
        $filesystemPath = App::getDir(App::WEBROOT).DIRECTORY_SEPARATOR.str_replace("/", DIRECTORY_SEPARATOR, $asset_path);
        if (file_exists($filesystemPath)) {
            $mtime = filemtime($filesystemPath);
            $parsed = parse_url(rtrim($domain_prefix, "/") . "/" . ltrim($asset_path, "/"));
            if (is_array($parsed) && isset($parsed['scheme'], $parsed['host'], $parsed['path'])) {
                parse_str($parsed['query'] ?? "", $existingQuery);
                return $parsed['scheme'].'://'.$parsed['host'].$parsed['path'].'?'.http_build_query($existingQuery + ['_ver' => $mtime]);
            }
        }

        return rtrim($domain_prefix, "/") . "/" . ltrim($asset_path, "/");
    }

    protected function isUrl($string) : bool 
    {
        return filter_var($string, FILTER_VALIDATE_URL) !== false;
    }
}
