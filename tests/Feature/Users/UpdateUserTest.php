<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class UpdateUserTest extends TestCase
{
    public function test_guests_cannot_update_users(): void
    {
        $user = User::factory()->create();

        $payload = [
            'name' => fake()->name,
        ];

        $this->putJson(
            route('users.update', ['user' => $user->id]),
            $payload
        )
            ->assertUnauthorized();
    }

    public function test_regular_users_can_update_their_own_info(): void
    {
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $user->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
        ];

        $this->actingAs($user)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                $json->has('id')
                    ->where('name', $payload['name'])
                    ->where('email', $payload['email'])
                    ->where('phone_number', $payload['phone_number'])
                    ->missing('api_key')
                    ->missing('role')
                )
            );

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone_number' => $payload['phone_number'],
        ]);
    }

    public function test_regular_users_cannot_update_their_role(): void
    {
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $user->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
            'role' => 'admin',
        ];

        $this->actingAs($user)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertJson(fn (AssertableJson $json) =>
                $json->where('message', 'Not allowed')
                    ->etc()
            );
    }

    public function test_regular_users_cannot_update_other_users_details(): void
    {
        [$userA, $userB] = User::factory()->regular()->count(2)->create();
        $headers = ['X-Api-Key' => $userA->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
            'role' => 'admin',
        ];

        $this->actingAs($userA)
            ->putJson(
                route('users.update', ['user' => $userB->id]),
                $payload,
                $headers
            )
            ->assertForbidden();
    }

    public function test_admin_users_can_update_every_user_detail(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
            'role' => 'admin',
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                $json->has('id')
                    ->where('name', $payload['name'])
                    ->where('email', $payload['email'])
                    ->where('phone_number', $payload['phone_number'])
                    ->has('api_key')
                    ->where('role', $payload['role'])
                )
            );

        $this->assertDatabaseHas(User::class, [
            'id' => $user->id,
            'name' => $payload['name'],
            'email' => $payload['email'],
            'phone_number' => $payload['phone_number'],
            'role' => $payload['role'],
        ]);
    }


    /**
     * @throws \Exception
     */
    public function test_the_name_field_has_to_be_a_string(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => random_int(1, 10),
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field must be a string.']);
    }


    public function test_the_name_field_is_limited_to_50_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => Str::random(51),
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field must not be greater than 50 characters.']);
    }

    public function test_the_email_field_has_to_be_a_valid_email(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'email' => fake()->name,
            'name' => fake()->name,
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['email' => 'The email field must be a valid email address.']);
    }

    public function test_the_email_field_is_limited_to_100_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $email = Str::random(99) . fake()->email;

        $payload = [
            'email' => $email,
            'name' => fake()->name,
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['email' => 'The email field must not be greater than 100 characters.']);
    }

    public function test_the_phone_number_field_is_limited_to_30_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'phone_number' => Str::random(31),
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['phone_number' => 'The phone number field must not be greater than 30 characters.']);
    }

    /**
     * @throws \Exception
     */
    public function test_the_role_field_has_to_be_a_string(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
            'role' => random_int(1, 10),
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['role' => [
                'The role field must be a string.',
                'The selected role is invalid.',
            ]]);
    }

    public function test_the_role_has_to_be_either_an_admin_or_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $user = User::factory()->regular()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => 'manager'
        ];

        $this->actingAs($admin)
            ->putJson(
                route('users.update', ['user' => $user->id]),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['role' => 'The selected role is invalid.']);
    }
}
