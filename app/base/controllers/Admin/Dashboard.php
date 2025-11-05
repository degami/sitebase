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

namespace App\Base\Controllers\Admin;

use App\App;
use App\Base\Models\Block;
use App\Site\Models\Contact;
use App\Site\Models\ContactSubmission;
use App\Site\Models\LinkExchange;
use App\Base\Models\MailLog;
use App\Site\Models\MediaElement;
use App\Base\Models\RequestLog;
use App\Base\Models\User;
use App\Base\Models\Website;
use App\Site\Models\Page;
use App\Site\Models\News;
use App\Site\Models\Taxonomy;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Base\Abstracts\Models\FrontendModel;
use App\Base\Tools\Utils\Globals;
use App\Site\Models\Event;
use HaydenPierce\ClassFinder\ClassFinder;

/**
 * "Dashboard" Admin Page
 */
class Dashboard extends AdminPage
{
    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public function getTemplateName(): string
    {
        return 'dashboard';
    }

    /**
     * {@inheritdoc}
     *
     * @return string
     */
    public static function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdoc}
     *
     * @return array|null
     */
    public static function getAdminPageLink() : array|null
    {
        return [
            'permission_name' => '',
            'route_name' => static::getPageRouteName(),
            'icon' => 'home',
            'text' => 'Dashboard',
            'section' => 'Main',
        ];
    }

    /**
     * {@inheritdoc}
     *
     * @return array
     */
    public function getTemplateData(): array
    {

        $frontendClasses = array_filter(array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::MODELS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
        ), function ($className) {
            return is_subclass_of($className, FrontendModel::class);
        });

        $this->template_data = [
            'websites' => Website::getCollection()->count(),
            'users' => User::getCollection()->count(),
            'contact_forms' => Contact::getCollection()->count(),
            'contact_submissions' => ContactSubmission::getCollection()->count(),
            'blocks' => Block::getCollection()->count(),
            'media' => MediaElement::getCollection()->count(),
            'page_views' => RequestLog::getCollection()->count(),
            'mails_sent' => MailLog::getCollection()->count(),
            'links' => LinkExchange::getCollection()->count(),
 //           'pages' => Page::getCollection()->count(),
//            'taxonomy_terms' => Taxonomy::getCollection()->count(),
//            'news' => News::getCollection()->count(),
//            'events' => Event::getCollection()->count(),
        ];


        foreach ($frontendClasses as $frontendClass) {
            $key = $this->getUtils()->pluralize(Globals::getClassBasename($frontendClass));
            $this->template_data[strtolower($key)] = $this->containerCall([$frontendClass, 'getCollection'])->count();
        }


        $adminControllers = array_filter(array_merge(
            ClassFinder::getClassesInNamespace(App::BASE_CONTROLLERS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
            ClassFinder::getClassesInNamespace(App::CONTROLLERS_NAMESPACE, ClassFinder::RECURSIVE_MODE),
        ), function ($className) {
            return is_subclass_of($className, AdminPage::class);
        });

        $this->template_data['dashboard_links'] = [];
        foreach ($adminControllers as $adminController) {
            if (is_callable([$adminController, 'exposeDataToDashboard'])) {
                $dashboardData = $this->containerCall([$adminController, 'exposeDataToDashboard']);
                $key = $adminController;
                $pageLink = $this->containerCall([$adminController, 'getAdminPageLink']);
                if (!is_null($dashboardData) && is_array($pageLink)) {
                    $this->template_data['dashboard_links'][$key] = [
                        'icon' => $pageLink['icon'],
                        'label' => $pageLink['text'],
                        'route_name' => $pageLink['route_name'],
                        'section' => $pageLink['section'],
                        'data' => $dashboardData,
                        'order' => $pageLink['order'] ?? 0,
                    ];
                }
            }
        }

//        var_dump($this->template_data);

        return $this->template_data;
    }
}
