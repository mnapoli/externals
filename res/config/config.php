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
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;

return [

    'debug' => false,
    'path.cache' => __DIR__ . '/../../var/cache',

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
    'twig.extensions' => add([
        get(Twig_Extensions_Extension_Date::class),
        get(GravatarExtension::class),
    ]),

    LoggerInterface::class => object(Logger::class)
        ->constructor('app', get('logger.handlers')),
    'logger.handlers' => [
        get(ConsoleHandler::class),
    ],
    ConsoleHandler::class => object()
        ->method('setFormatter', get(ConsoleFormatter::class)),

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

];
