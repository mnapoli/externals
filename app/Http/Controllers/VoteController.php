<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Actions\CastVote;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VoteController extends Controller
{
    public function __invoke(Request $request, int $number): JsonResponse
    {
        $user = $request->user();
        if (! $user) {
            return response()->json('You must be authenticated', 401);
        }

        $vote = (int) $request->input('value', 0);
        if ($vote > 1 || $vote < -1) {
            return response()->json('Invalid value', 400);
        }

        return response()->json([
            'newTotal' => app(CastVote::class)->handle($user->id, $number, $vote),
            'newValue' => $vote,
        ]);
    }
}
