<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Email\EmailRepository;
use Illuminate\Http\Response;

class EmailSourceController extends Controller
{
    public function __construct(private readonly EmailRepository $repository) {}

    public function __invoke(int $number): Response
    {
        return response($this->repository->getEmailSource($number), 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
