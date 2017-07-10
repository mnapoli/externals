<?php
declare(strict_types = 1);

use function DI\add;
use function DI\env;
use function DI\factory;
use function DI\get;
use function DI\object;
use function DI\string;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Externals\Application\Database\CustomMySQLPlatform;
use Externals\Application\Middleware\MaintenanceMiddleware;
use Externals\Search\AlgoliaSearchIndex;
use Externals\Search\SearchIndex;
use Gravatar\Twig\GravatarExtension;
use Interop\Container\ContainerInterface;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Psr\Log\LoggerInterface;
use PSR7Session\Http\SessionMiddleware;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;

return [

    'maintenance' => env('MAINTENANCE', false),
    'debug' => false,
    'path.cache' => __DIR__ . '/../../var/cache',
    'path.logs' => __DIR__ . '/../../var/log',

    'version' => env('PLATFORM_TREE_ID', factory(function () {
        return trim(shell_exec('git rev-parse HEAD'));
    })),

    'db.url' => env('DB_URL'),
    Connection::class => function (ContainerInterface $c) {
        return DriverManager::getConnection([
            'url' => $c->get('db.url'),
            'charset' => 'utf8mb4',
            'platform' => new CustomMySQLPlatform,
        ]);
    },

    'twig.paths' => [
        'app' => __DIR__ . '/../views',
    ],
    'twig.options' => [
        'cache' => string('{path.cache}/twig'),
        'auto_reload' => true,
    ],
    'twig.globals' => [
        'debug' => get('debug'),
        'version' => get('version'),
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
        'clientId' => env('GITHUB_OAUTH_CLIENT_ID'),
        'clientSecret' => env('GITHUB_OAUTH_CLIENT_SECRET'),
        'redirectUri' => env('GITHUB_OAUTH_REDIRECT_URL'),
    ],

    'algolia.index_prefix' => env('ALGOLIA_INDEX_PREFIX', 'dev_'),
    \AlgoliaSearch\Client::class => object()
        ->constructor(env('ALGOLIA_APP_ID'), env('ALGOLIA_API_KEY')),
    SearchIndex::class => object(AlgoliaSearchIndex::class)
        ->constructorParameter('indexPrefix', get('algolia.index_prefix')),

    'session.secret_key' => env('SESSION_SECRET_KEY'),
    SessionMiddleware::class => function (ContainerInterface $c) {
        $key = (string) $c->get('session.secret_key');
        return SessionMiddleware::fromSymmetricKeyDefaults($key, 31536000);
    },

    'sentry.url' => env('SENTRY_URL', null),

    MaintenanceMiddleware::class => object()
        ->constructorParameter('maintenance', get('maintenance')),

];
