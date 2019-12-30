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
namespace App\Base\Tools\Assets;

use \App\Base\Abstracts\ContainerAwareObject;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Request;
use \App\Site\Models\Menu;
use \App\Site\Models\Block;
use \App\Site\Models\Rewrite;
use \App\Site\Models\MailLog;
use \App\Site\Models\RequestLog;
use \App\Site\Routing\RouteInfo;
use \App\Base\Abstracts\BasePage;
use \App\Base\Abstracts\Model;
use \LessQL\Row;
use \Swift_Message;
use \Exception;
use \Degami\PHPFormsApi\Accessories\TagElement;

/**
 * Assets manager
 */
class Manager extends ContainerAwareObject
{
    const ASSETS_DOMAIN_PATH = 'app/frontend/assets_domain';

    /** @var boolean js already generated flag */
    protected $js_generated = false;

    /** @var boolean head js already generated flag */
    protected $head_js_generated = false;

    /** @var array head js */
    protected $head_js = ['encapsulated' => [], 'direct' => []];

    /** @var array js */
    protected $js = ['encapsulated' => [], 'direct' => []];

    /** @var array css */
    protected $css = [];

    /**
     * gets js elements
     * @return array
     */
    public function getJs()
    {
        return $this->js;
    }

    /**
     * gets head js
     * @return array
     */
    public function getHeadJs()
    {
        return $this->head_js;
    }

    /**
     * add js to element
     * @param string / array $js    javascript to add
     * @param boolean        $as_is no "minification"
     * @param string         $position
     * @param boolean        $on_ready
     * @return Element
     */
    public function addJs($js, $as_is = false, $position = null, $on_ready = true)
    {
        if (!$as_is) {
            if (is_array($js)) {
                $js = array_filter(array_map(['minify_js', $this], $js));
            } elseif (is_string($js) && trim($js) != '') {
                $js = $this->minifyJs($js);
            }
        }

        $target = 'js';
        if (strtolower(trim($position)) == 'head') {
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
     * generate the js string
     * @param string $position
     * @return string the js into a jquery sandbox
     */
    protected function generateJs($position = null)
    {
        $js = [];
        switch ($position) {
            case 'head':
                $position = 'head_';
                $js = $this->getHeadJs();
                break;
            default:
                $position = '';
                $js = $this->getJs();
                break;
        }

        if (!empty($js) && !$this->{trim($position)."js_generated"}) {
            foreach (['encapsulated', 'direct'] as $section) {
                $js[$section] = array_filter(array_map('trim', $js[$section]));
                foreach ($js[$section] as &$js_string) {
                    if ($js_string[strlen($js_string)-1] == ';') {
                        $js_string = substr($js_string, 0, strlen($js_string)-1);
                    }
                }
            }

            $this->{trim($position)."js_generated"} = true;
            return $this->encapsulateJs($js['encapsulated']).implode(";\n", array_filter(array_map("trim", $js['direct'])));
        }
        return "";
    }


    /**
     * generate the js string
     * @return string the js into a jquery sandbox
     */
    protected function generateHeadJs()
    {
        return $this->generateJs('head');
    }


    /**
     * generate the js string
     * @return string the js into a jquery sandbox
     */
    protected function generatePageJs()
    {
        return $this->generateJs();
    }

    /**
     * encapsulate js into jquery ready function
     * @param  array $js_array
     * @param  string $jquery_var_name
     * @return string
     */
    protected function encapsulateJs($js_array, $jquery_var_name = 'jQuery')
    {
        if (!is_array($js_array)) {
            $js_array = [$js_array];
        }

        $js_array = array_filter(array_map('trim', $js_array));

        return count($js_array) ? "(function(\$){\n".
        "\t$(document).ready(function(){\n".
        "\t\t".implode(";\n\t\t", $js_array).";\n".
        "\t});\n".
        "})({$jquery_var_name});" : '';
    }

    /**
     * minify js string
     * @param  string $js javascript minify
     * @return string
     */
    protected function minifyJs($js)
    {
        if (is_string($js) && trim($js) != '') {
            $js = trim(preg_replace("/\s+/", " ", str_replace("\n", "", "". $js)));
        }

        return $js;
    }

    /**
     * gets inline js
     * @return string
     */
    public function renderPageInlineJS()
    {
        $output = '';
        $js = $this->generatePageJs();
        if (!empty($js)) {
            $output .= "\n<script type=\"text/javascript\">\n".$js."\n</script>\n";
        }

        return $output;
    }

    /**
     * gets inline js for head
     * @return string
     */
    public function renderHeadInlineJS()
    {
        $output = '';
        $js = $this->generateHeadJs();
        if (!empty($js)) {
            $output .= "\n<script type=\"text/javascript\">\n".$js."\n</script>\n";
        }

        return $output;
    }

    /**
     * Add css to element
     * @param  string / array $css css to add
     * @return Element
     */
    public function addCss($css)
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
     * Get the element's css array
     * @return array element's css array
     */
    public function getCss()
    {
        if ($this instanceof FieldsContainer || $this instanceof Form) {
            $css = array_filter(array_map('trim', $this->css));
            foreach ($this->getFields() as $field) {
                /** @var \Degami\PHPFormsApi\Abstracts\App\Base\Field $field */
                $css = array_merge($css, $field->getCss());
            }
            return $css;
        }
        return $this->css;
    }

    /**
     * gets inline css
     * @return string
     */
    public function renderPageInlineCSS()
    {
        $output = '';
        if (count($this->getCss()) > 0) {
            $output .= '<style>'.implode("\n", $this->getCss())."</style>";
        }

        return $output;
    }

    /**
     * gets url for asset
     * @param  string $asset_path
     * @param  integet $website_id
     * @param  string $locale
     * @return string
     */
    public function assetUrl($asset_path, $website_id = null, $locale = null)
    {
        static $domain_prefix = null;

        if ($this->getApp()->getRouteInfo()->isAdminRoute()) {
            $domain_prefix = $this->getRouting()->getUrl('frontend.root');
        } elseif (empty($domain_prefix)) {
            $domain_prefix = $this->getSiteData()->getConfigValue(self::ASSETS_DOMAIN_PATH, $website_id, $locale);
            if (empty($domain_prefix)) {
                $domain_prefix = $this->getRouting()->getUrl('frontend.root');
            }
        }

        return rtrim($domain_prefix, "/") . "/" . ltrim($asset_path, "/");
    }
}