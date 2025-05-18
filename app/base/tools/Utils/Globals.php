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

namespace App\Base\Tools\Utils;

use App\Base\Abstracts\ContainerAwareObject;
use App\Base\Models\Language;
use App\Base\Models\Website;
use App\Base\Routing\RouteInfo;
use Degami\Basics\Exceptions\BasicException;
use DI\DependencyException;
use DI\NotFoundException;
use FastRoute\Dispatcher;
use GuzzleHttp\Exception\GuzzleException;
use Phpfastcache\Exceptions\PhpfastcacheSimpleCacheException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Token;
use Lcobucci\JWT\Validator;
use App\Base\Models\RequestLog;
use App\Base\Models\QueueMessage;
use App\Base\Controllers\Dummy\NullPage;
use Exception;
use Spatie\ArrayToXml\ArrayToXml;
use Throwable;
use App\Base\Exceptions\OfflineException;
use App\Base\Exceptions\BlockedIpException;
use App\Base\Exceptions\NotFoundException as AppNotFoundException;
use App\Base\Exceptions\NotAllowedException;
use App\Base\Exceptions\PermissionDeniedException;
use App\Base\Models\User;
use App\App;
use App\Base\Abstracts\Controllers\BasePage;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;

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
        foreach (Website::getCollection() as $website) {
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
    public function getSiteLanguagesSelectOptions(?int $website_id = null): array
    {
        $languages = $this->getSiteData()->getSiteLocales($website_id);
        $languages_on_DB = [];
        foreach (Language::getCollection()->where(['locale' => $languages]) as $l) {
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
    protected function logRequestIfNeeded(int $status_code, Request $request) : void
    {
        if (!$this->getApp()->isBlocked($request->getClientIp()) && $this->getSiteData()->getConfigValue('app/frontend/log_requests') == true) {
            $route_info = $this->getAppRouteInfo();
            try {
                $controller = null;
                if ($route_info instanceof RouteInfo) {
                    $controller = $route_info->getControllerObject();
                }
                /** @var RequestLog $log */
                $log = $this->containerMake(RequestLog::class);
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
     * @param string|null $template_name
     * @return Response
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     * @throws Throwable
     */
    public function errorPage(int $error_code, Request $request, ?RouteInfo $route_info = null, array $template_data = [], ?string $template_name = null): Response
    {
        if (App::installDone()) {
            $this->logRequestIfNeeded($error_code, $request);
        }

        if ($route_info == null) {
            $route_info = $this->getEmptyRouteInfo();
        }
        if (!is_array($template_data)) {
            $template_data = [$template_data];
        }
        if (!isset($template_data['controller'])) {
            $template_data['controller'] = $this->containerMake(NullPage::class, ['request' => $request,'route_info' => $route_info]);
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

                return $this->createHtmlResponse(
                    $template->render(),
                    $error_code
                );
            case 503:
                $template = $this->getTemplates()->make($template_name ?: 'errors::offline');
                $template_data['body_class'] = 'maintenance';
                $template->data($template_data);

                return $this->createHtmlResponse(
                    $template->render(),
                    $error_code
                );
        }

        if ($error_code == 500 && isset($template_data['e'])) {
            $template = $this->getTemplates()->make($template_name ?: 'errors::exception');
            $template->data($template_data);

            return $this->createHtmlResponse(
                $template->render(),
                500
            );
        }

        return $this->createHtmlResponse(
            $this->getTemplates()->make('errors::500')->render(),
            500
        );
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
    public function exceptionPage(Throwable $exception, Request $request, ?RouteInfo $route_info = null): Response
    {
        if (App::installDone()) {
            $this->logException($exception, null, $request);
        }

        if ($route_info == null) {
            $route_info = $this->getEmptyRouteInfo();
        }

        $template_data = [
            'e' => $exception,
        ];

        /** @var DebugBar $debugbar */
        $debugbar = $this->getDebugbar();

        if (getenv('DEBUG')) {
            $debugbar['exceptions']->addThrowable($exception);
        }


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
    public function blockedIpPage(Request $request, ?RouteInfo $route_info = null): Response
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
     * @return JsonResponse
     * @throws BasicException
     * @throws DependencyException
     * @throws NotFoundException
     * @throws PhpfastcacheSimpleCacheException
     */
    public function exceptionJson(Exception $exception, Request $request): JsonResponse
    {
        $this->logRequestIfNeeded(500, $request);

        if ($this->getEnv('DEBUG')) {
            $content = [
                'success' => false,
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
                'class' => get_class($exception),
            ];
        } else {
            $content = [
                'success' => false,
                'message' => 'Exception!',
            ];
        }        

        $exceptionCode = match(get_class($exception)) {
            OfflineException::class => 503,
            BlockedIpException::class => 503,
            AppNotFoundException::class => 404,
            PermissionDeniedException::class => 403,
            NotAllowedException::class => 405,
            default => 500,
        };

        return $this->createJsonResponse(
            $content,
            $exceptionCode
        );
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

        return $this->createXmlResponse(
            ArrayToXml::convert($content),
            500
        );
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
        return $this->containerMake(RouteInfo::class, [
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
    public function offlinePage(Request $request, ?RouteInfo $route_info = null): Response
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
    public function logException(Throwable $e, ?string $prefix = null, ?Request $request = null) : void
    {
        $this->getLog()->error($prefix . ($prefix != null ? ' - ' : '') . $e->getMessage());
        $this->getLog()->debug($e->getTraceAsString());
        if (!empty($request?->request->all())) {
            $this->getLog()->debug(serialize($request->request->all()));
        }

        if (App::installDone()) {
            $this->getApplicationLogger()->exception($e);
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
    public function translate(string $string, array $params = [], ?string $locale = null): string
    {
        if ($locale == null) {
            $locale = $this->getApp()->getCurrentLocale();
        }
        if (empty($params)) {
            return $this->getTranslator($locale)->translate($string);
        }
        return sprintf($this->getTranslator($locale)->translate($string), ...$params);
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
    public function getEncodedPass(string $pass, ?string $salt = null): string
    {
        if (is_null($salt)) {
            $salt = $this->getEnv('SALT');
        }

        return sha1($salt . $pass) . ':' . $salt;
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
        $message = $this->containerCall([QueueMessage::class, 'new']);
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
    public function randString(int $length = 10): string
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
    public function getWrappedMailBody(string $subject, string $mail_body, string $template_name = 'generic'): string
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


    /**
     * gets Authorization token header
     *
     * @return string|null
     */
    public function getAuthorizationHeader(): ?string
    {
        $token = $this->getRequest()->headers->get('Authorization');
        return (string) ($token ?: $this->getRequest()->cookies->get('Authorization'));
    }

    /**
     * gets Authorization token Object
     *
     * @return ?Token
     */
    public function getToken(): ?Token
    {
        $auth_token = $this->getAuthorizationHeader();

        /** @var Parser $parser */
        $parser = $this->getContainer()->get('jwt:configuration')->parser();

        if (preg_match("/^Bearer /", $auth_token)) {
            $auth_token = str_replace("Bearer ", "", $auth_token);
        }

        if (preg_match("/^Basic /", $auth_token)) {
            $auth_token = explode(":", base64_decode(str_replace("Basic ", "", $auth_token)));
            if (count($auth_token) == 2) {
                $user = $this->getUserByCredentials($auth_token[0], $auth_token[1]);
                if ($user) {
                    $auth_token = "" . $user->getJWT(getExisting: false);
                } else {
                    $auth_token = null;
                }
            } else {
                $auth_token = null;
            }
        }

        if (!$auth_token) {
            return null;
        }

        return $parser->parse($auth_token);
    }

    /**
     * gets token data
     *
     * @return mixed
     */
    public function getTokenUserDataClaim(): mixed
    {
        try {
            $token = $this->getToken();
            if (is_null($token)) {
                return null;
            }
            /** @var Validator $validator */
            $validator = $this->getContainer()->get('jwt:configuration')->validator();
            $constraints = $this->getContainer()->get('jwt:configuration')->validationConstraints();
            if ($validator->validate($token, ...$constraints)) {
                $claims = $token->claims();
                return (array) $claims->get('userdata');
            }
        } catch (Exception $e) {
            $this->getUtils()->logException($e);
        }

        return false;
    }

    /**
     * gets user by credentials
     * 
     * @param string $username
     * @param string $password
     * 
     * @return User|null
     */
    public function getUserByCredentials(string $username, string $password) : ?User
    {
        /** @var User $user */
        $user = User::getCollection()->addCondition([
            'username' => $username,
            'password' => $this->getEncodedPass($password),
        ])->addCondition('locked != 1 OR locked_until < NOW()')->getFirst();


        if (!$user) {
            // salt could be changed
            $userSalt = $this->getDb()->select('user', [
                'expr' => ['salt' => 'SUBSTR(password, POSITION(\':\' in password)+1)'],
                'where' => ['username = ?'],
                'params' => [$username],
                'limitCount' => 1,
            ])->fetchColumn();

            if ($userSalt) {
                $user = User::getCollection()->addCondition([
                    'username' => $username,
                    'password' => $this->getEncodedPass($password, $userSalt),
                ])->addCondition('locked != 1 OR locked_until < NOW()')->getFirst();

                if ($user) {
                    // update user encoded password to use new salt
                    $user->setPassword($this->getEncodedPass($password));
                } else {
                    return null;
                }
            }
        }

        return $user;
    }

    /**
     * gets csv string from array
     * 
     * @param array $data
     * @param array $header
     * @param string $delimiter
     * @param string $enclosure
     * @param string $escape_char
     * @return string|false
     */
    public function array2csv(array $data, array $header = [], string $delimiter = ',', string $enclosure = '"', string $escape_char = "\\") : string|false
    {
        $f = fopen('php://memory', 'r+');
        if (!empty($header)) {
            fputcsv($f, $header, $delimiter, $enclosure, $escape_char);
        }
        foreach ($data as $item) {
            fputcsv($f, $item, $delimiter, $enclosure, $escape_char);
        }
        rewind($f);
        return stream_get_contents($f);
    }

    /**
     * gets csv contents as array
     * 
     * @param string $csvFile
     * @return array
     */
    public function csv2array(string $csvFile) : array
    {
        $out = [];
        $csvHeader = null;
        if (($handle = fopen($csvFile, "r")) !== FALSE) {
            while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                if ($csvHeader == null) {
                    $csvHeader = $data;
                } else {
                    $out[] = array_combine((array) $csvHeader, $data);
                }
            }
    
            fclose($handle);
        }

        return $out;
    }

    /**
     * Creates a JSON response.
     *
     * @param mixed $data
     * @param int $status
     * @return JsonResponse
     */
    public function createJsonResponse(mixed $data, int $status = 200, ?BasePage $currentPage = null): JsonResponse
    {
        $out = new JsonResponse(
            $data,
            $status
        );

        if (!is_null($currentPage)) {
            foreach ($currentPage->getResponse()?->headers->getCookies() as $cookie) {
                $out->headers->setCookie($cookie);
            }
        }

        return $out;
    }

    /**
     * Creates an HTML response.
     *
     * @param string $content
     * @param int $status
     * @return Response
     */
    public function createHtmlResponse(string $content, int $status = 200, ?BasePage $currentPage = null): Response
    {
        $out = new Response(
            $content,
            $status,
            ['Content-Type' => 'text/html']
        );

        if (!is_null($currentPage)) {
            foreach ($currentPage->getResponse()?->headers->getCookies() as $cookie) {
                $out->headers->setCookie($cookie);
            }
        }

        return $out;
    }

    /**
     * Creates an XML response.
     *
     * @param string $content
     * @param int $status
     * @return Response
     */
    public function createXmlResponse(string $content, int $status = 200, ?BasePage $currentPage = null): Response
    {
        $out = new Response(
            $content,
            $status,
            ['Content-Type' => 'text/xml']
        );

        if (!is_null($currentPage)) {
            foreach ($currentPage->getResponse()?->headers->getCookies() as $cookie) {
                $out->headers->setCookie($cookie);
            }
        }

        return $out;
    }

    /**
     * returns a redirect object
     *
     * @param $url
     * @param array $additional_headers
     * @return RedirectResponse
     */
    public function createRedirectResponse(string $url, int $code, array $headers = []): RedirectResponse
    {
        return new RedirectResponse(
            $url,
            $code,
            $headers
        );
    }

    /**
     * Check if the current environment is CLI
     * 
     * @return bool
     */
    public static function isCli(): bool
    {
        return (php_sapi_name() === 'cli' || defined('STDIN'));
    }

    /**
     * Check if the current environment is CLI server
     * 
     * @return bool
     */
    public static function isCliServer(): bool
    {
        return (php_sapi_name() === 'cli-server');
    }

    /**
     * Check if the current environment is web
     * 
     * @return bool
     */
    public static function isWeb(): bool
    {
        return !self::isCli() && !self::isCliServer();
    }
}
