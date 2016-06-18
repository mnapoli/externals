<?php
declare(strict_types = 1);

use function DI\add;
use function DI\env;
use function DI\get;
use function DI\object;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\DriverManager;
use Imapi\Client;
use Interop\Container\ContainerInterface;
use Monolog\Logger;
use Ornicar\GravatarBundle\Templating\Helper\GravatarHelper;
use Ornicar\GravatarBundle\Templating\Helper\GravatarHelperInterface;
use Ornicar\GravatarBundle\Twig\GravatarExtension;
use Psr\Log\LoggerInterface;
use Symfony\Bridge\Monolog\Formatter\ConsoleFormatter;
use Symfony\Bridge\Monolog\Handler\ConsoleHandler;

return [

    'debug' => false,

    Client::class => function(ContainerInterface $c) {
        $config = $c->get('imap.config');
        return Client::connect($config['host'], $config['user'], $config['password'], $config['port'], $config['security']);
    },

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

    LoggerInterface::class => object(Logger::class)
        ->constructor('app', get('logger.handlers')),
    'logger.handlers' => [
        get(ConsoleHandler::class),
    ],
    ConsoleHandler::class => object()
        ->method('setFormatter', get(ConsoleFormatter::class)),

];
