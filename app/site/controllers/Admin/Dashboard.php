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
use \App\Base\Abstracts\Controllers\AdminPage;

/**
 * "Dashboard" Admin Page
 */
class Dashboard extends AdminPage
{
    /**
     * @var array template data
     */
    protected $template_data = [];

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getTemplateName(): string
    {
        return 'dashboard';
    }

    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission(): string
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getTemplateData(): array
    {
        $this->template_data = [
            'websites' => $this->getContainer()->call([Website::class, 'totalNum']),
            'users' => $this->getContainer()->call([User::class, 'totalNum']),
            'pages' => $this->getContainer()->call([Page::class, 'totalNum']),
            'contact_forms' => $this->getContainer()->call([Contact::class, 'totalNum']),
            'contact_submissions' => $this->getContainer()->call([ContactSubmission::class, 'totalNum']),
            'taxonomy_terms' => $this->getContainer()->call([Taxonomy::class, 'totalNum']),
            'blocks' => $this->getContainer()->call([Block::class, 'totalNum']),
            'media' => $this->getContainer()->call([MediaElement::class, 'totalNum']),
            'page_views' => $this->getContainer()->call([RequestLog::class, 'totalNum']),
            'mails_sent' => $this->getContainer()->call([MailLog::class, 'totalNum']),
            'links' => $this->getContainer()->call([LinkExchange::class, 'totalNum']),
            'news' => $this->getContainer()->call([News::class, 'totalNum']),
        ];
        return $this->template_data;
    }
}
