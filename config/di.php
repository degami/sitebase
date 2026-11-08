<?php

return [
    'dbhost' => DI\env('DATABASE_HOST'),
    'dbname' => DI\env('DATABASE_NAME'),
    'dbuser' => DI\env('DATABASE_USER'),
    'dbpass' => DI\env('DATABASE_PASS'),
    'smtp_host' => DI\env('SMTP_HOST'),
    'smtp_port' => DI\env('SMTP_PORT'),
    'smtp_enc' => DI\env('SMTP_ENC'),
    'smtp_user' => DI\env('SMTP_USER'),
    'smtp_pass' => DI\env('SMTP_PASS'),
    'ses_profile' => DI\env('SES_PROFILE'),
    'ses_region' => DI\env('SES_REGION'),
    'jwt_issuer' => 'issuer',
    'jwt_audience' => 'audience',
    'jwt_id' => 'id',
    'cache_default_driver' => 'Files',
    'debug' => DI\env('DEBUG'),

    \App\Base\Environment\Manager::class => DI\autowire(\App\Base\Environment\Manager::class),
    'environment' => DI\get(\App\Base\Environment\Manager::class),

    \App\Base\Tools\Utils\ClassFinder::class => DI\autowire(\App\Base\Tools\Utils\ClassFinder::class),
    'classfinder' => DI\get(\App\Base\Tools\Utils\ClassFinder::class),

    // 'log'
    \Monolog\Logger::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $debug = boolval($c->get('environment')->getVariable('DEBUG'));
        $logpath = \App\App::getDir(\App\App::ROOT) . DS . "var" . DS . "log" . DS . "logger.log";
        $logger = new \Monolog\Logger('logger');
        $fileHandler = new \Monolog\Handler\StreamHandler(
            $logpath,
            $debug ? \Monolog\Logger::DEBUG : \Monolog\Logger::ERROR
        );
        $fileHandler->setFormatter(new \Monolog\Formatter\LineFormatter());
        $logger->pushHandler($fileHandler);
        return $logger;
    }),
    'log' => DI\get(\Monolog\Logger::class),

    // 'pdo'
    \PDO::class => DI\Factory(function (\Psr\Container\ContainerInterface $c) {

        $dbhost = $c->get('environment')->getVariable('DATABASE_HOST');
        $dbname = $c->get('environment')->getVariable('DATABASE_NAME');
        $dbuser = $c->get('environment')->getVariable('DATABASE_USER');
        $dbpass = $c->get('environment')->getVariable('DATABASE_PASS');

        if (empty($dbhost) || empty($dbname) || empty($dbuser)) {
            return null;
        }

        $dsn = "mysql:dbname={$dbname};host={$dbhost}";
        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        ];
        $pdo = new \PDO($dsn, $dbuser, $dbpass, $options);
        return $pdo;
    }),
    'pdo' => DI\get(\PDO::class),

    // 'db'
    \LessQL\Database::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $pdo = $c->get('pdo');
        if ($pdo === null) {
            return null;
        }

        $db = new \LessQL\Database($pdo);
        return $db;
    }),
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
            \Lcobucci\JWT\Signer\Key\InMemory::file(\App\App::getDir(\App\App::ASSETS) . '/rsa_private.pem')
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
    \Degami\SqlSchema\Schema::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $pdo = $c->get('pdo');
        if ($pdo === null) {
            return null;
        }

        $schema = new \Degami\SqlSchema\Schema($pdo);
        return $schema;
    }),
    'schema' => DI\get(\Degami\SqlSchema\Schema::class),

    // 'forms'
    \Degami\PHPFormsApi\FormBuilder::class => DI\create(\Degami\PHPFormsApi\FormBuilder::class),
    'forms' => DI\get(\Degami\PHPFormsApi\FormBuilder::class),

    // 'icons'
    \Feather\Icons::class => DI\create(\Feather\Icons::class),
    'icons' => DI\get(\Feather\Icons::class),

    // 'debugbar'
    \DebugBar\StandardDebugBar::class => DI\create(\DebugBar\StandardDebugBar::class),
    'debugbar' => DI\get(\DebugBar\StandardDebugBar::class),

    // 'traceable_pdo'
    \DebugBar\DataCollector\PDO\TraceablePDO::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $pdo = $c->get('pdo');
        if ($pdo === null) {
            return null;
        }

        $traceablePdo = new \DebugBar\DataCollector\PDO\TraceablePDO($pdo);
        return $traceablePdo;
    }),
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
    \Degami\EventManager\SharedEventEmitter::class => DI\create(\Degami\EventManager\SharedEventEmitter::class),
    'event_manager' => DI\get(\Degami\EventManager\SharedEventEmitter::class),

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
    \Swift_SmtpTransport::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $smtp_host = $c->get('environment')->getVariable('SMTP_HOST');
        $smtp_port = $c->get('environment')->getVariable('SMTP_PORT');
        $smtp_enc = $c->get('environment')->getVariable('SMTP_ENC');
        $smtp_user = $c->get('environment')->getVariable('SMTP_USER');
        $smtp_pass = $c->get('environment')->getVariable('SMTP_PASS');

        $transport = new \Swift_SmtpTransport(
            $smtp_host,
            $smtp_port,
            $smtp_enc
        );
        if (!empty($smtp_user)) {
            $transport->setUsername($smtp_user);
        }
        if (!empty($smtp_pass)) {
            $transport->setPassword($smtp_pass);
        }

        $mailer = new \Swift_Mailer($transport);
        return $mailer;
    }),
    'smtp_mailer' => DI\get(\Swift_SmtpTransport::class),

    // 'ses_mailer'
    \Aws\Ses\SesClient::class => DI\factory(function (\Psr\Container\ContainerInterface $c) {
        $ses_profile = $c->get('environment')->getVariable('SES_PROFILE');
        $ses_region = $c->get('environment')->getVariable('SES_REGION');
        $SesClient = new \Aws\Ses\SesClient([
            'profile' => $ses_profile ?? 'default',
            'version' => '2010-12-01',
            'region'  => $ses_region
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

    \App\Base\Tools\Utils\GZip::class => DI\autowire(\App\Base\Tools\Utils\GZip::class),
    'gzip' => DI\get(\App\Base\Tools\Utils\GZip::class),

    \App\Base\Tools\ApplicationLogger\Manager::class => DI\autowire(\App\Base\Tools\ApplicationLogger\Manager::class),
    'application_logger' => DI\get(\App\Base\Tools\ApplicationLogger\Manager::class),

    \App\Base\Auth\Manager::class => DI\autowire(\App\Base\Auth\Manager::class),
    'auth' => DI\get(\App\Base\Auth\Manager::class),

    \App\Base\Tools\AI\Manager::class => DI\autowire(\App\Base\Tools\AI\Manager::class),
    'ai' => DI\get(\App\Base\Tools\AI\Manager::class),

    \App\Base\Tools\Utils\Geocoder::class => DI\autowire(\App\Base\Tools\Utils\Geocoder::class),
    'geocoder' => DI\get(\App\Base\Tools\Utils\Geocoder::class),
];
