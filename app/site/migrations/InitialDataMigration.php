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

namespace App\Site\Migrations;

use App\App;
use \App\Base\Abstracts\Migrations\BaseMigration;
use App\Base\Abstracts\Models\BaseModel;
use App\Base\Exceptions\InvalidValueException;
use App\Site\Models\Configuration;
use App\Site\Models\Language;
use App\Site\Models\Page;
use App\Site\Models\Permission;
use App\Site\Models\Role;
use App\Site\Models\RolePermission;
use App\Site\Models\User;
use App\Site\Models\Website;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;

/**
 * basic data migration
 * @package App\Site\Migrations
 */
class InitialDataMigration extends BaseMigration
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName(): string
    {
        return '100_' . parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @return void
     * @throws BasicException
     * @throws InvalidValueException
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function up()
    {
        $this->addRolesPermissions();
        $this->addLanguages();

        $website = $this->addWebsite();
        $admin = $this->addAdmin();
        $home_page = $this->addHomePage($website, $admin);
        $this->addVariables($website, $home_page);
    }

    /**
     * adds website model
     *
     * @return Website
     * @throws BasicException
     * @throws InvalidValueException
     */
    private function addWebsite(): Website
    {
        $website_model = Website::new($this->getContainer());
        $website_model->setSiteName($this->getEnv('APPNAME'));

        $site_domain = ltrim(strtolower(preg_replace("/https?:\/\//i", "", trim($this->getEnv('APPDOMAIN')))), 'www.');
        $website_model->setDomain($site_domain);
        $website_model->setAliases('www.' . $site_domain);
        $website_model->setDefaultLocale('en');

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
    private function addAdmin(): BaseModel
    {
        $admin_model = User::new($this->getContainer());
        $admin_model->setUsername($this->getEnv('ADMIN_USER'));
        $admin_model->setNickname($this->getEnv('ADMIN_USER'));
        $admin_model->setPassword($this->getUtils()->getEncodedPass($this->getEnv('ADMIN_PASS')));
        $admin_model->setEmail($this->getEnv('ADMIN_EMAIL'));
        $admin_model->setLocale('en');

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
    private function addPermission(Role $role_model, string $permission_name)
    {
        /** @var Permission $permission_model */
        $permission_model = null;
        try {
            $permission_model = $this->getContainer()->call([Permission::class, 'loadBy'], ['field' => 'name', 'value' => $permission_name]);
        } catch (\Exception $e) {
            $permission_model = $this->getContainer()->call([Permission::class, 'new'], ['initial_data' => ['name' => $permission_name]]);
            $permission_model->persist();
        }

        $pivot_model = RolePermission::new($this->getContainer());
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
    private function addRolesPermissions()
    {
        $guest_role_model = Role::new($this->getContainer());
        $guest_role_model->setName('guest');
        $guest_role_model->persist();

        $logged_role_model = Role::new($this->getContainer());
        $logged_role_model->setName('logged_user');
        $logged_role_model->persist();

        $admin_role_model = Role::new($this->getContainer());
        $admin_role_model->setName('admin');
        $admin_role_model->persist();

        // base permissions
        $permissions = ['view_site'];
        foreach ($permissions as $permission_name) {
            $this->addPermission($guest_role_model, $permission_name);
            $this->addPermission($logged_role_model, $permission_name);
            $this->addPermission($admin_role_model, $permission_name);
        }

        // base logged permissions
        $permissions = ['view_logged_site'];
        foreach ($permissions as $permission_name) {
            $this->addPermission($logged_role_model, $permission_name);
            $this->addPermission($admin_role_model, $permission_name);
        }

        // admin only permissions
        $permissions = [
            'administer_site',
            'administer_users',
            'administer_permissions',
            'administer_pages',
            'administer_medias',
            'administer_languages',
            'administer_menu',
            'administer_taxonomy',
            'administer_blocks',
            'administer_rewrites',
            'administer_cron',
            'administer_contact',
            'administer_logs',
            'administer_links',
            'administer_queue',
            'administer_news',
            'administer_sitemaps',
        ];
        foreach ($permissions as $permission_name) {
            $this->addPermission($admin_role_model, $permission_name);
        }
    }

    /**
     * adds languages
     *
     * @throws BasicException
     * @throws InvalidValueException
     */
    private function addLanguages()
    {
        $fd = fopen("app/base/tools/iso_639-1.csv", "r");
        $header = null;
        while ($row = fgetcsv($fd)) {
            if ($header == null) {
                $header = $row;
            } else {
                $lang = array_combine($header, $row);

                $lang_model = Language::new($this->getContainer());
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
     * adds homepage model
     *
     * @param Website $website_model
     * @param User $owner_model
     * @return Page
     * @throws BasicException
     */
    private function addHomePage(Website $website_model, User $owner_model): Page
    {
        /** @var Page $page_model */
        $page_model = $this->getContainer()->call([Page::class, 'new']);

        $page_model->setWebsiteId($website_model->getId());
        $page_model->setUrl('homepage');
        $page_model->setTitle($this->getEnv('APPNAME') . ' home');
        $page_model->setLocale($website_model->getDefaultLocale());
        $page_model->setContent('');
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
    private function addVariables(Website $website_model, Page $homePage)
    {
        foreach ([
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
                     'app/frontend/assets_domain' => ['locale' => null, 'value' => 'http://' . $website_model->getDomain()],
                     'app/frontend/date_format' => ['locale' => $website_model->getDefaultLocale(), 'value' => 'Y-m-d'],
                 ] as $path => $info) {
            $configuration_model = Configuration::new($this->getContainer());
            $configuration_model->setWebsiteId($website_model->getId());
            $configuration_model->setLocale($info['locale']);
            $configuration_model->setPath($path);
            $configuration_model->setValue($info['value']);
            $configuration_model->setIsSystem(1);
            $configuration_model->persist();
        }
    }

    /**
     * {@inheritdocs}
     *
     * @return void
     */
    public function down()
    {
    }
}
