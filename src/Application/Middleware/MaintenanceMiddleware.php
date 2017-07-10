<?php
declare(strict_types = 1);

namespace Externals\Application\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Stratify\Http\Middleware\Middleware;
use Zend\Diactoros\Response\HtmlResponse;

/**
 * Displays a "Maintenance" page.
 */
class MaintenanceMiddleware implements Middleware
{
    /**
     * @var bool
     */
    private $maintenance;

    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(bool $maintenance, \Twig_Environment $twig)
    {
        $this->maintenance = $maintenance;
        $this->twig = $twig;
    }

    public function __invoke(ServerRequestInterface $request, callable $next) : ResponseInterface
    {
        if ($this->maintenance) {
            return new HtmlResponse($this->twig->render('@app/maintenance.html.twig'));
        }

        return $next($request);
    }
}
