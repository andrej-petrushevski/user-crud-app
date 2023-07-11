<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class ShowUserTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_see_user_details(): void
    {
        $user = User::factory()->create();

        $this->getJson(route('users.show', ['user' => $user->id]))
            ->assertUnauthorized();
    }

    public function test_regular_users_cannot_see_other_users_details(): void
    {
        [$userA, $userB] = User::factory()->regular()->count(2)->create();
        $headers = ['X-Api-Key' => $userA->api_key];

        $this->actingAs($userA)
            ->getJson(
                route('users.show', ['user' => $userB->id]),
                $headers
            )
            ->assertForbidden();
    }

    public function test_regular_users_can_see_their_own_details(): void
    {
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $user->api_key];

        $this->actingAs($user)
            ->getJson(
                route('users.show', ['user' => $user->id]),
                $headers
            )
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->has('id')
                        ->has('name')
                        ->has('email')
                        ->has('phone_number')
                        ->missing('api_key')
                        ->missing('role')
                    )
            );
    }

    public function test_admin_users_can_see_all_users_details(): void
    {
        $admin = User::factory()->admin()->create();
        $regularUser = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $this->actingAs($admin)
            ->getJson(
                route('users.show', ['user' => $regularUser->id]),
                $headers
            )
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) =>
            $json->has('data', fn (AssertableJson $json) =>
                $json->has('id')
                    ->has('name')
                    ->has('email')
                    ->has('phone_number')
                    ->has('api_key')
                    ->has('role')
                )
            );
    }
}
