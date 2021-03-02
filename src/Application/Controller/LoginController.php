<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Micro\Controller;
use Externals\User\User;
use Externals\User\UserRepository;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Sessions\Storageless\Http\SessionMiddleware;
use PSR7Sessions\Storageless\Session\SessionInterface;
use Twig\Environment;

class LoginController extends Controller
{
    public function __construct(
        private UserRepository $userRepository,
        private Environment $twig,
        private AbstractProvider $authProvider
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        assert($session instanceof SessionInterface);

        // If already logged in
        $user = $session->get('user');
        if ($user instanceof User) {
            return $this->redirectResponse('/');
        }

        $code = $this->queryParameter($request, 'code');
        if (! $code) {
            // Get an authorization code from GitHub
            $redirectUrl = $this->authProvider->getAuthorizationUrl();
            $state = $this->authProvider->getState();
            $session->set('oauth2state', $state);
            return $this->redirectResponse($redirectUrl);
        }

        // Check given state against previously stored one to mitigate CSRF attack
        $requestOauthState = $this->queryParameter($request, 'state');
        $sessionOauthState = $session->get('oauth2state');
        if (empty($requestOauthState) || ($requestOauthState !== $sessionOauthState)) {
            $session->remove('oauth2state');
            return $this->htmlResponse($this->twig->render('auth/login-error.html.twig', [
                'error' => 'Invalid state',
            ]), 400);
        }

        try {
            // Try to get an access token using the authorization code grant
            $accessToken = $this->authProvider->getAccessToken('authorization_code', [
                'code' => $code,
            ]);
            /** @var GithubResourceOwner $userProfile */
            $userProfile = $this->authProvider->getResourceOwner($accessToken);
        } catch (IdentityProviderException $e) {
            return $this->htmlResponse($this->twig->render('auth/login-error.html.twig', [
                'error' => $e->getMessage(),
            ]), 400);
        }

        $user = $this->userRepository->getOrCreate((string) $userProfile->getId(), (string) $userProfile->getNickname());
        $session->set('user', $user);

        return $this->redirectResponse('/');
    }
}
