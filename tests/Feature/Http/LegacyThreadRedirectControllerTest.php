<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class LegacyThreadRedirectControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_404_when_legacy_thread_id_unknown(): void
    {
        $this->get('/thread/123')->assertNotFound();
    }

    public function test_returns_404_when_no_email_matches_subject(): void
    {
        DB::table('threads_old')->insert(['id' => 1, 'subject' => 'No match']);

        $this->get('/thread/1')->assertNotFound();
    }

    public function test_redirects_to_latest_matching_thread_root(): void
    {
        DB::table('threads_old')->insert(['id' => 1, 'subject' => 'Reused subject']);
        Email::factory()->create([
            'subject' => 'Reused subject',
            'number' => 100,
            'date' => '2020-01-01 00:00:00',
        ]);
        Email::factory()->create([
            'subject' => 'Reused subject',
            'number' => 200,
            'date' => '2024-01-01 00:00:00',
        ]);

        $response = $this->get('/thread/1');

        $response->assertRedirect('/message/200');
        $response->assertStatus(301);
    }
}
