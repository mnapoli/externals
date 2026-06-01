<?php

declare(strict_types=1);

namespace Feature\Http;

use App\Models\Email;
use App\Models\User;
use App\Models\Vote;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VoteControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_unauthenticated_user_gets_401(): void
    {
        $response = $this->postJson('/votes/1', ['value' => 1]);

        $response->assertStatus(401);
        $this->assertSame('"You must be authenticated"', $response->getContent());
    }

    public function test_rejects_value_greater_than_one(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/votes/1', ['value' => 2]);

        $response->assertStatus(400);
        $this->assertSame('"Invalid value"', $response->getContent());
    }

    public function test_rejects_value_less_than_minus_one(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/votes/1', ['value' => -2]);

        $response->assertStatus(400);
    }

    public function test_records_vote_and_returns_new_total(): void
    {
        Email::factory()->create(['number' => 42]);
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/votes/42', ['value' => 1]);

        $response->assertOk();
        $response->assertExactJson(['newTotal' => 1, 'newValue' => 1]);
        $this->assertDatabaseHas('votes', [
            'userId' => $user->id,
            'emailNumber' => 42,
            'value' => 1,
        ]);
    }

    public function test_zero_value_clears_existing_vote(): void
    {
        Email::factory()->create(['number' => 42]);
        $user = User::factory()->create();
        $this->actingAs($user)->postJson('/votes/42', ['value' => 1]);

        $response = $this->actingAs($user)->postJson('/votes/42', ['value' => 0]);

        $response->assertOk();
        $response->assertJson(['newValue' => 0]);
        $this->assertSame(0, Vote::where('userId', $user->id)->where('emailNumber', 42)->sum('value'));
    }
}
