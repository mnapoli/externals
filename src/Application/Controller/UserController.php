<?php
declare(strict_types = 1);

namespace Externals\Application\Controller;

use Externals\User\User;
use Externals\User\UserRepository;
use League\OAuth2\Client\Provider\AbstractProvider;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use League\OAuth2\Client\Provider\GithubResourceOwner;
use Psr\Http\Message\ServerRequestInterface;
use PSR7Session\Http\SessionMiddleware;
use Twig_Environment;
use Zend\Diactoros\Response\HtmlResponse;
use Zend\Diactoros\Response\RedirectResponse;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class UserController
{
    /**
     * @var UserRepository
     */
    private $userRepository;

    /**
     * @var Twig_Environment
     */
    private $twig;

    /**
     * @var AbstractProvider
     */
    private $authProvider;

    public function __construct(UserRepository $userRepository, Twig_Environment $twig, AbstractProvider $authProvider)
    {
        $this->userRepository = $userRepository;
        $this->twig = $twig;
        $this->authProvider = $authProvider;
    }

    public function login(ServerRequestInterface $request)
    {
        newrelic_name_transaction('login');

        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);

        // If already logged in
        $user = $session->get('user');
        if ($user instanceof User) {
            return new RedirectResponse('/');
        }

        $code = $request->getQueryParams()['code'] ?? null;
        if (!$code) {
            // Get an authorization code from GitHub
            $redirectUrl = $this->authProvider->getAuthorizationUrl();
            $state = $this->authProvider->getState();
            $session->set('oauth2state', $state);
            return new RedirectResponse($redirectUrl);
        }

        // Check given state against previously stored one to mitigate CSRF attack
        $requestOauthState = $request->getQueryParams()['state'] ?? null;
        $sessionOauthState = $session->get('oauth2state');
        if (empty($requestOauthState) || ($requestOauthState !== $sessionOauthState)) {
            $session->remove('oauth2state');
            return new HtmlResponse($this->twig->render('/app/views/auth/login-error.html.twig', [
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
            return new HtmlResponse($this->twig->render('/app/views/auth/login-error.html.twig', [
                'error' => $e->getMessage(),
            ]), 400);
        }

        $user = $this->userRepository->getOrCreate((string) $userProfile->getId(), (string) $userProfile->getNickname());
        $session->set('user', $user);

        return new RedirectResponse('/');
    }

    public function logout(ServerRequestInterface $request)
    {
        newrelic_name_transaction('logout');
        $session = $request->getAttribute(SessionMiddleware::SESSION_ATTRIBUTE);
        $session->remove('user');
        return new RedirectResponse('/');
    }
}
