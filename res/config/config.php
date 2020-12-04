<?php
declare(strict_types = 1);

use Bref\Logger\StderrLogger;
use Externals\RssBuilder;
use Externals\RssRfcBuilder;
use Gravatar\Gravatar;
use League\CommonMark\CommonMarkConverter;
use League\CommonMark\EnvironmentInterface;
use Psr\Log\LogLevel;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use function DI\autowire;
use function DI\create;
use function DI\env;
use function DI\get;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Externals\Application\Database\CustomMySQLPlatform;
use Externals\Search\AlgoliaSearchIndex;
use Externals\Search\SearchIndex;
use Gravatar\Twig\GravatarExtension;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use Psr\Container\ContainerInterface as Container;
use Psr\Log\LoggerInterface;

return [

    'maintenance' => env('MAINTENANCE', false),
    'debug' => false,
    'path.cache' => '/tmp/cache',

    'version' => env('EXTERNALS_APP_VERSION', ''),

    'db.url' => env('DB_URL'),
    Connection::class =>
        fn(Container $c) => DriverManager::getConnection([
            'url' => $c->get('db.url'),
            'charset' => 'utf8mb4',
            'platform' => new CustomMySQLPlatform(),
            'driverOptions' => [
                PDO::ATTR_TIMEOUT => 10,
            ],
        ]),

    Environment::class => function (Container $c) {
        $loader = new FilesystemLoader(__DIR__ . '/../views');
        $twig = new Environment($loader, [
            'cache' => $c->get('path.cache') . '/twig',
            'auto_reload' => true,
            'globals' => [
                'debug' => $c->get('debug'),
                'version' => $c->get('version'),
            ]
        ]);
        $twig->addExtension(new Twig_Extensions_Extension_Date);
        $twig->addExtension(new GravatarExtension(new Gravatar()));
        return $twig;
    },

    LoggerInterface::class =>
        fn() => new StderrLogger(LogLevel::INFO),

    CommonMarkConverter::class =>
        fn() => new CommonMarkConverter([
            'renderer' => [
                'soft_break' => " <br>\n",
            ],
            'html_input' => EnvironmentInterface::HTML_INPUT_ESCAPE,
            'allow_unsafe_links' => false,
        ]),

    AbstractProvider::class =>
        fn(Container $c) => new Github($c->get('oauth.github.config')),
    'oauth.github.config' => [
        'clientId' => env('GITHUB_OAUTH_CLIENT_ID'),
        'clientSecret' => env('GITHUB_OAUTH_CLIENT_SECRET'),
        'redirectUri' => env('GITHUB_OAUTH_REDIRECT_URL'),
    ],

    'algolia.index_prefix' => env('ALGOLIA_INDEX_PREFIX', 'dev_'),
    \AlgoliaSearch\Client::class => create()
        ->constructor(env('ALGOLIA_APP_ID'), env('ALGOLIA_API_KEY')),
    SearchIndex::class => autowire(AlgoliaSearchIndex::class)
        ->constructorParameter('indexPrefix', get('algolia.index_prefix')),

    'session.secret_key' => env('SESSION_SECRET_KEY'),
    SessionMiddleware::class =>
        fn (Container $c) => SessionMiddleware::fromSymmetricKeyDefaults(
            (string) $c->get('session.secret_key'),
            31536000
        ),

    'sentry.url' => env('SENTRY_URL', null),

    'rss.host' => env('RSS_HOST', 'https://externals.io'),
    RssBuilder::class => create()
        ->constructor(get('rss.host')),
    RssRfcBuilder::class => create()
        ->constructor(get('rss.host')),

    'http.middlewares' => [
        SessionMiddleware::class,
    ],
];
