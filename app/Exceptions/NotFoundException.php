<?php

declare(strict_types=1);

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Thrown when a domain entity (email, thread, …) cannot be found.
 *
 * Extends Symfony's NotFoundHttpException so Laravel automatically turns it
 * into a 404 response in the HTTP layer, while still being usable from the
 * CLI.
 */
class NotFoundException extends NotFoundHttpException {}
