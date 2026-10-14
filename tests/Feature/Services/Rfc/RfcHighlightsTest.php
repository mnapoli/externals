<?php

declare(strict_types=1);

use App\Models\Rfc;
use App\Models\RfcPoll;
use App\Services\Rfc\RfcHighlights;
use App\Services\Rfc\RfcStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function createPoll(Rfc $rfc, bool $isClosed, ?string $closesAt, int $yes = 0, int $no = 0): RfcPoll
{
    return RfcPoll::create([
        'rfcId' => $rfc->id,
        'position' => 0,
        'question' => 'Accept?',
        'isClosed' => $isClosed,
        'closesAt' => $closesAt,
        'choices' => [['label' => 'Yes', 'count' => $yes], ['label' => 'No', 'count' => $no], ['label' => 'Abstain', 'count' => 4]],
        'voters' => [],
    ]);
}

test('RFCs with an open vote are listed, closest deadline first', function (): void {
    $later = Rfc::factory()->create(['slug' => 'later', 'status' => RfcStatus::Voting]);
    createPoll($later, false, '2026-10-22 00:00:00 UTC');
    $sooner = Rfc::factory()->create(['slug' => 'sooner', 'status' => RfcStatus::Voting]);
    createPoll($sooner, false, '2026-10-19 13:15:00 UTC');
    $closed = Rfc::factory()->create(['slug' => 'closed', 'status' => RfcStatus::Voting]);
    createPoll($closed, true, '2026-10-01 00:00:00 UTC');
    Rfc::factory()->create(['slug' => 'discussed', 'status' => RfcStatus::Discussion]);

    expect(app(RfcHighlights::class)->voting()->pluck('slug')->all())->toBe(['sooner', 'later']);
});

test('the share of yes ignores abstentions', function (): void {
    $poll = createPoll(Rfc::factory()->create(), false, null, yes: 20, no: 10);

    expect(RfcHighlights::yesShare($poll))->toBe(20 / 30);
});

test('a poll without votes has no share of yes', function (): void {
    $poll = createPoll(Rfc::factory()->create(), false, null);

    expect(RfcHighlights::yesShare($poll))->toBeNull();
});
