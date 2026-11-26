<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Rfc;
use App\Services\Rfc\RfcStatus;
use App\Services\Rfc\RfcThreads;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ListRfcsController extends Controller
{
    public function __invoke(Request $request, RfcThreads $threads): JsonResponse
    {
        $validated = $request->validate([
            'status' => ['sometimes', Rule::enum(RfcStatus::class)],
        ]);
        $status = isset($validated['status']) ? RfcStatus::from($validated['status']) : null;

        $rfcs = Rfc::query()
            ->where('isListed', true)
            ->when($status, fn($query) => $query->where('status', $status))
            ->orderBy('indexPosition')
            ->get(['slug', 'title', 'status', 'indexSubsection', 'phpVersion', 'authors', 'date']);
        $activity = $threads->activityByRfc($status !== null ? array_values($rfcs->map(fn(Rfc $rfc) => $rfc->slug)->all()) : null);

        return response()->json([
            'data' => $rfcs->map(function (Rfc $rfc) use ($activity): array {
                $rfcActivity = $activity->get($rfc->slug);

                return [
                    'slug' => $rfc->slug,
                    'title' => $rfc->title,
                    'status' => $rfc->status->value,
                    'section' => $rfc->indexSubsection,
                    'phpVersion' => $rfc->phpVersion,
                    'authors' => $rfc->authors,
                    'date' => $rfc->date,
                    'threadCount' => $rfcActivity->threadCount ?? 0,
                    'lastActivity' => $rfcActivity?->lastActivity?->toAtomString(),
                    'url' => $rfc->url(),
                    'markdownUrl' => $rfc->url() . '.md',
                    'apiUrl' => url('/api/rfcs/' . $rfc->slug),
                    'wikiUrl' => $rfc->wikiUrl(),
                ];
            }),
        ])->header('Cache-Control', 'public, max-age=300');
    }
}
