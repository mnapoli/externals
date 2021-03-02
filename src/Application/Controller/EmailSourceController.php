<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Micro\Controller;
use Externals\Email\EmailRepository;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class EmailSourceController extends Controller
{
    public function __construct(
        private EmailRepository $repository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $number = (int) $request->getAttribute('number');

        return $this->textResponse($this->repository->getEmailSource($number));
    }
}
