<?php

return [
    'dbhost' => DI\env('DATABASE_HOST'),
    'dbname' => DI\env('DATABASE_NAME'),
    'dbuser' => DI\env('DATABASE_USER'),
    'dbpass' => DI\env('DATABASE_PASS'),
    'jwt_issuer' => 'issuer',
    'jwt_audience' => 'audience',
    'jwt_id' => 'id',
    'cache_default_driver' => 'Files',

    'log' => DI\factory(function () {
        $logpath =
            \App\App::getDir(\App\App::ROOT) . DS .
            "var".DS.
            "log".DS.
            "logger.log";
        $logger = new \Monolog\Logger('logger');
        $fileHandler = new \Monolog\Handler\StreamHandler(
            $logpath,
            getenv('DEBUG') ? \Monolog\Logger::DEBUG : \Monolog\Logger::ERROR
        );
        $fileHandler->setFormatter(new \Monolog\Formatter\LineFormatter());
        $logger->pushHandler($fileHandler);
        return $logger;
    }),
    'pdo' => DI\autowire(\PDO::class)
                ->constructor(
                    DI\string("mysql:dbname={dbname};host={dbhost}"),
                    DI\string('{dbuser}'),
                    DI\string('{dbpass}'),
                    []
                ),
    'db' => DI\autowire(\LessQL\Database::class)->constructor(DI\get('pdo')),
    'templates' => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $engine = new \League\Plates\Engine();
        $engine->loadExtension(new \App\Base\Tools\Plates\SiteBase($c));

        return $engine;
    }),
    'jwt:builder' => DI\create(\Lcobucci\JWT\Builder::class),
    'jwt:parser' => DI\create(\Lcobucci\JWT\Parser::class),
    'imagine' => DI\create(\Imagine\Gd\Imagine::class),
    'schema' => DI\create(\Degami\SqlSchema\Schema::class),
    'forms' => DI\create(\Degami\PhpFormApi\FormBuilder::class),
    'icons' => DI\create(\Feather\Icons::class),
    'debugbar' => DI\create(\DebugBar\StandardDebugBar::class),
    'traceable_pdo' => DI\autowire(DebugBar\DataCollector\PDO\TraceablePDO::class)
                            ->constructor(DI\get('pdo')),
    'db_collector' => DI\autowire(\DebugBar\DataCollector\PDO\PDOCollector::class)
                            ->constructor(DI\get('traceable_pdo')),
    'monolog_collector' => DI\autowire(\DebugBar\Bridge\MonologCollector::class)
                            ->constructor(DI\get('log')),
    'event_manager' => DI\create(\Gplanchat\EventManager\SharedEventEmitter::class),
    'routing' => DI\autowire(\App\Site\Routing\Web::class),
    'site_data' => DI\autowire(\App\Base\Tools\Utils\SiteData::class),
    'html_renderer' => DI\autowire(\App\Base\Tools\Utils\HtmlPartsRenderer::class),
    'utils' => DI\autowire(\App\Base\Tools\Utils\Globals::class),
    'assets' => DI\autowire(\App\Base\Tools\Assets\Manager::class),
    'smtp_mailer' => DI\factory(function () {
        $transport = new \Swift_SmtpTransport(
            getenv("SMTP_HOST"),
            getenv('SMTP_PORT'),
            getenv('SMTP_ENC')
        );
        if (!empty(getenv('SMTP_USER'))) {
            $transport->setUsername(getenv('SMTP_USER'));
        }
        if (!empty(getenv('SMTP_PASS'))) {
            $transport->setPassword(getenv('SMTP_PASS'));
        }

        $mailer = new \Swift_Mailer($transport);
        return $mailer;
    }),
    'ses_mailer' => DI\factory(function () {
        $SesClient = new \Aws\Ses\SesClient([
            'profile' => getenv('SES_PROFILE') ?? 'default',
            'version' => '2010-12-01',
            'region'  => getenv('SES_REGION')
        ]);

        return $SesClient;
    }),
    'mailer' => DI\autowire(\App\Base\Tools\Utils\Mailer::class),
    'cache_engine' => DI\factory(function () {
        $config = new \Phpfastcache\Drivers\Files\Config([
            'path' =>   \App\App::getDir(\App\App::ROOT).DS.
                        'var'.DS.
                        'cache',
        ]);
        $config->setSecurityKey(str_replace(" ", "_", strtolower(getenv('APPNAME'))));
        $cache = \Phpfastcache\CacheManager::getInstance('Files', $config);

        return $cache;
    }),
    'cache' => DI\autowire(\App\Base\Tools\Cache\Manager::class),
    'guzzle' => DI\create(\GuzzleHttp\Client::class),
];
