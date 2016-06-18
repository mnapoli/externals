<?php
declare(strict_types = 1);

namespace Externals\Application\Controller;

use Zend\Diactoros\Response;

/**
 * @author Matthieu Napoli <matthieu@mnapoli.fr>
 */
class NotFoundController
{
    /**
     * @var \Twig_Environment
     */
    private $twig;

    public function __construct(\Twig_Environment $twig)
    {
        $this->twig = $twig;
    }

    public function __invoke()
    {
        return new Response($this->twig->render('/app/views/404.html.twig'));
    }
}
