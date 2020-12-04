<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Framework\Controller;
use Externals\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Session\Http\SessionMiddleware;
use PSR7Session\Session\SessionInterface;
use Twig\Environment;

class LogoutController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private Environment $twig,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        /** @var SessionInterface $session */
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $session->remove('user');
        return $this->redirectResponse('/');
    }
}
