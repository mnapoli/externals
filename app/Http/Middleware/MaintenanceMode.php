<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MaintenanceMode
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('externals.maintenance_mode')) {
            return response()->view('maintenance', [], 503);
        }

        return $next($request);
    }
}
