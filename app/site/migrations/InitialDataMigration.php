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

use \App\Base\Abstracts\Migrations\BaseMigration;
use \Psr\Container\ContainerInterface;
use \Degami\SqlSchema\Index;

/**
 * basic data migration
 */
class InitialDataMigration extends BaseMigration
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    public function getName()
    {
        return '100_'.parent::getName();
    }

    /**
     * {@inheritdocs}
     *
     * @return void
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
     */
    private function addWebsite()
    {
        $website_model = \App\Site\Models\Website::new($this->getContainer());
        $website_model->site_name = $this->getEnv('APPNAME');
        $website_model->domain = $this->getEnv('APPDOMAIN');
        $website_model->default_locale = 'en';

        $website_model->persist();

        return $website_model;
    }

    /**
     * adds admin user model
     *
     * @return User
     */
    private function addAdmin()
    {
        $admin_model = \App\Site\Models\User::new($this->getContainer());
        $admin_model->username = $this->getEnv('ADMIN_USER');
        $admin_model->nickname = $this->getEnv('ADMIN_USER');
        $admin_model->password = $this->getUtils()->getEncodedPass($this->getEnv('ADMIN_PASS'));
        $admin_model->email = $this->getEnv('ADMIN_EMAIL');
        $admin_model->locale = 'en';

        $admin_model->setRole('admin');
        $admin_model->persist();

        return $admin_model;
    }

    /**
     * adds permission to role
     *
     * @param  Role   $role_model
     * @param  string $permission_name
     * @return RolePermission
     */
    private function addPermission($role_model, $permission_name)
    {
        $permission_dbrow = $this->getDb()->permission()->where(['name' => $permission_name])->fetch();
        $permission_model = \App\Site\Models\Permission::new($this->getContainer());
        if ($permission_dbrow) {
            $permission_model = $this->getContainer()->make(\App\Site\Models\Permission::class, ['dbrow' => $permission_dbrow]);
        } else {
            $permission_model->name = $permission_name;
            $permission_model->persist();
        }

        $pivot_model = \App\Site\Models\RolePermission::new($this->getContainer());
        $pivot_model->permission_id = $permission_model->id;
        $pivot_model->role_id = $role_model->id;
        $pivot_model->persist();
    }

    /**
     * adds permissions and roles
     */
    private function addRolesPermissions()
    {
        $guest_role_model = \App\Site\Models\Role::new($this->getContainer());
        $guest_role_model->name = 'guest';
        $guest_role_model->persist();

        $admin_role_model = \App\Site\Models\Role::new($this->getContainer());
        $admin_role_model->name = 'admin';
        $admin_role_model->persist();

        // base permissions
        $permissions = ['view_site'];
        foreach ($permissions as $permission_name) {
            $this->addPermission($guest_role_model, $permission_name);
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
     */
    private function addLanguages()
    {
        $fd = fopen("app/base/tools/iso_639-1.csv", "r");
        $header = null;
        while ($row=fgetcsv($fd)) {
            if ($header == null) {
                $header = $row;
            } else {
                $lang = array_combine($header, $row);

                $lang_model = \App\Site\Models\Language::new($this->getContainer());
                $lang_model->locale = $lang['639-1'];
                $lang_model->{"639-1"} = $lang['639-1'];
                $lang_model->{"639-2"} = $lang['639-2'];
                $lang_model->name = $lang['name'];
                $lang_model->native = $lang['nativeName'];
                $lang_model->family = $lang['family'];

                $lang_model->persist();
            }
        }
    }

    /**
     * adds homepage model
     *
     * @param  Website $website_model
     * @param  User    $owner_model
     * @return Page
     */
    private function addHomePage($website_model, $owner_model)
    {
        $page_model = \App\Site\Models\Page::new($this->getContainer());

        $page_model->website_id = $website_model->id;
        $page_model->url = 'homepage';
        $page_model->title = $this->getEnv('APPNAME') . ' home';
        $page_model->locale = $website_model->default_locale;
        $page_model->content = '';
        $page_model->user_id = $owner_model->id;

        $page_model->persist();
        return $page_model;
    }

    /**
     * adds configuration variables
     *
     * @param Website $website_model
     * @param Page    $homePage
     */
    private function addVariables($website_model, $homePage)
    {
        foreach ([
            'app/frontend/homepage' => ['locale' => $website_model->default_locale, 'value' => $homePage->id],
            'app/frontend/homepage_redirects_to_language' => ['locale' => null, 'value' => 0],
            'app/frontend/langs' => ['locale' => null, 'value' => $website_model->default_locale],
            'app/frontend/main_menu' => ['locale' => $website_model->default_locale, 'value' => ''],
            'app/global/site_mail_address' => ['locale' => null, 'value' => ''],
            'app/mail/ses_sender' => ['locale' => $website_model->default_locale, 'value' => ''],
            'app/frontend/menu_with_logo' => ['locale' => null, 'value' => 1],
            'app/backend/log_requests' => ['locale' => null, 'value' => 1],
            'app/frontend/log_requests' => ['locale' => null, 'value' => 1],
            'app/frontend/themename' => ['locale' => null, 'value' => 'theme'],
        ] as $path => $info) {
            $configuration_model = \App\Site\Models\Configuration::new($this->getContainer());
            $configuration_model->website_id = $website_model->id;
            $configuration_model->locale = $info['locale'];
            $configuration_model->path = $path;
            $configuration_model->value = $info['value'];
            $configuration_model->is_system = 1;
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
