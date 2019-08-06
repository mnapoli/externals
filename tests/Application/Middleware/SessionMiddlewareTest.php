<?php declare(strict_types=1);

namespace Externals\Test\Application\Middleware;

use Externals\Application\Middleware\SessionMiddleware;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Session\Http\SessionMiddleware as Psr7Middleware;
use PSR7Session\Session\SessionInterface;
use Zend\Diactoros\Response\TextResponse;
use Zend\Diactoros\ServerRequest;

class SessionMiddlewareTest extends TestCase
{
    /**
     * @test
     */
    public function should_add_session_in_request()
    {
        $next = function (ServerRequestInterface $request) {
            // Check that the session is now in the request
            $this->assertInstanceOf(SessionInterface::class, $request->getAttribute(Psr7Middleware::SESSION_ATTRIBUTE));
            return new TextResponse('Hello');
        };

        $response = (new SessionMiddleware(
            Psr7Middleware::fromSymmetricKeyDefaults('the-key', 3600)
        ))->__invoke(new ServerRequest, $next);

        // Check that next was called
        $this->assertEquals('Hello', $response->getBody()->getContents());
    }
}
