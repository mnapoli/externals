<?php

declare(strict_types=1);

namespace App\Services\Rfc;

use App\Models\Rfc;
use Illuminate\Support\Collection;

/**
 * All RFCs, in the sections of the wiki index ("In voting", "Implemented — PHP 8.5"…).
 */
class RfcList
{
    /**
     * @return list<array{title: string, status: RfcStatus, open: bool, rfcs: list<Rfc>}>
     */
    public function sections(?RfcStatus $status = null): array
    {
        $rfcs = Rfc::query()
            ->where('isListed', true)
            ->when($status, fn($query) => $query->where('status', $status))
            ->with('polls')
            ->orderBy('indexPosition')
            ->get();

        return $this->group($rfcs);
    }

    /**
     * Groups consecutive RFCs of the same section: the index is already sorted by section.
     *
     * @param  Collection<int, Rfc>  $rfcs
     * @return list<array{title: string, status: RfcStatus, open: bool, rfcs: list<Rfc>}>
     */
    private function group(Collection $rfcs): array
    {
        $sections = [];
        $section = null;
        $hasOpenImplemented = false;
        foreach ($rfcs as $rfc) {
            $title = $rfc->status->label() . ($rfc->indexSubsection !== null ? ' — ' . $rfc->indexSubsection : '');
            if ($section === null || $section['title'] !== $title) {
                if ($section !== null) {
                    $sections[] = $section;
                }
                $section = [
                    'title' => $title,
                    'status' => $rfc->status,
                    'open' => match ($rfc->status) {
                        RfcStatus::Voting, RfcStatus::Discussion, RfcStatus::Draft, RfcStatus::Accepted => true,
                        // Only the latest PHP version
                        RfcStatus::Implemented => ! $hasOpenImplemented,
                        default => false,
                    },
                    'rfcs' => [],
                ];
                $hasOpenImplemented = $hasOpenImplemented || $rfc->status === RfcStatus::Implemented;
            }
            $section['rfcs'][] = $rfc;
        }
        if ($section !== null) {
            $sections[] = $section;
        }

        return $sections;
    }
}
