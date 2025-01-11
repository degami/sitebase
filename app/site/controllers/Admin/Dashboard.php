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

namespace App\Site\Controllers\Admin;

use App\Site\Models\Block;
use App\Site\Models\Contact;
use App\Site\Models\ContactSubmission;
use App\Site\Models\LinkExchange;
use App\Site\Models\MailLog;
use App\Site\Models\MediaElement;
use App\Site\Models\RequestLog;
use App\Site\Models\User;
use App\Site\Models\Website;
use App\Site\Models\Page;
use App\Site\Models\News;
use App\Site\Models\Taxonomy;
use App\Base\Abstracts\Controllers\AdminPage;
use App\Site\Models\Event;

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
    public Function getAdminPageLink() : array|null
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
        $this->template_data = [
            'websites' => Website::getCollection()->count(),
            'users' => User::getCollection()->count(),
            'pages' => Page::getCollection()->count(),
            'contact_forms' => Contact::getCollection()->count(),
            'contact_submissions' => ContactSubmission::getCollection()->count(),
            'taxonomy_terms' => Taxonomy::getCollection()->count(),
            'blocks' => Block::getCollection()->count(),
            'media' => MediaElement::getCollection()->count(),
            'page_views' => RequestLog::getCollection()->count(),
            'mails_sent' => MailLog::getCollection()->count(),
            'links' => LinkExchange::getCollection()->count(),
            'news' => News::getCollection()->count(),
            'events' => Event::getCollection()->count(),
        ];
        return $this->template_data;
    }
}
