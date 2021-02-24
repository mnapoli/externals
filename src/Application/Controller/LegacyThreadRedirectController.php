<?php declare(strict_types=1);

namespace Externals\Application\Controller;

use Bref\Micro\Controller;
use Doctrine\DBAL\Connection;
use Externals\Email\EmailRepository;
use Externals\NotFound;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class LegacyThreadRedirectController extends Controller
{
    public function __construct(
        private Connection $db,
        private EmailRepository $emailRepository,
    ) {
    }

    public function handle(ServerRequestInterface $request): ResponseInterface
    {
        $id = $request->getAttribute('id');

        $threadSubject = $this->db->fetchOne('SELECT `subject` FROM threads_old WHERE id = ?', [$id]);
        if (! $threadSubject) {
            throw new NotFound('Thread not found');
        }

        $email = $this->emailRepository->findBySubject($threadSubject);
        // Permanent redirection
        return $this->redirectResponse("/message/{$email->getNumber()}", status: 301);
    }
}
