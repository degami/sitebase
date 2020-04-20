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
namespace App\Base\Tools\Utils;

use \App\Base\Abstracts\ContainerAwareObject;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Request;
use \App\Site\Models\Menu;
use \App\Site\Models\Block;
use \App\Site\Models\Rewrite;
use \App\Site\Models\MailLog;
use \App\Site\Models\RequestLog;
use \App\Site\Models\Website;
use \App\Site\Routing\RouteInfo;
use \App\Base\Abstracts\Controllers\BasePage;
use \App\Base\Abstracts\Models\BaseModel;
use \LessQL\Row;
use \Swift_Message;
use \Exception;

/**
 * Site Data Helper Class
 */
class SiteData extends ContainerAwareObject
{
    const LOCALES_PATH = 'app/frontend/langs';
    const HOMEPAGE_ID_PATH = 'app/frontend/homepage';
    const HOMEPAGE_REDIRECTS_TO_LANGUAGE_PATH = 'app/frontend/homepage_redirects_to_language';
    const MAINMENU_PATH = 'app/frontend/main_menu';
    const SITE_EMAIL_PATH = 'app/global/site_mail_address';
    const CONFIGURATION_CACHE_KEY = 'site.configuration';
    const MENU_LOGO_PATH = 'app/frontend/menu_with_logo';
    const THEMENAME_PATH = 'app/frontend/themename';
    const DEFAULT_LOCALE = 'en';

    /**
     * gets current website id
     *
     * @return Website|null
     */
    public function getCurrentWebsite()
    {
        static $current_website = null;

        if (is_numeric($current_website)) {
            return $current_website;
        }

        $website = null;
        if (php_sapi_name() == 'cli-server' || php_sapi_name() == 'cli') {
            $website = $this->getContainer()->call([Website::class, 'load'], ['id' => getenv('website_id')]);
        } else {
            //$website = $this->getContainer()->call([Website::class, 'loadBy'], ['field' => 'domain', 'value' => $_SERVER['SERVER_NAME']]);
            $result = $this->getContainer()->call([Website::class, 'select'], ['options' => ['where' => ['domain = '.$this->getDb()->quote($_SERVER['SERVER_NAME']).' OR (FIND_IN_SET('.$this->getDb()->quote($_SERVER['SERVER_NAME']).', aliases) > 0)']]])->fetch();
            $dbrow = $this->getContainer()->make(Row::class, ['db' => $this->getDb(), 'name' => 'website', 'properties' => $result]);
            $website = $this->getContainer()->make(Website::class, ['dbrow' => $dbrow]);
        }

        if ($website instanceof Website) {
            return $current_website = $website;
        }

        return null;
    }

    /**
     * gets current website id
     *
     * @return integer|null
     */
    public function getCurrentWebsiteId()
    {
        static $current_siteid = null;

        if (is_numeric($current_siteid)) {
            return $current_siteid;
        }

        $website = $this->getCurrentWebsite();

        if ($website instanceof Website) {
            return $current_siteid = $website->id;
        }

        return null;
    }

    /**
     * gets default site locale
     *
     * @return string|null
     */
    public function getDefaultLocale()
    {
        static $website_default_locale = null;

        if (!is_null($website_default_locale)) {
            return $website_default_locale;
        }

        $website = $this->getCurrentWebsite();

        if ($website instanceof Website) {
            return $website_default_locale = $website->getDefaultLocale();
        }

        return null;
    }

    /**
     * gets preferred language by browser configuration
     *
     * @return string
     */
    public function getBrowserPreferredLanguage()
    {
        $langs = [];
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            preg_match_all(
                '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                $lang_parse
            );
            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $langs = array_combine($lang_parse[1], $lang_parse[4]);
                // set default to 1 for any without q factor
                foreach ($langs as $lang => $val) {
                    if ($val === '') {
                        $langs[$lang] = 1;
                    }
                }
                // sort list based on value
                arsort($langs, SORT_NUMERIC);
            }
        }

        //extract most important (first)
        $lang = array_keys($langs)[0];

        //if complex language simplify it
        if (stristr($lang, "-")) {
            $lang = explode("-", $lang)[0];
        }

        if (!in_array($lang, $this->getSiteLocales()) || empty($lang)) {
            $lang = $this->getDefaultLocale();
        }

        return $lang;
    }


    /**
     * get cached config
     *
     * @return array
     */
    public function getCachedConfig()
    {
        if ($this->getCache()->has(self::CONFIGURATION_CACHE_KEY)) {
            return (array)$this->getCache()->get(self::CONFIGURATION_CACHE_KEY);
        }

        return [];
    }

    /**
     * gets config value
     *
     * @param  string  $config_path
     * @param  integer $website_id
     * @param  string  $locale
     * @return mixed
     */
    public function getConfigValue($config_path, $website_id = null, $locale = null)
    {
        if ($website_id == null) {
            $website_id = $this->getCurrentWebsiteId();
        }

        if ($locale == null && $this->getContainer()->has('app')) {
            $locale = $this->getApp()->getCurrentLocale();
        }

        if ($locale == null) {
            $locale = static::DEFAULT_LOCALE;
        }

        $cached_configuration = $this->getCachedConfig();
        if (isset($cached_configuration[$website_id][$config_path][$locale])) {
            return $cached_configuration[$website_id][$config_path][$locale];
        }
        if (isset($cached_configuration[$website_id][$config_path]['default'])) {
            return $cached_configuration[$website_id][$config_path]['default'];
        }

        $result = $this->getDb()->table('configuration')->where(['path'=> $config_path, 'website_id' => $website_id, 'locale' => array_unique([$locale, null])])->fetch();
        if ($result instanceof Row) {
            $cached_configuration[$website_id][$config_path][$result->locale ?? 'default'] = $result->value;
            $this->getCache()->set(self::CONFIGURATION_CACHE_KEY, $cached_configuration);
            return $result->value;
        }

        return null;
    }

    /**
     * gets homepage page id
     *
     * @param  integer $website_id
     * @param  string  $locale
     * @return integer
     */
    public function getHomePageId($website_id = null, $locale = null)
    {
        if ($locale == null) {
            $locale = static::DEFAULT_LOCALE;
        }

        return  $this->getConfigValue(self::HOMEPAGE_ID_PATH, $website_id, $locale);
    }

    /**
     * gets homepage redirects to default language preference
     *
     * @param  integer $website_id
     * @return boolean
     */
    public function getHomePageRedirectsToLanguage($website_id = null)
    {
        return  boolval($this->getConfigValue(self::HOMEPAGE_REDIRECTS_TO_LANGUAGE_PATH, $website_id));
    }

    /**
     * gets show logo in menu preference
     *
     * @param  integer $website_id
     * @return boolean
     */
    public function getShowLogoOnMenu($website_id = null)
    {
        return  boolval($this->getConfigValue(self::MENU_LOGO_PATH, $website_id));
    }

    /**
     * gets website email address
     *
     * @param  integer $website_id
     * @return string
     */
    public function getSiteEmail($website_id = null)
    {
        return  $this->getConfigValue(self::SITE_EMAIL_PATH, $website_id, null);
    }

    /**
     * gets site enabled locales
     *
     * @param  integer $website_id
     * @return array
     */
    public function getSiteLocales($website_id = null)
    {
        return explode(",", $this->getConfigValue(self::LOCALES_PATH, $website_id, null));
    }

    /**
     * gets main menu name
     *
     * @param  integer $website_id
     * @param  string  $locale
     * @return string
     */
    public function getMainMenuName($website_id = null, $locale = null)
    {
        return $this->getConfigValue(self::MAINMENU_PATH, $website_id, $locale);
    }


    /**
     * gets main menu name
     *
     * @param  integer $website_id
     * @param  string  $locale
     * @return string
     */
    public function getThemeName($website_id = null, $locale = null)
    {
        return $this->getConfigValue(self::THEMENAME_PATH, $website_id, $locale);
    }
}
