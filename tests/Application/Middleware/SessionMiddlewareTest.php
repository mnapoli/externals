<?php
declare(strict_types = 1);

namespace Externals\Test\Application\Middleware;

use Externals\Application\Middleware\SessionMiddleware;
use Psr\Http\Message\ServerRequestInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;

class SessionMiddlewareTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @test
     */
    public function should_add_session_in_request()
    {
        $next = function (ServerRequestInterface $request) {
            // Check that the session is now in the request
            $this->assertInstanceOf(SessionInterface::class, $request->getAttribute(SessionInterface::class));
            return new TextResponse('Hello');
        };

        $response = (new SessionMiddleware)->__invoke(new ServerRequest, $next);

        // Check that next was called
        $this->assertEquals('Hello', $response->getBody()->getContents());
    }
}
