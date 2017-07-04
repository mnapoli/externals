<?php
declare(strict_types = 1);

use Dflydev\FigCookies\SetCookie;
use Interop\Container\ContainerInterface;
use Lcobucci\JWT\Parser;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use PSR7Session\Http\SessionMiddleware;
use PSR7Session\Time\SystemCurrentTime;
use function DI\get;

return [

    'debug' => true,

    'twig.options' => [
        'debug' => get('debug'),
        'cache' => false,
        'strict_variables' => true,
    ],

    'algolia.index_prefix' => 'dev_',

    // Allows using the session without HTTPS (for dev purposes)
    SessionMiddleware::class => function (ContainerInterface $c) {
        $config = $c->get('session');
        return new SessionMiddleware(
            new Sha256,
            (string) $config['key'],
            (string) $config['key'],
            SetCookie::create(SessionMiddleware::DEFAULT_COOKIE)
                ->withSecure(false) // THIS IS THE SECURE FLAG WE SET TO FALSE
                ->withHttpOnly(true)
                ->withPath('/'),
            new Parser,
            (int) $config['lifetime'],
            new SystemCurrentTime
        );
    },

];
