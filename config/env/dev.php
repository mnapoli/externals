<?php
declare(strict_types = 1);

use Bref\Logger\StderrLogger;
use Dflydev\FigCookies\SetCookie;
use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Psr\Container\ContainerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use function DI\create;
use function DI\get;

return [

    'debug' => true,

    'assetsBaseUrl' => '',

    'twig.options' => [
        'debug' => get('debug'),
        'cache' => false,
        'strict_variables' => true,
    ],

    'algolia.index_prefix' => 'dev_',

    LoggerInterface::class => create(StderrLogger::class)
        ->constructor(LogLevel::INFO),

    // Allows using the session without HTTPS (for dev purposes)
    SessionMiddleware::class => function (ContainerInterface $c) {
        $key = (string) $c->get('session.secret_key');
        return new SessionMiddleware(
            new Sha256,
            $key,
            $key,
            // Override the default `__Secure-slsession` which only works on HTTPS
            SetCookie::create('slsession')
                // Disable mandatory HTTPS
                ->withSecure(false)
                ->withHttpOnly(true)
                ->withPath('/'),
            new Parser,
            31536000,
            SystemClock::fromUTC(),
        );
    },

];
