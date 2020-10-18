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
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use GuzzleHttp\Exception\GuzzleException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use \Symfony\Component\HttpFoundation\Response;
use \Symfony\Component\HttpFoundation\Request;
use \App\Site\Models\Menu;
use \App\Site\Models\Block;
use \App\Site\Models\RequestLog;
use \App\Site\Models\QueueMessage;
use \App\Base\Controllers\Dummy\NullPage;
use \Exception;
use \Spatie\ArrayToXml\ArrayToXml;
use Throwable;

/**
 * Global utils functions Helper Class
 */
class Globals extends ContainerAwareObject
{

    /**
     * get page regions list
     *
     * @return array
     * @throws BasicException
     */
    public function getPageRegions()
    {
        return array_filter(array_map('trim', explode(",", $this->getEnv('PAGE_REGIONS', 'menu,header,content,footer'))));
    }

    /**
     * gets available block regions
     *
     * @return array
     * @throws BasicException
     */
    public function getBlockRegions()
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
     */
    public function getAllPageBlocks($locale = null)
    {
        static $pageBlocks = null;

        if (is_null($pageBlocks)) {
            $website_id = $this->getSiteData()->getCurrentWebsiteId();

            $pageBlocks = [];
            foreach ($this->getDb()->table('block')->where(['locale' => [$locale, null], 'website_id' => [$website_id, null]])->orderBy('order')->fetchAll() as $row) {
                $block = $this->getContainer()->make(Block::class, ['dbrow' => $row]);
                if (!isset($pageBlocks[$block->region])) {
                    $pageBlocks[$block->region] = [];
                }
                $block->loadInstance();
                $pageBlocks[$block->region][] = $block;
            }
        }

        return $pageBlocks;
    }

    /**
     * gets websites options for selects
     *
     * @return array
     * @throws BasicException
     */
    public function getWebsitesSelectOptions()
    {
        $websitesDB = [];
        foreach ($this->getDb()->table('website')->fetchAll() as $w) {
            $websitesDB[$w->id] = $w->site_name . " (" . $w->domain . ")";
        }
        return $websitesDB;
    }

    /**
     * gets site languages options for selects
     *
     * @param integer|null $website_id
     * @return array
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getSiteLanguagesSelectOptions($website_id = null)
    {
        $languages = $this->getSiteData()->getSiteLocales($website_id);
        $langsDB = [];
        foreach ($this->getDb()->table('language')->where(['locale' => $languages])->fetchAll() as $l) {
            $langsDB[$l->locale] = $l;
        }

        return array_combine(
            $languages,
            array_map(
                function ($el) use ($langsDB) {
                    $lang = isset($langsDB[$el]) ? $langsDB[$el] : null;
                    return $lang ? "{$lang->native}" : $el;
                },
                $languages
            )
        );
    }

    /**
     * logs request (if needed)
     *
     * @param $status_code
     * @param Request $request
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function logRequestIfNeeded($status_code, Request $request)
    {
        if (!$this->getApp()->isBlocked($request->getClientIp()) && $this->getSiteData()->getConfigValue('app/frontend/log_requests') == true) {
            $route_info = $this->getApp()->getRouteInfo();
            try {
                $controller = null;
                if ($route_info instanceof RouteInfo) {
                    $controller = $route_info->getControllerObject();
                }
                /** @var RequestLog $log */
                $log = $this->getContainer()->make(RequestLog::class);
                $log->fillWithRequest($request, $controller);
                $log->setResponseCode($status_code);
                $log->persist();
            } catch (Exception $e) {
                $this->logException($e, "Can't write RequestLog", $request);
            }
        }
    }

    /**
     * return an error page
     *
     * @param integer $error_code
     * @param Request|null $request
     * @param array $template_data
     * @param string|null $template_name
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function errorPage($error_code, Request $request, $template_data = [], $template_name = null)
    {
        $this->logRequestIfNeeded($error_code, $request);

        if (!is_array($template_data)) {
            $template_data = [$template_data];
        }
        if (!isset($template_data['controller'])) {
            $template_data['controller'] = $this->getContainer()->make(NullPage::class);
        }
        if (!isset($template_data['body_class'])) {
            $template_data['body_class'] = 'error';
        }

        switch ($error_code) {
            case 403:
            case 404:
            case 405:
                $template = $this->getTemplates()->make($template_name ?: 'errors::' . $error_code);
                $template->data($template_data);

                return (new Response(
                    $template->render(),
                    $error_code
                ));
            case 503:
                $template = $this->getTemplates()->make($template_name ?: 'errors::offline');
                $template_data['body_class'] = 'manteinance';
                $template->data($template_data);

                return (new Response(
                    $template->render(),
                    $error_code
                ));
        }

        if ($error_code == 500 && isset($template_data['e'])) {
            $template = $this->getTemplates()->make($template_name ?: 'errors::exception');
            $template->data($template_data);

            return (new Response(
                $template->render(),
                500
            ));
        }

        return (new Response(
            $this->getTemplates()->make('errors::500')->render(),
            500
        ));
    }

    /**
     * returns a exception error page
     *
     * @param Throwable $exception
     * @param Request|null $request
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function exceptionPage(Throwable $exception, Request $request)
    {
        $this->logException($exception, null, $request);

        $template_data = [
            'e' => $exception,
        ];

        return $this->errorPage(500, $request, $template_data, 'errors::exception');
    }

    /**
     * returns a blocked ip exception error page
     *
     * @param Request $request
     * @return Response
     * @throws BasicException
     * @throws Throwable
     */
    public function blockedIpPage(Request $request)
    {
        $template_data = [
            'ip_addr' => $request->getClientIp(),
        ];

        return $this->errorPage(503, $request, $template_data, 'errors::blocked');
    }


    /**
     * returns an exception error json
     *
     * @param Exception $exception
     * @param Request $request
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function exceptionJson(Exception $exception, Request $request)
    {
        $this->logRequestIfNeeded(500, $request);

        if ($this->getEnv('DEBUG')) {
            $content = [
                'success' => false,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
        } else {
            $content = [
                'success' => false,
                'message' => 'Exception!',
            ];
        }

        return (new Response(
            json_encode($content),
            500,
            ['Content-Type' => 'application/json']
        ));
    }

    /**
     * returns an exception error xml
     *
     * @param Exception $exception
     * @param Request $request
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function exceptionXML(Exception $exception, Request $request)
    {
        $this->logRequestIfNeeded(500, $request);

        if ($this->getEnv('DEBUG')) {
            $content = [
                'success' => false,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ];
        } else {
            $content = [
                'success' => false,
                'message' => 'Exception!',
            ];
        }

        return (new Response(
            ArrayToXml::convert($content),
            500,
            ['Content-Type' => 'text/xml']
        ));
    }

    /**
     * returns a "site is offline" error page
     *
     * @param Request|null $request
     * @return Response
     * @throws BasicException
     * @throws Throwable
     */
    public function offlinePage(Request $request)
    {
        return $this->errorPage(503, $request);
    }

    /**
     * returns site menu
     *
     * @param string $menu_name
     * @param integer $website_id
     * @param string $locale
     * @param Menu|null $menu_element
     * @return array
     * @throws BasicException
     */
    public function getSiteMenu($menu_name, $website_id, $locale, $menu_element = null)
    {
        $out = [];
        if ($menu_element instanceof Menu) {
            $out['menu_id'] = $menu_element->getId();
            $out['title'] = $menu_element->getTitle();
            $out['href'] = $menu_element->getLinkUrl();
            $out['target'] = $menu_element->getTarget();
            $out['breadcrumb'] = $menu_element->getBreadcumb();
            $out['children'] = [];
            foreach ($menu_element->getChildren($locale) as $child) {
                $out['children'][] = $this->getSiteMenu($menu_name, $website_id, $locale, $child);
            }
        } else {
            $query = $this->getDb()->table('menu')->where(['menu_name' => $menu_name, 'website_id' => $website_id, 'parent_id' => null, 'locale' => [$locale, null]])->orderBy('position');
            $out = array_map(
                function ($el) use ($menu_name, $website_id, $locale) {
                    /**
                     * @var Menu $menu_model
                     */
                    $menu_model = $this->getContainer()->make(Menu::class, ['dbrow' => $el]);
                    return $this->getSiteMenu($menu_name, $website_id, $locale, $menu_model);
                },
                $query->fetchAll()
            );
        }
        return $out;
    }

    /**
     * returns site menu
     *
     * @param $menu_items
     * @param Menu|null $menu_element
     * @return array
     * @throws BasicException
     */
    public function buildSiteMenu($menu_items, $menu_element = null)
    {
        $out = [];
        if ($menu_element instanceof Menu) {
            $out['menu_id'] = $menu_element->getId();
            $out['title'] = $menu_element->getTitle();
            $out['href'] = $menu_element->getLinkUrl();
            $out['target'] = $menu_element->getTarget();
            $out['breadcrumb'] = $menu_element->getBreadcumb();
            $out['children'] = [];
            foreach ($menu_items as $child) {
                /** @var Menu $child */
                if ($child->getParentId() == $menu_element->getId()) {
                    $out['children'][] = $this->buildSiteMenu($menu_items, $child);
                }
            }
        } else {
            foreach ($menu_items as $item) {
                /** @var Menu $item */
                if ($item->getParentId() == null) {
                    $out[] = $this->buildSiteMenu($menu_items, $item);
                }
            }
        }
        return $out;
    }

    /**
     * logs an exception
     *
     * @param Exception $e
     * @param string|null $prefix
     * @param Request|null $request
     * @throws BasicException
     */
    public function logException(Throwable $e, $prefix = null, Request $request = null)
    {
        $this->getLog()->error($prefix . ($prefix != null ? ' - ' : '') . $e->getMessage());
        $this->getLog()->debug($e->getTraceAsString());
        if ($request != null && !empty($request->request->all())) {
            $this->getLog()->debug(serialize($request->request->all()));
        }
    }

    /**
     * gets an icon
     *
     * @param string $icon_name
     * @param array $attributes
     * @return string
     */
    public function getIcon($icon_name, $attributes = [])
    {
        return $this->getContainer()->get('icons')->get($icon_name, $attributes, false);
    }

    /**
     * executes an http request
     *
     * @param string $url
     * @param string $method
     * @param array $options
     * @return string|boolean
     * @throws GuzzleException
     * @throws BasicException
     */
    public function httpRequest($url, $method = 'GET', array $options = [])
    {
        $res = $this->getGuzzle()->request($method, $url, $options);
        if ($res->getStatusCode() == 200) {
            $body = (string)$res->getBody();
            if (preg_match("/application\/json/i", $res->getHeader('content-type')[0])) {
                return json_decode($body);
            }

            return $body;
        }
        return false;
    }

    /**
     * translates a string
     *
     * @param string $string
     * @param string|null $locale
     * @return string
     * @throws BasicException
     */
    public function translate($string, $locale = null)
    {
        if ($locale == null) {
            $locale = $this->getApp()->getCurrentLocale();
        }
        return $this->getTranslator($locale)->translate($string);
    }

    /**
     * checks password
     *
     * @param string $pass
     * @param string $encoded_pass
     * @return boolean
     */
    public function checkPass($pass, $encoded_pass)
    {
        $salt = substr($encoded_pass, strrpos($encoded_pass, ':') + 1);
        return (sha1($salt . $pass) . ':' . $salt) == $encoded_pass;
    }

    /**
     * gets encoded version of password
     *
     * @param string $pass
     * @return string
     * @throws BasicException
     */
    public function getEncodedPass($pass)
    {
        return sha1($this->getEnv('SALT') . $pass) . ':' . $this->getEnv('SALT');
    }

    /**
     * adds message to queue
     *
     * @param string $queue_name
     * @param mixed $data
     * @return QueueMessage
     * @throws BasicException
     */
    public function addQueueMessage($queue_name, $data)
    {
        $message = $this->getContainer()->call([QueueMessage::class, 'new']);
        $message->setQueueName($queue_name);
        $message->setMessage(json_encode($data));
        $message->setStatus(QueueMessage::STATUS_PENDING);
        $message->setWebsiteId($this->getSiteData()->getCurrentWebsiteId());
        $message->persist();
        return $message;
    }

    /**
     * computes a random string
     *
     * @param int $length
     * @return string
     */
    public function randString($length = 10)
    {
        $characters = implode("", array_merge(range(0, 9), range('a', 'z'), range('A', 'Z')));
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}
