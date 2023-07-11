<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\Fluent\AssertableJson;
use JetBrains\PhpStorm\NoReturn;
use Tests\TestCase;

class IndexUserTest extends TestCase
{
    use RefreshDatabase;

    #[NoReturn] public function setUp(): void
    {
        parent::setUp();

        User::factory()->count(10)->create();
    }

    public function test_guests_cannot_list_users(): void
    {
        $this->getJson(route('users.index'))
            ->assertUnauthorized();
    }

    public function test_regular_users_cannot_list_users(): void
    {
        $regularUser = User::factory()->regular()->create();

        $this->actingAs($regularUser)
            ->getJson(route('users.index', ['api_key' => $regularUser->api_key]))
            ->assertForbidden();
    }

    public function test_admin_users_can_list_all_users(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->getJson(route('users.index', ['api_key' => $admin->api_key]))
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', 11, fn (AssertableJson $json) =>
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
