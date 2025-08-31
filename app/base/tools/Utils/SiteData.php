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

namespace App\Base\Tools\Utils;

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Models\Block;
use App\Base\Models\Configuration;
use App\Base\Models\Menu;
use App\Base\Models\Redirect;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use Exception;
use HaydenPierce\ClassFinder\ClassFinder;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use App\Base\Models\Website;
use App\Base\Controllers\Admin\Json\ChatGPT;
use App\Base\Controllers\Admin\Json\GoogleGemini;
use App\Base\Controllers\Admin\Json\Claude;
use App\Base\Controllers\Admin\Json\Mistral;

/**
 * Site Data Helper Class
 */
class SiteData extends ContainerAwareObject
{
    public const LOCALES_PATH = 'app/frontend/langs';
    public const HOMEPAGE_ID_PATH = 'app/frontend/homepage';
    public const HOMEPAGE_REDIRECTS_TO_LANGUAGE_PATH = 'app/frontend/homepage_redirects_to_language';
    public const MAINMENU_PATH = 'app/frontend/main_menu';
    public const SITE_EMAIL_PATH = 'app/global/site_mail_address';
    public const CONFIGURATION_CACHE_KEY = 'site.configuration';
    public const MENU_LOGO_PATH = 'app/frontend/menu_with_logo';
    public const THEMENAME_PATH = 'app/frontend/themename';
    public const DEFAULT_LOCALE = 'en';
    public const DATE_FORMAT_PATH = 'app/frontend/date_format';
    public const DATE_TIME_FORMAT_PATH = 'app/frontend/date_time_format';

    /**
     * gets current server name
     *
     * @return string|null
     */
    public function currentServerName(): ?string
    {
        return $_SERVER['HTTP_HOST'] ?: $_SERVER['SERVER_NAME'];
    }

    /**
     * gets current website id
     *
     * @return Website|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentWebsite(): ?Website
    {        
        if ($this->getAppWebsite() instanceof Website && $this->getAppWebsite()->isLoaded()) {
            return $this->getAppWebsite();
        }

        /** @var DebugBar $debugbar */
        $debugbar = $this->getDebugbar();

        $measure_key = 'SiteData getCurrentWebsite';

        if (getenv('DEBUG')) {
            $debugbar['time']->startMeasure($measure_key);
        }

        $website = null;
        if (Globals::isCliServer() || Globals::isCli()) {
            $website = $this->containerCall([Website::class, 'load'], ['id' => getenv('website_id')]);
        } else {
            $website = Website::getCollection()->where('domain = ' . $this->getDb()->quote($this->currentServerName()) . ' OR (FIND_IN_SET(' . $this->getDb()->quote($this->currentServerName()) . ', aliases) > 0)')->getFirst();
        }

        if (getenv('DEBUG')) {
            $debugbar['time']->stopMeasure($measure_key);
        }

        if ($website instanceof Website) {
            // register into container
            $this->getContainer()->set(Website::class, $website);

            return $website;
        }

        return null;
    }

    /**
     * gets current website id
     *
     * @return int|string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getCurrentWebsiteId(): int|string|null
    {
        static $current_site_id = null;

        if (is_numeric($current_site_id)) {
            return $current_site_id;
        }

        $website = $this->getCurrentWebsite();

        if ($website instanceof Website) {
            return $current_site_id = $website->getId();
        }

        return null;
    }

    /**
     * gets default site locale
     *
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDefaultLocale(): ?string
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
     * @return string|null
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getBrowserPreferredLanguage(): ?string
    {
        $languages = [];
        $lang = null;
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            // break up string into pieces (languages and q factors)
            preg_match_all(
                '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                $lang_parse
            );
            if (count($lang_parse[1])) {
                // create a list like "en" => 0.8
                $languages = array_combine($lang_parse[1], $lang_parse[4]);
                // set default to 1 for any without q factor
                foreach ($languages as $lang => $val) {
                    if ($val === '') {
                        $languages[$lang] = 1;
                    }
                }
                // sort list based on value
                arsort($languages, SORT_NUMERIC);
            }
        }

        if (count($languages) > 1) {
            //extract most important (first)
            $lang = array_keys($languages)[0];

            //if complex language simplify it
            if (stristr($lang, "-")) {
                $lang = explode("-", $lang)[0];
            }
        }

        if (!in_array($lang, $this->getSiteLocales()) || empty($lang)) {
            $lang = $this->getDefaultLocale();
        }

        return $lang;
    }

    /**
     * loads configuration
     *
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function preloadConfiguration(): array
    {
        $configuration = [];
        foreach (Configuration::getCollection() as $result) {
            /** @var Configuration $result */
            $configuration[$result->getWebsiteId()][$result->getPath()][$result->getLocale() ?? 'default'] = $result->getValue();
        }
        $this->getCache()->set(SiteData::CONFIGURATION_CACHE_KEY, $configuration);

        return $configuration;
    }

    /**
     * get cached config
     *
     * @return array
     * @throws PhpfastcacheSimpleCacheException
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws BasicException
     */
    public function getCachedConfig(): array
    {
        if ($this->getCache()->has(self::CONFIGURATION_CACHE_KEY)) {
            return (array)$this->getCache()->get(self::CONFIGURATION_CACHE_KEY);
        }

        return [];
    }

    /**
     * gets config value
     *
     * @param string $config_path
     * @param int|null $website_id
     * @param string|null $locale
     * @return mixed
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getConfigValue(string $config_path, ?int $website_id = null, ?string $locale = null): mixed
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

        try {
            $result = $this->containerCall([Configuration::class, 'loadByCondition'], ['condition' => ['path' => $config_path, 'website_id' => $website_id, 'locale' => array_unique([$locale, null])]]);
            if ($result instanceof Configuration) {
                $cached_configuration[$website_id][$config_path][$result->getLocale() ?? 'default'] = $result->getValue();
                $this->getCache()->set(self::CONFIGURATION_CACHE_KEY, $cached_configuration);
                return $result->getValue();
            }
        } catch (Exception $e) {
        }

        return null;
    }

    /**
     * sets config value
     *
     * @param string $config_path
     * @param int|null $website_id
     * @param string|null $locale
     * @return bool
     */
    public function setConfigValue(string $config_path, mixed $value, ?int $website_id = null, ?string $locale = null) : bool
    {

        if ($website_id == null) {
            $website_id = $this->getCurrentWebsiteId();
        }
/*
        if ($locale == null && $this->getContainer()->has('app')) {
            $locale = $this->getApp()->getCurrentLocale();
        }

        if ($locale == null) {
            $locale = static::DEFAULT_LOCALE;
        }
*/
        try {
            /** @var Configuration $result */
            $result = $this->containerCall([Configuration::class, 'loadByCondition'], ['condition' => ['path' => $config_path, 'website_id' => $website_id, 'locale' => array_unique([$locale, null])]]);
            if ($result instanceof Configuration) {
                $result->setValue($value);
                $result->persist();
            }
        } catch (Exception $e) {
//            return false;
            /** @var Configuration $result */
            $result = $this->containerMake(Configuration::class);
            $result->setPath($config_path)->setWebsiteId($website_id)->setLocale($locale);
        }

        try {
            $result->setValue($value);
            $result->persist();
        } catch (Exception $e) {
            return false;
        }


        // refresh configuration
        $this->getCache()->delete(self::CONFIGURATION_CACHE_KEY);
        $this->preloadConfiguration();

        return true;
    }

    /**
     * gets homepage page id
     *
     * @param null $website_id
     * @param null $locale
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getHomePageId(?int $website_id = null, ?string $locale = null): mixed
    {
        if ($locale == null) {
            $locale = static::DEFAULT_LOCALE;
        }

        return $this->getConfigValue(self::HOMEPAGE_ID_PATH, $website_id, $locale);
    }

    /**
     * gets homepage redirects to default language preference
     *
     * @param null $website_id
     * @return bool
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getHomePageRedirectsToLanguage(?int $website_id = null): bool
    {
        return boolval($this->getConfigValue(self::HOMEPAGE_REDIRECTS_TO_LANGUAGE_PATH, $website_id));
    }

    /**
     * gets show logo in menu preference
     *
     * @param null $website_id
     * @return bool
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getShowLogoOnMenu(?int $website_id = null): bool
    {
        return boolval($this->getConfigValue(self::MENU_LOGO_PATH, $website_id));
    }

    /**
     * gets website email address
     *
     * @param null $website_id
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getSiteEmail(?int $website_id = null): mixed
    {
        return $this->getConfigValue(self::SITE_EMAIL_PATH, $website_id, null);
    }

    /**
     * gets site enabled locales
     *
     * @param null $website_id
     * @return bool|array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSiteLocales(?int $website_id = null): array|bool
    {
        return explode(",", $this->getConfigValue(self::LOCALES_PATH, $website_id, null));
    }

    /**
     * gets main menu name
     *
     * @param null $website_id
     * @param null $locale
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getMainMenuName(?int $website_id = null, ?string $locale = null): mixed
    {
        return $this->getConfigValue(self::MAINMENU_PATH, $website_id, $locale);
    }

    /**
     * gets main menu name
     *
     * @param null $website_id
     * @param null $locale
     * @return mixed
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getThemeName(?int $website_id = null, ?string $locale = null): mixed
    {
        return $this->getConfigValue(self::THEMENAME_PATH, $website_id, $locale);
    }

    /**
     * gets date format string
     *
     * @param null $website_id
     * @param null $locale
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDateFormat(?int $website_id = null, ?string $locale = null): string
    {
        $date_format = $this->getSiteData()->getConfigValue(self::DATE_FORMAT_PATH, $website_id, $locale);
        return $date_format ?: 'Y-m-d';
    }

    /**
     * gets date format string
     *
     * @param null $website_id
     * @param null $locale
     * @return string
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getDateTimeFormat(?int $website_id = null, ?string $locale = null): string
    {
        $date_format = $this->getSiteData()->getConfigValue(self::DATE_TIME_FORMAT_PATH, $website_id, $locale);
        return $date_format ?: 'Y-m-d H:i';
    }

    /**
     * gets defined redirects
     *
     * @param int $current_website_id
     * @param bool $reset
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getRedirects(int $current_website_id, bool $reset = false): array
    {
        $redirects = [];
        $redirects_key = "site." . $current_website_id . ".redirects";
        if (!$this->getCache()->has($redirects_key) || $reset) {
            foreach (Redirect::getCollection()->where(['website_id' => $current_website_id]) as $redirect_model) {
                $redirects[$redirect_model->getUrlFrom()] = [
                    'url_to' => $redirect_model->getUrlTo(),
                    'redirect_code' => $redirect_model->getRedirectCode(),
                ];
            }
            $this->getCache()->set($redirects_key, $redirects);
        } elseif ($current_website_id) {
            $redirects = $this->getCache()->get($redirects_key);
        }

        return $redirects;
    }

    /**
     * get page regions list
     *
     * @return array
     * @throws BasicException
     */
    public function getPageRegions(): array
    {
        return array_filter(array_map('trim', explode(",", $this->getEnv('PAGE_REGIONS', 'menu,header,content,footer'))));
    }

    /**
     * gets available block regions
     *
     * @return array
     * @throws BasicException
     */
    public function getBlockRegions(): array
    {
        $out = [
            '' => '',
            'after_body_open' => 'After Body-Open',
            'before_body_close' => 'Before Body-Close',
        ];

        foreach ($this->getPageRegions() as $region) {
            $out['pre_' . $region] = 'Pre-' . ucfirst(strtolower($region));
            $out['post_' . $region] = 'Post-' . ucfirst(strtolower($region));
        }

        return $out;
    }

    /**
     * gets all blocks for current locale
     *
     * @param string|null $locale
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getAllPageBlocks(?string $locale = null): array
    {
        static $pageBlocks = null;

        if (is_null($pageBlocks)) {
            $website_id = $this->getSiteData()->getCurrentWebsiteId();

            $pageBlocks = [];
            foreach (Block::getCollection()->where(['locale' => [$locale, null], 'website_id' => [$website_id, null]], ['order' => 'asc']) as $block) {
                /** @var Block $block */
                if (!isset($pageBlocks[$block->getRegion()])) {
                    $pageBlocks[$block->getRegion()] = [];
                }
                $block->loadInstance();
                $pageBlocks[$block->getRegion()][] = $block;
            }
        }

        return $pageBlocks;
    }

    /**
     * returns site menu
     *
     * @param string $menu_name
     * @param int $website_id
     * @param string $locale
     * @param Menu|null $menu_element
     * @param bool $absoluteLinks
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getSiteMenu(string $menu_name, int $website_id, string $locale, ?Menu $menu_element = null, bool $absoluteLinks = true): array
    {
        $out = [];
        if ($menu_element instanceof Menu) {
            $out = $this->menuElementToArray($menu_element, $absoluteLinks);

            foreach ($menu_element->getChildren($locale) as $child) {
                $out['children'][] = $this->getSiteMenu($menu_name, $website_id, $locale, $child, $absoluteLinks);
            }
        } else {
            $out = array_map(
                function ($menu_model) use ($menu_name, $website_id, $locale, $absoluteLinks) {
                    /** @var Menu $menu_model */
                    return $this->getSiteMenu($menu_name, $website_id, $locale, $menu_model, $absoluteLinks);
                },
                Menu::getCollection()->where(['menu_name' => $menu_name, 'website_id' => $website_id, 'parent_id' => null, 'locale' => [$locale, null]], ['position' => 'asc'])->getItems()
            );
        }
        return $out;
    }

    /**
     * returns site menu
     *
     * @param array $menu_items
     * @param Menu|null $menu_element
     * @param bool $absoluteLinks
     * @return array
     * @throws BasicException
     */
    public function buildSiteMenu(array $menu_items, ?Menu $menu_element = null, bool $absoluteLinks = true): array
    {
        $out = [];
        if ($menu_element instanceof Menu) {
            $out = $this->menuElementToArray($menu_element, $absoluteLinks);

            foreach ($menu_items as $child) {
                /** @var Menu $child */
                if ($child->getParentId() == $menu_element->getId()) {
                    $out['children'][] = $this->buildSiteMenu($menu_items, $child, $absoluteLinks);
                }
            }
        } else {
            foreach ($menu_items as $item) {
                /** @var Menu $item */
                if ($item->getParentId() == null) {
                    $out[] = $this->buildSiteMenu($menu_items, $item, $absoluteLinks);
                }
            }
        }
        return $out;
    }

    /**
     * converts a menu element to array
     *
     * @param Menu $menu_element
     * @param boolean $absolute
     * @return array
     * @throws BasicException
     */
    protected function menuElementToArray(Menu $menu_element, bool $absolute = true): array
    {
        return [
            'id' => $menu_element->getId(),
            'parent_id' => $menu_element->getParentId(),
            'title' => $menu_element->getTitle(),
            'href' => $menu_element->getLinkUrl($absolute),
            'target' => $menu_element->getTarget(),
            'internal_route' => $menu_element->getInternalRoute(),
            'breadcrumb' => $menu_element->getBreadcrumb(),
            'rewrite_id' => $menu_element->getRewriteId(),
            'locale' => $menu_element->getLocale(),
            'level' => $menu_element->getLevel(),
            'children' => [],
        ];
    }

    public function getAdminSidebarMenu(bool $reset = false) : array
    {
        $links = [];

        $admin_links_key = "admin.links";
        if (!$this->getCache()->has($admin_links_key) || $reset) {
            $controllerClasses = array_unique(array_merge(
                ClassFinder::getClassesInNamespace(App::BASE_CONTROLLERS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
                ClassFinder::getClassesInNamespace(App::CONTROLLERS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ));
            foreach ($controllerClasses as $controllerClass) {
                if (method_exists($controllerClass, 'getAdminPageLink')) {
                    $adminLink = $this->containerCall([$controllerClass, 'getAdminPageLink']) ?? null;
                    if ($adminLink) {
                        $links[$adminLink['section']][] = $adminLink;
                    }
                }
            }
    
            $this->getCache()->set($admin_links_key, $links);
        } else {
            $links = $this->getCache()->get($admin_links_key);
        }

        foreach ($links as $sectionName => $sectionLinks) {
            if (empty($sectionLinks)) {
                unset($links[$sectionName]);
                continue;
            }

            usort($links[$sectionName], function ($a, $b) {
                if (isset($a['order']) && isset($b['order'])) {
                    return $a['order'] <=> $b['order'];
                }
                return ($a['text'] ?? '') <=> ($b['text'] ?? '');
            });
        }

        ksort($links);
        return $links;
    }

    public function getAdminSidebarVisibleLinks(AdminPage $controller)
    {
        $links = $this->getAdminSidebarMenu();

        foreach ($links as $sectionName => $sectionLinks) {
            $sectionLinks = array_filter(array_map(function ($link) use ($controller) {
                if (empty($link['permission_name']) || $controller->checkPermission($link['permission_name'])) {
                    return $link;
                }
                return false;
            }, $sectionLinks));
        
            if (empty($sectionLinks)) {
                unset($links[$sectionName]);
            }
        }

        return $links;
    }

    public function getAvailableAIs(bool $withNames = false) : array
    {
        $AIs = [
            'googlegemini' => GoogleGemini::getModelName(), 
            'chatgpt' => ChatGPT::getModelName(), 
            'claude' => Claude::getModelName(), 
            'mistral' => Mistral::getModelName()
        ];

        if ($withNames) {
            return $AIs;
        }

        return array_keys($AIs);
    }

    public function isAiAvailable(string|array|null $ai = null): bool
    {
        if (is_array($ai)) {
            $out = true;
            $ai = array_intersect(array_map('strtolower', $ai), $this->getAvailableAIs());
            if (empty($ai)) {
                return false;
            }

            foreach($ai as $aiElem) {
                if ($aiElem == 'googlegemini' && !GoogleGemini::isEnabled()) {
                    $out &= false;
                }
                if ($aiElem == 'chatgpt' && !ChatGPT::isEnabled()) {
                    $out &= false;
                }
                if ($aiElem == 'claude' && !Claude::isEnabled()) {
                    $out &= false;
                }
                if ($aiElem == 'mistral' && !Mistral::isEnabled()) {
                    $out &= false;
                }
            }
            return $out;
        } elseif (is_string($ai)) {
            if ($ai == 'googlegemini') {
                return GoogleGemini::isEnabled();
            } elseif ($ai == 'chatgpt') {
                return ChatGPT::isEnabled();
            } elseif ($ai == 'claude') {
                return Claude::isEnabled();
            } elseif ($ai == 'mistral') {
                return Mistral::isEnabled();
            }
            return false;
        }

        return GoogleGemini::isEnabled() || ChatGPT::isEnabled() || Claude::isEnabled() || Mistral::isEnabled();
    }
}
