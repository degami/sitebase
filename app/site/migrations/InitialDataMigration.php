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

namespace App\Site\Migrations;

use App\App;
use App\Base\Abstracts\Migrations\BaseMigration;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Exceptions\InvalidValueException;
use App\Base\Exceptions\NotFoundException as ExceptionsNotFoundException;
use App\Base\Models\Configuration;
use App\Base\Models\Language;
use App\Base\Models\Country;
use App\Site\Models\Page;
use App\Base\Models\Permission;
use App\Base\Models\Role;
use App\Base\Models\RolePermission;
use App\Base\Models\User;
use App\Base\Models\Website;
use App\Base\Tools\Utils\SiteData;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use HaydenPierce\ClassFinder\ClassFinder;

/**
 * basic data migration
 */
class InitialDataMigration extends BaseMigration
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getName(): string
    {
        return '100_' . parent::getName();
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     * @throws BasicException
     * @throws InvalidValueException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function up()
    {
        static::addRolesPermissions();
        static::addLanguages();
        static::addCountries();

        $website = static::addWebsite();
        $admin = static::addAdmin();
        $home_page = static::addHomePage($website, $website->getDefaultLocale(), $admin);
        static::addVariables($website, $home_page);
    }

    /**
     * adds website model
     *
     * @return Website
     * @throws BasicException
     * @throws InvalidValueException
     */
    public static function addWebsite(): Website
    {
        $website_model = Website::new();
        $website_model->setSiteName(App::getInstance()->getEnvironment()->getVariable('APPNAME'));

        $site_domain = ltrim(strtolower(preg_replace("/https?:\/\//i", "", trim(App::getInstance()->getEnvironment()->getVariable('APPDOMAIN')))), 'www.');
        $website_model->setDomain($site_domain);
        $website_model->setAliases('www.' . $site_domain);
        $website_model->setDefaultLocale(SiteData::DEFAULT_LOCALE);
        $website_model->setDefaultCurrencyCode('EUR');

        $website_model->persist();

        return $website_model;
    }

    /**
     * adds admin user model
     *
     * @return BaseModel
     * @throws BasicException
     * @throws InvalidValueException
     */
    public static function addAdmin(): BaseModel
    {
        $admin_model = User::new();

        $admin_user = App::getInstance()->getEnvironment()->getVariable('ADMIN_USER', $_ENV['ADMIN_USER'] ?? null);
        $admin_pass = App::getInstance()->getEnvironment()->getVariable('ADMIN_PASS', $_ENV['ADMIN_PASS'] ?? null);
        $admin_email = App::getInstance()->getEnvironment()->getVariable('ADMIN_EMAIL', $_ENV['ADMIN_EMAIL'] ?? null);

        if (empty($admin_user) || empty($admin_pass) || empty($admin_email)) {
            echo "\nmissing admin info. Using default values, please modify admin user after login.\n Admin User: \"admin\", Admin password:\"admin\", Admin email:\"admin@localhost\".\n\n";
            $admin_user = 'admin';
            $admin_pass = 'admin';
            $admin_email = 'admin@localhost';
        }

        $admin_model->setUsername($admin_user);
        $admin_model->setNickname($admin_user);
        $admin_model->setPassword(App::getInstance()->getUtils()->getEncodedPass($admin_pass));
        $admin_model->setEmail($admin_email);
        $admin_model->setLocale(SiteData::DEFAULT_LOCALE);

        $admin_model->setRole('admin');
        $admin_model->persist();

        if (file_exists(App::getDir(App::ROOT) . DS . ".env")) {
            if ($contents = file(App::getDir(App::ROOT) . DS . ".env")) {
                if ($fp = fopen(App::getDir(App::ROOT) . DS . ".env", "w")) {
                    foreach ($contents as $line) {
                        if (preg_match("/ADMIN_(EMAIL|USER|PASS)=/", $line)) {
                            continue;
                        }
                        fwrite($fp, $line);
                    }
                    fclose($fp);
                }
            }
        }

        return $admin_model;
    }

    /**
     * adds permission to role
     *
     * @param Role $role_model
     * @param string $permission_name
     * @throws BasicException
     * @throws InvalidValueException
     */
    public static function addPermission(Role $role_model, string $permission_name)
    {
        /** @var Permission $permission_model */
        $permission_model = null;
        try {
            $permission_model = Permission::getCollection()->where(['name' => $permission_name])->getFirst();
            if (!$permission_model) {
                throw new ExceptionsNotFoundException();
            }
        } catch (\Exception $e) {
            $permission_model = App::getInstance()->containerCall([Permission::class, 'new'], ['initial_data' => ['name' => $permission_name]]);
            $permission_model->persist();
        }

        $existingPivot = RolePermission::getCollection()->where([
            'permission_id' => $permission_model->getId(),
            'role_id' => $role_model->getId(),
        ])->getFirst();

        if ($existingPivot) {
            return;
        }

        $pivot_model = RolePermission::new();
        $pivot_model->setPermissionId($permission_model->getId());
        $pivot_model->setRoleId($role_model->getId());
        $pivot_model->persist();
    }

    /**
     * adds permissions and roles
     *
     * @throws BasicException
     * @throws InvalidValueException
     */
    public static function addRolesPermissions()
    {
        $guest_role_model = App::getInstance()->containerCall([Role::class, 'new']);
        $guest_role_model->setName('guest');
        $guest_role_model->persist();

        $logged_role_model = App::getInstance()->containerCall([Role::class, 'new']);
        $logged_role_model->setName('logged_user');
        $logged_role_model->persist();

        $admin_role_model = App::getInstance()->containerCall([Role::class, 'new']);
        $admin_role_model->setName('admin');
        $admin_role_model->persist();

        // base permissions
        $permissions = ['view_site'];
        foreach ($permissions as $permission_name) {
            static::addPermission($guest_role_model, $permission_name);
            static::addPermission($logged_role_model, $permission_name);
            static::addPermission($admin_role_model, $permission_name);
        }

        // base logged permissions
        $permissions = ['view_logged_site'];
        foreach ($permissions as $permission_name) {
            static::addPermission($logged_role_model, $permission_name);
            static::addPermission($admin_role_model, $permission_name);
        }

        // admin only permissions
        $permissions = static::getAdminPermissionsArray();
        foreach ($permissions as $permission_name) {
            static::addPermission($admin_role_model, $permission_name);
        }
    }

    /**
     * adds languages
     *
     * @throws BasicException
     * @throws InvalidValueException
     */
    public static function addLanguages()
    {
        $fd = fopen("app/base/tools/iso_639-1.csv", "r");
        $header = null;
        while ($row = fgetcsv($fd)) {
            if ($header == null) {
                $header = $row;
            } else {
                $lang = array_combine((array)$header, $row);

                $lang_model = Language::new();
                $lang_model->setLocale($lang['639-1']);
                $lang_model->{"639-1"} = $lang['639-1'];
                $lang_model->{"639-2"} = $lang['639-2'];
                $lang_model->setName($lang['name']);
                $lang_model->setNative($lang['nativeName']);
                $lang_model->setFamily($lang['family']);

                $lang_model->persist();
            }
        }
    }

    /**
     * adds countries
     *
     * @throws BasicException
     * @throws InvalidValueException
     */
    public static function addCountries()
    {
        $fd = fopen("app/base/tools/all_countries_with_capitals.csv", "r");
        $header = null;
        while ($row = fgetcsv($fd)) {
            if ($header == null) {
                $header = $row;
            } else {
                $country = array_combine((array)$header, $row);

                $country_model = Country::new();
                $country_model->setIso2($country['iso2']);
                $country_model->setIso3($country['iso3']);
                $country_model->setNameEn($country['name_en']);
                $country_model->setNameNative($country['name_native']);
                $country_model->setCapital($country['capital']);
                $country_model->setLongitude((float)$country['longitude']);
                $country_model->setLatitude((float)$country['latitude']);

                $country_model->persist();
            }
        }
    }

    /**
     * adds homepage model
     *
     * @param Website $website_model
     * @param User $owner_model
     * @return Page
     * @throws BasicException
     */
    public static function addHomePage(Website $website_model, string $locale, User $owner_model): Page
    {
        /** @var Page $page_model */
        $page_model = App::getInstance()->containerCall([Page::class, 'new']);

        $page_model->setWebsiteId($website_model->getId());
        $page_model->setUrl('homepage');
        $page_model->setTitle(App::getInstance()->getEnvironment()->getVariable('APPNAME') . ' home');
        $page_model->setLocale($locale);
        $page_model->setContent('<p>Welcome to '.App::getInstance()->getEnvironment()->getVariable('APPNAME').' - Empower Your Digital Presence</p>
<p>'.App::getInstance()->getEnvironment()->getVariable('APPNAME').' provides the tools you need to effortlessly create, manage, and grow your online presence. Whether you\'re building a personal blog, a business website, or a robust e-commerce platform, '.App::getInstance()->getEnvironment()->getVariable('APPNAME').' is designed with simplicity, flexibility, and performance in mind.</p>
<p>With '.App::getInstance()->getEnvironment()->getVariable('APPNAME').', you’ll enjoy a user-friendly interface, powerful customization options, and seamless integrations to enhance your website’s functionality. No coding skills? No problem. Our intuitive tools let you focus on what matters most: engaging your audience and achieving your goals.</p>
<p>Get '.App::getInstance()->getEnvironment()->getVariable('APPNAME').' to bring their ideas to life. Start building your future online today!</p>');
        $page_model->setUserId($owner_model->getId());

        $page_model->persist();
        return $page_model;
    }

    /**
     * adds configuration variables
     *
     * @param Website $website_model
     * @param Page $homePage
     * @throws BasicException
     * @throws InvalidValueException
     */
    public static function addVariables(Website $website_model, Page $homePage)
    {
        $variables = [
            'app/frontend/homepage' => ['locale' => $website_model->getDefaultLocale(), 'value' => $homePage->getId()],
            'app/frontend/homepage_redirects_to_language' => ['locale' => null, 'value' => 0],
            'app/frontend/langs' => ['locale' => null, 'value' => $website_model->getDefaultLocale()],
            'app/frontend/main_menu' => ['locale' => $website_model->getDefaultLocale(), 'value' => ''],
            'app/global/site_mail_address' => ['locale' => null, 'value' => ''],
            'app/mail/ses_sender' => ['locale' => $website_model->getDefaultLocale(), 'value' => ''],
            'app/frontend/menu_with_logo' => ['locale' => null, 'value' => 1],
            'app/backend/log_requests' => ['locale' => null, 'value' => 1],
            'app/frontend/log_requests' => ['locale' => null, 'value' => 1],
            'app/frontend/themename' => ['locale' => null, 'value' => 'theme'],
            'app/frontend/assets_domain' => ['locale' => null, 'value' => 'https://' . $website_model->getDomain()],
            'app/frontend/date_format' => ['locale' => $website_model->getDefaultLocale(), 'value' => 'Y-m-d'],
            'app/frontend/date_time_format' => ['locale' => $website_model->getDefaultLocale(), 'value' => 'Y-m-d H:i'],
            'app/versions/keep_num' => ['locale' => null, 'value' => 20],
        ];
        foreach ($variables as $path => $info) {
            $configuration_model = App::getInstance()->containerCall([Configuration::class, 'new']);
            $configuration_model->setWebsiteId($website_model->getId());
            $configuration_model->setLocale($info['locale']);
            $configuration_model->setPath($path);
            $configuration_model->setValue($info['value']);
            $configuration_model->setIsSystem(1);
            $configuration_model->persist();
        }
    }

    /**
     * {@inheritdoc}
     *
     * @return void
     */
    public function down()
    {
    }

    public static function getAdminPermissionsArray() : array 
    {
        $permissionsArray = [];
        $controllerClasses = array_unique(array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_CONTROLLERS_NAMESPACE.'\Admin', ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::CONTROLLERS_NAMESPACE.'\Admin', ClassFinder::RECURSIVE_MODE),
        ));
        foreach ($controllerClasses as $controllerClass) {
            if (is_callable([$controllerClass, 'getAccessPermission'])) {
                $permissionsArray[] = App::getInstance()->containerCall([$controllerClass, 'getAccessPermission']);
                $permissionsArray = array_unique($permissionsArray);
            }
        }

        return $permissionsArray;
    }
}
