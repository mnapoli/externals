<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Exceptions\NotFoundException;
use App\Models\Email;
use Illuminate\Http\Response;

class EmailSourceController extends Controller
{
    public function __invoke(int $number): Response
    {
        $source = Email::where('number', $number)->value('source');
        if ($source === null) {
            throw new NotFoundException('Email not found');
        }

        return response($source, 200, [
            'Content-Type' => 'text/plain; charset=utf-8',
        ]);
    }
}
