<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Micro\Controller;
use Externals\User\User;
use Externals\Voting;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Environment;

class VoteController extends Controller
{
    public function __construct(
        private Environment $twig,
        private Voting $voting,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $user = $request->getAttribute('user');
        if (!$user) {
            return $this->jsonResponse('You must be authenticated', 401);
        }
        assert($user instanceof User);
        $vote = (int) $this->bodyField($request, 'value', 0);
        if ($vote > 1 || $vote < -1) {
            return $this->jsonResponse('Invalid value', 400);
        }
        $number = (int) $request->getAttribute('number');

        return $this->jsonResponse([
            'newTotal' => $this->voting->vote($user->id, $number, $vote),
            'newValue' => $vote,
        ]);
    }
}
