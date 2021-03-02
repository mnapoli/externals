<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Micro\Controller;
use Externals\User\UserRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use PSR7Sessions\Storageless\Session\SessionInterface;
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
