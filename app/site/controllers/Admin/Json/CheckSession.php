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
namespace App\Site\Controllers\Admin\Json;

use \Psr\Container\ContainerInterface;
use \App\Base\Abstracts\AdminJsonPage;
use \App\Site\Models\Contact;
use \App\Site\Routing\RouteInfo;

/**
 * Check Admin Session
 */
class CheckSession extends AdminJsonPage
{
    /**
     * {@inheritdocs}
     *
     * @return string
     */
    protected function getAccessPermission()
    {
        return 'administer_site';
    }

    /**
     * {@inheritdocs}
     *
     * @return array
     */
    protected function getJsonData()
    {
        return [
            'user' => $this->getCurrentUser()->getData(),
        ];
    }
}
