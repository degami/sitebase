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
use \App\Site\Models\QueueMessage;
use \App\Site\Routing\RouteInfo;
use \App\Base\Abstracts\BasePage;
use \App\Base\Abstracts\Model;
use \App\Base\Controllers\Dummy\NullPage;
use \LessQL\Row;
use \Swift_Message;
use \Exception;
use \Degami\PHPFormsApi\Accessories\TagElement;
use \Spatie\ArrayToXml\ArrayToXml;

/**
 * Global utils functions Helper Class
 */
class Globals extends ContainerAwareObject
{

    /**
     * get page regions list
     *
     * @return array
     */
    public function getPageRegions()
    {
        return array_filter(array_map('trim', explode(",", $this->getEnv('PAGE_REGIONS', 'menu,header,content,footer'))));
    }

    /**
     * gets available block regions
     *
     * @return array
     */
    public function getBlockRegions()
    {
        $out = [
            '' => '',
            'after_body_open' => 'After Body-Open',
            'before_body_close' => 'Before Body-Close',
        ];

        foreach ($this->getPageRegions() as $region) {
            $out['pre_'.$region] = 'Pre-'.ucfirst(strtolower($region));
            $out['post_'.$region] = 'Post-'.ucfirst(strtolower($region));
        }

        return $out;
    }

    /**
     * gets all blocks for current locale
     *
     * @param  string $locale
     * @return array
     */
    public function getAllPageBlocks($locale = null)
    {
        static $pageBlocks = null;

        if (is_null($pageBlocks)) {
            $website_id = $this->getSiteData()->getCurrentWebsiteId();

            $pageBlocks = [];
            foreach ($this->getDb()->table('block')->where(['locale' => [$locale, null], 'website_id' =>[$website_id, null]])->orderBy('order')->fetchAll() as $row) {
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
     */
    public function getWebsitesSelectOptions()
    {
        $websitesDB = [];
        foreach ($this->getDb()->table('website')->fetchAll() as $w) {
            $websitesDB[$w->id] = $w->site_name . " (".$w->domain.")";
        }
        return $websitesDB;
    }

    /**
     * gets site languages options for selects
     *
     * @param  integer $website_id
     * @return array
     */
    public function getSiteLanguagesSelectOptions($website_id = null)
    {
        $languages = $this->getSiteData()->getSiteLocales($website_id);
        $langsDB = [];
        foreach ($this->getDb()->table('language')->where(['locale'=>$languages])->fetchAll() as $l) {
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
     * return an error page
     *
     * @param  integer $error_code
     * @param  array   $template_data
     * @return Response
     */
    public function errorPage($error_code, $template_data = [])
    {
        switch ($error_code) {
            case 403:
            case 404:
            case 405:
                $template = $this->getTemplates()->make('errors::'.$error_code);
                $template_data['controller'] = $this->getContainer()->make(NullPage::class);
                if (!isset($template_data['body_class'])) {
                    $template_data['body_class'] = 'error';
                }
                $template->data($template_data);

                return (new Response(
                    $template->render(),
                    $error_code
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
     * @param  \Exception $exception
     * @return Response
     */
    public function exceptionPage(\Exception $exception)
    {
        $template = $this->getTemplates()->make('errors::exception');
        $template_data = [
            'e' => $exception,
            'controller' =>  $this->getContainer()->make(NullPage::class),
            'body_class' => 'error',
        ];
        $template->data($template_data);

        return (new Response(
            $template->render(),
            500
        ));
    }

    /**
     * returns an exception error json
     *
     * @param  \Exception $exception
     * @return Response
     */
    public function exceptionJson(\Exception $exception)
    {
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
     * @param  \Exception $exception
     * @return Response
     */
    public function exceptionXML(\Exception $exception)
    {
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
     * @return Response
     */
    public function offlinePage()
    {
        $template = $this->getTemplates()->make('errors::offline');
        $template_data = [
            'controller' =>  $this->getContainer()->make(NullPage::class),
            'body_class' => 'manteinance',
        ];
        $template->data($template_data);

        return (new Response(
            $template->render(),
            500
        ));
    }

    /**
     * returns site menu
     *
     * @param  string    $menu_name
     * @param  integer   $website_id
     * @param  string    $locale
     * @param  Menu|null $menu_element
     * @return array
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
            $query = $this->getDb()->table('menu')->where(['menu_name' => $menu_name, 'website_id' => $website_id, 'parent_id' => null, 'locale' => [$locale, null]]);
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
     * logs an exception
     *
     * @param  Exception $e
     * @param  string    $prefix
     * @param  boolean   $with_request
     * @return void
     */
    public function logException(Exception $e, $prefix = null, $with_request = true)
    {
        if ($with_request == true) {
            $request = Request::createFromGlobals();
        }

        $this->getLog()->error($prefix . ($prefix != null ? ' - ':'') . $e->getMessage());
        $this->getLog()->debug($e->getTraceAsString());
        if ($with_request == true && !empty($request->request->all())) {
            $this->getLog()->debug(serialize($request->request->all()));
        }
    }

    /**
     * gets an icon
     *
     * @param  string $icon_name
     * @return string
     */
    public function getIcon($icon_name)
    {
        return $this->getContainer()->get('icons')->get($icon_name, [], false);
    }

    /**
     * executes an http request
     *
     * @param  string $url
     * @param  string $method
     * @param  array  $options
     * @return string|boolean
     */
    public function httpRequest($url, $method = 'GET', array $options = [])
    {
        $res = $this->getGuzzle()->request($method, $url, $options);
        if ($res->getStatusCode() == 200) {
            $body = (string) $res->getBody();
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
     * @param  string $string
     * @param  string $locale
     * @return string
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
     * @param  string $pass
     * @param  string $encoded_pass
     * @return boolean
     */
    public function checkPass($pass, $encoded_pass)
    {
        $salt = substr($encoded_pass, strrpos($encdoded_pass, ':')+1);
        return (sha1($salt.$pass).':'.$salt) == $encoded_pass;
    }

    /**
     * gets encoded version of password
     *
     * @param  string $pass
     * @return string
     */
    public function getEncodedPass($pass)
    {
        return sha1($this->getEnv('SALT').$pass).':'.$this->getEnv('SALT');
    }

    /**
     * adds message to queue
     *
     * @param string $queue_name
     * @param mixed  $data
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
}
