<?php

namespace Tests\Feature\Users;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class StoreUserTest extends TestCase
{
    protected array $payload;

    public function setUp(): void
    {
        parent::setUp();

        $password = Str::random(20);

        $this->payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => $password,
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];
    }

    public function test_guests_cannot_create_users(): void
    {
        $this->postJson(route('users.store'), $this->payload)
            ->assertUnauthorized();
    }

    public function test_regular_users_cannot_create_other_users(): void
    {
        $regularUser = User::factory()->regularUser()->create();
        $headers = ['X-Api-Key' => $regularUser->api_key];

        $this->actingAs($regularUser)
            ->postJson(
                route('users.store'),
                $this->payload,
                $headers
            )
            ->assertForbidden();
    }

    public function test_admin_users_can_create_other_users(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $this->payload,
                $headers
            )
            ->assertSuccessful()
            ->assertJson(fn (AssertableJson $json) =>
                $json->has('data', fn (AssertableJson $json) =>
                    $json->has('id')
                        ->where('name', $this->payload['name'])
                        ->where('email', $this->payload['email'])
                        ->where('phone_number', $this->payload['phone_number'])
                        ->has('api_key')
                        ->where('role', $this->payload['role'])
                    )
            );

        $this->assertDatabaseHas(User::class, [
            'name' => $this->payload['name'],
            'email' => $this->payload['email'],
            'phone_number' => $this->payload['phone_number'],
            'role' => $this->payload['role'],
        ]);
    }

    public function test_the_name_field_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field is required.']);
    }

    /**
     * @throws \Exception
     */
    public function test_the_name_field_has_to_be_a_string(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => random_int(1, 10),
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field must be a string.']);
    }

    public function test_the_name_field_is_limited_to_50_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => Str::random(51),
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['name' => 'The name field must not be greater than 50 characters.']);
    }

    public function test_the_email_field_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['email' => 'The email field is required.']);
    }

    public function test_the_email_field_has_to_be_a_valid_email(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'email' => fake()->name,
            'name' => fake()->name,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['email' => 'The email field must be a valid email address.']);
    }

    public function test_the_email_field_is_limited_to_100_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $email = Str::random(99) . fake()->email;

        $payload = [
            'email' => $email,
            'name' => fake()->name,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['email' => 'The email field must not be greater than 100 characters.']);
    }

    public function test_the_password_field_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['password' => 'The password field is required.']);
    }

    public function test_the_password_field_has_to_contain_ascii_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'email' => fake()->email,
            'name' => fake()->name,
            'password' => '汉字',
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['password' => 'The password field must only contain letters and numbers.']);
    }

    public function test_the_password_field_is_limited_to_20_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => Str::random(21),
            'phone_number' => fake()->phoneNumber,
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['password' => 'The password field must not be greater than 20 characters.']);
    }

    public function test_the_phone_number_field_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => Str::random(20),
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['phone_number' => 'The phone number field is required.']);
    }
    public function test_the_phone_number_field_is_limited_to_30_characters(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => Str::random(31),
            'role' => fake()->randomElement(['admin', 'user']),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['phone_number' => 'The phone number field must not be greater than 30 characters.']);
    }
    public function test_the_role_field_is_required(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['role' => 'The role field is required.']);
    }

    /**
     * @throws \Exception
     */
    public function test_the_role_field_has_to_be_a_string(): void
    {
        $admin = User::factory()->admin()->create();
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => random_int(1, 10),
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
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
        $headers = ['X-Api-Key' => $admin->api_key];

        $payload = [
            'name' => fake()->name,
            'email' => fake()->email,
            'password' => Str::random(20),
            'phone_number' => fake()->phoneNumber,
            'role' => 'manager'
        ];

        $this->actingAs($admin)
            ->postJson(
                route('users.store'),
                $payload,
                $headers
            )
            ->assertUnprocessable()
            ->assertInvalid(['role' => 'The selected role is invalid.']);
    }
}
