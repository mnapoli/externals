<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Email;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class StatsController extends Controller
{
    public function __invoke(Request $request): View
    {
        return view('stats', [
            'userCount' => User::count(),
            'threadCount' => Email::where('isThreadRoot', true)->count(),
            'emailCount' => Email::count(),
            'user' => $request->user(),
        ]);
    }
}
