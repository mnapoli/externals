<?php
declare(strict_types = 1);

use function DI\add;
use function DI\env;
use function DI\get;
use function DI\object;
use function DI\string;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Gravatar\Twig\GravatarExtension;
use Imapi\Client;
use Interop\Container\ContainerInterface;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;

return [

    'debug' => false,
    'path.cache' => __DIR__ . '/../../var/cache',
    'path.logs' => __DIR__ . '/../../var/log',

    Client::class => function (ContainerInterface $c) {
        $config = $c->get('imap.config');
        return Client::connect($config['host'], $config['user'], $config['password'], (string) $config['port'],
            $config['security']);
    },

    Connection::class => function (ContainerInterface $c) {
        return DriverManager::getConnection([
            'url' => $c->get('db.url'),
        ]);
    },

    'twig.options' => [
        'cache' => string('{path.cache}/twig'),
        'auto_reload' => true,
    ],
    'twig.globals' => [
        'debug' => get('debug'),
    ],
    'twig.extensions' => add([
        get(Twig_Extensions_Extension_Date::class),
        get(GravatarExtension::class),
    ]),

    LoggerInterface::class => object(Logger::class)
        ->constructor('app', get('logger.handlers')),
    'logger.handlers' => [
        get(ConsoleHandler::class),
        get('logger.file_handler'),
    ],
    ConsoleHandler::class => object()
        ->method('setFormatter', get(ConsoleFormatter::class)),
    'logger.file_handler' => object(StreamHandler::class)
        ->constructor(string('{path.logs}/app.log'), Logger::INFO),

    DocParser::class => function (ContainerInterface $c) {
        return new DocParser($c->get(Environment::class));
    },
    HtmlRenderer::class => function (ContainerInterface $c) {
        return new HtmlRenderer($c->get(Environment::class));
    },
    Environment::class => function () {
        $environment = Environment::createCommonMarkEnvironment();
        $environment->mergeConfig([
            'renderer' => [
                'soft_break' => " <br>\n",
            ],
            'html_input' => Environment::HTML_INPUT_ESCAPE,
            'allow_unsafe_links' => false,
        ]);
        return $environment;
    },

    AbstractProvider::class => object(Github::class)
        ->constructor(get('oauth.github.config')),
    'oauth.github.config' => [
        'clientId' => get('github.oauth.client_id'),
        'clientSecret' => get('github.oauth.client_secret'),
        'redirectUri' => get('github.oauth.redirect_url'),
    ],

    \AlgoliaSearch\Client::class => object()
        ->constructor(get('algolia.app_id'), get('algolia.api_key')),

];
