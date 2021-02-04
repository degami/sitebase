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

use App\App;
use App\Base\Abstracts\ContainerAwareObject;
use App\Site\Models\Language;
use App\Site\Models\Website;
use App\Site\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use GuzzleHttp\Exception\GuzzleException;
use League\Plates\Template\Template;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use App\Site\Models\RequestLog;
use App\Site\Models\QueueMessage;
use App\Base\Controllers\Dummy\NullPage;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;
use Throwable;

/**
 * Global utils functions Helper Class
 */
class Globals extends ContainerAwareObject
{
    /**
     * gets websites options for selects
     *
     * @return array
     */
    public function getWebsitesSelectOptions(): array
    {
        $out = [];
        foreach ($this->getContainer()->call([Website::class,'all']) as $website) {
            /** @var Website $website */
            $out[$website->getId()] = $website->getSiteName() . " (" . $website->getDomain() . ")";
        }
        return $out;
    }

    /**
     * gets site languages options for selects
     *
     * @param int|null $website_id
     * @return array
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function getSiteLanguagesSelectOptions($website_id = null): array
    {
        $languages = $this->getSiteData()->getSiteLocales($website_id);
        $languages_on_DB = [];
        foreach ($this->getContainer()->call([Language::class, 'where'], ['condition' => ['locale' => $languages]]) as $l) {
            /** @var Language $l */
            $languages_on_DB[$l->getLocale()] = $l;
        }

        return array_combine(
            $languages,
            array_map(
                function ($el) use ($languages_on_DB) {
                    /** @var Language $lang */
                    $lang = isset($languages_on_DB[$el]) ? $languages_on_DB[$el] : null;
                    return $lang ? $lang->getNative() : $el;
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
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    protected function logRequestIfNeeded($status_code, Request $request) : void
    {
        if (!$this->getApp()->isBlocked($request->getClientIp()) && $this->getSiteData()->getConfigValue('app/frontend/log_requests') == true) {
            $route_info = $this->getAppRouteInfo();
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
     * @param int $error_code
     * @param Request $request
     * @param RouteInfo|null $route_info
     * @param array $template_data
     * @param null $template_name
     * @return Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function errorPage(int $error_code, Request $request, RouteInfo $route_info = null, $template_data = [], $template_name = null): Response
    {
        $this->logRequestIfNeeded($error_code, $request);

        if ($route_info == null) {
            $route_info = $this->getEmptyRouteInfo();
        }
        if (!is_array($template_data)) {
            $template_data = [$template_data];
        }
        if (!isset($template_data['controller'])) {
            $template_data['controller'] = $this->getContainer()->make(NullPage::class, ['request' => $request,'route_info' => $route_info]);
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
                $template_data['body_class'] = 'maintenance';
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
     * @param Request $request
     * @param RouteInfo|null $route_info
     * @return Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function exceptionPage(Throwable $exception, Request $request, RouteInfo $route_info = null): Response
    {
        $this->logException($exception, null, $request);

        if ($route_info == null) {
            $route_info = $this->getEmptyRouteInfo();
        }

        $template_data = [
            'e' => $exception,
        ];

        return $this->errorPage(500, $request, $route_info, $template_data, 'errors::exception');
    }

    /**
     * returns a blocked ip exception error page
     *
     * @param Request $request
     * @param RouteInfo|null $route_info
     * @return Response
     * @throws BasicException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function blockedIpPage(Request $request, RouteInfo $route_info = null): Response
    {
        $template_data = [
            'ip_addr' => $request->getClientIp(),
        ];

        if ($route_info == null) {
            $route_info = $this->getEmptyRouteInfo();
        }

        return $this->errorPage(503, $request, $route_info, $template_data, 'errors::blocked');
    }

    /**
     * returns an exception error json
     *
     * @param Exception $exception
     * @param Request $request
     * @return Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function exceptionJson(Exception $exception, Request $request): Response
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
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function exceptionXML(Exception $exception, Request $request): Response
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
     * gets an empty RouteInfo object
     *
     * @return RouteInfo
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function getEmptyRouteInfo() : RouteInfo
    {
        $http_method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        $request_uri = $_SERVER['REQUEST_URI'] ?? '/no-route';

        // Fetch method and URI from somewhere
        $parsed = parse_url($request_uri);

        // Strip query string (?foo=bar) and decode URI
        $uri = rawurldecode($parsed['path']);
        $route = $uri;
        $route_name = null;
        $rewrite_id = null;

        // return a RouteInfo instance
        return $this->getContainer()->make(RouteInfo::class, [
            'dispatcher_info' => [Dispatcher::NOT_FOUND],
            'http_method' => $http_method,
            'uri' => $uri,
            'route' => $route,
            'route_name' => $route_name,
            'rewrite' => $rewrite_id,
        ]);
    }

    /**
     * returns a "site is offline" error page
     *
     * @param Request $request
     * @param RouteInfo|null $route_info
     * @return Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function offlinePage(Request $request, RouteInfo $route_info = null): Response
    {
        if ($route_info == null) {
            $route_info = $this->getEmptyRouteInfo();
        }
        return $this->errorPage(503, $request, $route_info);
    }

    /**
     * logs an exception
     *
     * @param Throwable $e
     * @param string|null $prefix
     * @param Request|null $request
     * @throws BasicException
     */
    public function logException(Throwable $e, $prefix = null, Request $request = null) : void
    {
        $this->getLog()->error($prefix . ($prefix != null ? ' - ' : '') . $e->getMessage());
        $this->getLog()->debug($e->getTraceAsString());
        if ($request != null && !empty($request->request->all())) {
            $this->getLog()->debug(serialize($request->request->all()));
        }
    }

    /**
     * executes an http request
     *
     * @param string $url
     * @param string $method
     * @param array $options
     * @return mixed
     * @throws GuzzleException
     * @throws BasicException
     */
    public function httpRequest(string $url, $method = 'GET', array $options = []) : mixed
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function translate(string $string, $locale = null): string
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
     * @return bool
     */
    public function checkPass(string $pass, string $encoded_pass): bool
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
    public function getEncodedPass(string $pass): string
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
     * @throws DependencyException
     * @throws NotFoundException
     */
    public function addQueueMessage(string $queue_name, mixed $data): QueueMessage
    {
        /** @var QueueMessage $message */
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
    public function randString($length = 10): string
    {
        $characters = implode("", array_merge(range(0, 9), range('a', 'z'), range('A', 'Z')));
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * @param string $subject
     * @param string $mail_body
     * @param string $template_name
     * @return string
     * @throws BasicException
     * @throws Throwable
     */
    public function getWrappedMailBody(string $subject, string $mail_body, $template_name = 'generic'): string
    {
        $old_directory = $this->getTemplates()->getDirectory();

        $template = $this->getTemplates()->make('mails::' . $template_name);
        $template->data([
            'subject' => $subject,
            'body' => $mail_body,
        ]);
        $out = $template->render();
        $this->getTemplates()->setDirectory($old_directory);

        return $out;
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $queue_name
     * @param string $template_name
     * @return QueueMessage
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Throwable
     */
    protected function queueMail(string $from, string $to, string $subject, string $body, string $queue_name, string $template_name = 'generic'): QueueMessage
    {
        return $this->addQueueMessage($queue_name, [
            'from' => $from,
            'to' => $to,
            'subject' => $subject,
            'body' => $this->getWrappedMailBody($subject, $body, $template_name),
        ]);
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @param string $template_name
     * @return QueueMessage
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Throwable
     */
    public function queueInternalMail(string $from, string $to, string $subject, string $body, string $template_name = 'generic'): QueueMessage
    {
        return $this->queueMail($from, $to, $subject, $body, 'internal_mail', $template_name);
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return QueueMessage
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Throwable
     */
    public function queueContactFormMail(string $from, string $to, string $subject, string $body): QueueMessage
    {
        return $this->queueMail($from, $to, $subject, $body, 'contact_form_mail');
    }

    /**
     * @param string $from
     * @param string $to
     * @param string $subject
     * @param string $body
     * @return QueueMessage
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws Throwable
     */
    public function queueLinksFormMail(string $from, string $to, string $subject, string $body): QueueMessage
    {
        return $this->queueMail($from, $to, $subject, $body, 'link_form_mail');
    }
}
