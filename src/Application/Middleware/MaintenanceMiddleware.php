<?php declare(strict_types=1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\Http\Middleware\Middleware;
use Twig\Environment;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * Displays a "Maintenance" page.
 */
class MaintenanceMiddleware implements Middleware
{
    /** @var bool */
    private $maintenance;

    /** @var Environment */
    private $twig;

    public function __construct(bool $maintenance, Environment $twig)
    {
        $this->maintenance = $maintenance;
        $this->twig = $twig;
    }

    public function __invoke(ServerRequestInterface $request, callable $next): ResponseInterface
    {
        if ($this->maintenance) {
            return new HtmlResponse($this->twig->render('maintenance.html.twig'));
        }

        return $next($request);
    }
}
