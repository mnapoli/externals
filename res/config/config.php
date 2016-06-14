<?php
declare(strict_types = 1);

use function DI\add;
use function DI\env;
use function DI\get;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Imapi\Client;
use Interop\Container\ContainerInterface;
use Ornicar\GravatarBundle\Templating\Helper\GravatarHelper;
use Ornicar\GravatarBundle\Templating\Helper\GravatarHelperInterface;
use Ornicar\GravatarBundle\Twig\GravatarExtension;

return [

    'imap.config' => [
        'host' => env('IMAP_HOST'),
        'user' => env('IMAP_USER'),
        'password' => env('IMAP_PASSWORD'),
        'port' => env('IMAP_PORT'),
        'security' => env('IMAP_SECURITY'),
    ],
    Client::class => function(ContainerInterface $c) {
        $config = $c->get('imap.config');
        return Client::connect($config['host'], $config['user'], $config['password'], $config['port'], $config['security']);
    },

    'db.url' => env('DB_URL'),
    Connection::class => function (ContainerInterface $c) {
        return DriverManager::getConnection([
            'url' => $c->get('db.url'),
        ]);
    },

    'twig.extensions' => add([
        get(Twig_Extensions_Extension_Date::class),
        get(GravatarExtension::class),
    ]),

    GravatarHelperInterface::class => DI\object(GravatarHelper::class),

];
