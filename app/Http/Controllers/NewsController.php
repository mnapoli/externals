<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class NewsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('news', ['user' => $request->user()]);
    }
}
