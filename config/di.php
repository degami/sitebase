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

    // 'log'
    \Monolog\Logger::class => DI\factory(function () {
        $logpath = \App\App::getDir(\App\App::ROOT) . DS . "var" . DS . "log" . DS . "logger.log";
        $logger = new \Monolog\Logger('logger');
        $fileHandler = new \Monolog\Handler\StreamHandler(
            $logpath,
            getenv('DEBUG') ? \Monolog\Logger::DEBUG : \Monolog\Logger::ERROR
        );
        $fileHandler->setFormatter(new \Monolog\Formatter\LineFormatter());
        $logger->pushHandler($fileHandler);
        return $logger;
    }),
    'log' => DI\get(\Monolog\Logger::class),

    // 'pdo'
    \PDO::class => DI\autowire(\PDO::class)
        ->constructor(
            DI\string("mysql:dbname={dbname};host={dbhost}"),
            DI\string('{dbuser}'),
            DI\string('{dbpass}'),
            []
        ),
    'pdo' => DI\get(\PDO::class),

    // 'db'
    \LessQL\Database::class => DI\autowire(\LessQL\Database::class)
        ->constructor(DI\get('pdo')),
    'db' => DI\get(\LessQL\Database::class),

    // 'templates'
    \League\Plates\Engine::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $engine = new \League\Plates\Engine();
        $engine->loadExtension(new \App\Base\Tools\Plates\SiteBase($c));

        return $engine;
    }),
    'templates' => DI\get(\League\Plates\Engine::class),

    \Lcobucci\JWT\Configuration::class => DI\factory(function(\Psr\Container\ContainerInterface $c){
        $configuration = \Lcobucci\JWT\Configuration::forSymmetricSigner(
        // You may use RSA or ECDSA and all their variations (256, 384, and 512) and EdDSA over Curve25519
            new \Lcobucci\JWT\Signer\Hmac\Sha256(),
            //\Lcobucci\JWT\Signer\Ecdsa\Sha256::create(),
            \Lcobucci\JWT\Signer\Key\LocalFileReference::file(\App\App::getDir(\App\App::ASSETS) . '/rsa_private.pem')
        );

        $configuration->setValidationConstraints(
            new \Lcobucci\JWT\Validation\Constraint\IssuedBy($c->get('jwt_issuer')),
            new \Lcobucci\JWT\Validation\Constraint\PermittedFor($c->get('jwt_audience'))
        );

        return $configuration;
    }),
    'jwt:configuration' => DI\get(\Lcobucci\JWT\Configuration::class),

    // 'imagine'
    \Imagine\Gd\Imagine::class => DI\create(\Imagine\Gd\Imagine::class),
    'imagine' => DI\get(\Imagine\Gd\Imagine::class),

    // 'schema'
    \Degami\SqlSchema\Schema::class => DI\autowire(\Degami\SqlSchema\Schema::class)
        ->constructor(DI\get("pdo")),
    'schema' => DI\get(\Degami\SqlSchema\Schema::class),

    // 'forms'
    \Degami\PHPFormsApi\FormBuilder::class => DI\create(\Degami\PhpFormApi\FormBuilder::class),
    'forms' => DI\get(\Degami\PHPFormsApi\FormBuilder::class),

    // 'icons'
    \Feather\Icons::class => DI\create(\Feather\Icons::class),
    'icons' => DI\get(\Feather\Icons::class),

    // 'debugbar'
    \DebugBar\StandardDebugBar::class => DI\create(\DebugBar\StandardDebugBar::class),
    'debugbar' => DI\get(\DebugBar\StandardDebugBar::class),

    // 'traceable_pdo'
    \DebugBar\DataCollector\PDO\TraceablePDO::class => DI\autowire(DebugBar\DataCollector\PDO\TraceablePDO::class)
        ->constructor(DI\get('pdo')),
    'traceable_pdo' => DI\get(\DebugBar\DataCollector\PDO\TraceablePDO::class),

    // 'db_collector'
    \DebugBar\DataCollector\PDO\PDOCollector::class => DI\autowire(\DebugBar\DataCollector\PDO\PDOCollector::class)
        ->constructor(DI\get('traceable_pdo')),
    'db_collector' => DI\get(\DebugBar\DataCollector\PDO\PDOCollector::class),

    // 'monolog_collector'
    \DebugBar\Bridge\MonologCollector::class => DI\autowire(\DebugBar\Bridge\MonologCollector::class)
        ->constructor(DI\get('log')),
    'monolog_collector' => DI\get(\DebugBar\Bridge\MonologCollector::class),

    // 'event_manager'
    \Gplanchat\EventManager\SharedEventEmitter::class => DI\create(\Gplanchat\EventManager\SharedEventEmitter::class),
    'event_manager' => DI\get(\Gplanchat\EventManager\SharedEventEmitter::class),

    // 'web_router'
    \App\Site\Routing\Web::class => DI\autowire(\App\Site\Routing\Web::class),
    'web_router' => DI\get(\App\Site\Routing\Web::class),

    // 'crud_router'
    \App\Site\Routing\Crud::class => DI\autowire(\App\Site\Routing\Crud::class),
    'crud_router' => DI\get(\App\Site\Routing\Crud::class),

    // 'webhooks_router'
    \App\Site\Routing\Webhooks::class => DI\autowire(\App\Site\Routing\Webhooks::class),
    'webhooks_router' => DI\get(\App\Site\Routing\Webhooks::class),

    // 'graphql_router'
    \App\Site\Routing\Graphql::class => DI\autowire(\App\Site\Routing\Graphql::class),
    'graphql_router' => DI\get(\App\Site\Routing\Graphql::class),

    // 'routers'
    'routers' => [
        'web_router',
        'crud_router',
        'webhooks_router',
        'graphql_router',
    ],

    // 'site_data'
    \App\Base\Tools\Utils\SiteData::class => DI\autowire(\App\Base\Tools\Utils\SiteData::class),
    'site_data' => DI\get(\App\Base\Tools\Utils\SiteData::class),

    // 'html_renderer'
    \App\Base\Tools\Utils\HtmlPartsRenderer::class => DI\autowire(\App\Base\Tools\Utils\HtmlPartsRenderer::class),
    'html_renderer' => DI\get(\App\Base\Tools\Utils\HtmlPartsRenderer::class),

    // 'utils'
    \App\Base\Tools\Utils\Globals::class => DI\autowire(\App\Base\Tools\Utils\Globals::class),
    'utils' => DI\get(\App\Base\Tools\Utils\Globals::class),

    // 'assets'
    \App\Base\Tools\Assets\Manager::class => DI\autowire(\App\Base\Tools\Assets\Manager::class),
    'assets' => DI\get(\App\Base\Tools\Assets\Manager::class),

    // 'smtp_mailer'
    \Swift_SmtpTransport::class => DI\factory(function () {
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
    'smtp_mailer' => DI\get(\Swift_SmtpTransport::class),

    // 'ses_mailer'
    \Aws\Ses\SesClient::class => DI\factory(function () {
        $SesClient = new \Aws\Ses\SesClient([
            'profile' => getenv('SES_PROFILE') ?? 'default',
            'version' => '2010-12-01',
            'region'  => getenv('SES_REGION')
        ]);

        return $SesClient;
    }),
    'ses_mailer' => DI\get(\Aws\Ses\SesClient::class),

    // 'mailer'
    \App\Base\Tools\Utils\Mailer::class => DI\autowire(\App\Base\Tools\Utils\Mailer::class),
    'mailer' => DI\get(\App\Base\Tools\Utils\Mailer::class),

    // 'cache'
    \App\Base\Tools\Cache\Manager::class => DI\autowire(\App\Base\Tools\Cache\Manager::class),
    'cache' => DI\get(\App\Base\Tools\Cache\Manager::class),

    // 'guzzle'
    \GuzzleHttp\Client::class => DI\create(\GuzzleHttp\Client::class),
    'guzzle' => DI\get(\GuzzleHttp\Client::class),

    \App\Base\Tools\Redis\Manager::class => DI\autowire(\App\Base\Tools\Redis\Manager::class),
    'redis' => DI\get(\App\Base\Tools\Redis\Manager::class),

    \App\Base\Tools\Search\Manager::class => DI\autowire(\App\Base\Tools\Search\Manager::class),
    'search' => DI\get(\App\Base\Tools\Search\Manager::class),

    \PHPGangsta_GoogleAuthenticator::class => DI\create(PHPGangsta_GoogleAuthenticator::class),
    'googleauthenticator' => DI\get(\PHPGangsta_GoogleAuthenticator::class),

    \App\Base\Tools\Utils\Zip::class => DI\autowire(\App\Base\Tools\Utils\Zip::class),
    'zip' => DI\get(\App\Base\Tools\Utils\Zip::class),

    // 'request'
    \Symfony\Component\HttpFoundation\Request::class => DI\factory(function(){
        return \Symfony\Component\HttpFoundation\Request::createFromGlobals();
    }),
    'request' => DI\get(\Symfony\Component\HttpFoundation\Request::class),
];
