<?php
declare(strict_types = 1);

use Bref\Logger\StderrLogger;
use function DI\add;
use function DI\autowire;
use function DI\create;
use function DI\env;
use function DI\factory;
use function DI\get;
use function DI\string;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Externals\Application\Database\CustomMySQLPlatform;
use Externals\Application\Middleware\MaintenanceMiddleware;
use Externals\Search\AlgoliaSearchIndex;
use Externals\Search\SearchIndex;
use Gravatar\Twig\GravatarExtension;
use League\CommonMark\DocParser;
use League\CommonMark\Environment;
use League\CommonMark\HtmlRenderer;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Github;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use PSR7Session\Http\SessionMiddleware;

return [

    'maintenance' => env('MAINTENANCE', false),
    'debug' => false,
    'path.cache' => '/tmp/cache',

    'version' => env('EXTERNALS_APP_VERSION', factory(function () {
        $rev = shell_exec('git rev-parse HEAD');

        if (null !== $rev) {
            return trim($rev);
        }

        return null;
    })),

    'db.url' => env('DB_URL'),
    Connection::class => function (ContainerInterface $c) {
        return DriverManager::getConnection([
            'url' => $c->get('db.url'),
            'charset' => 'utf8mb4',
            'platform' => new CustomMySQLPlatform,
            'driverOptions' => [
                PDO::ATTR_TIMEOUT => 10,
            ],
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

    LoggerInterface::class => create(StderrLogger::class),

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

    AbstractProvider::class => create(Github::class)
        ->constructor(get('oauth.github.config')),
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
    SessionMiddleware::class => function (ContainerInterface $c) {
        $key = (string) $c->get('session.secret_key');
        return SessionMiddleware::fromSymmetricKeyDefaults($key, 31536000);
    },

    'sentry.url' => env('SENTRY_URL', null),

    MaintenanceMiddleware::class => autowire()
        ->constructorParameter('maintenance', get('maintenance')),

];
