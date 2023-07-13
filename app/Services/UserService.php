<?php

namespace App\Services;

use App\Contracts\UserServiceInterface;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UserService implements UserServiceInterface
{
    public function createUser(User $user): void
    {
        $payload = [
            'name' => $user->name,
            'email' => $user->email,
            'phone_number' => $user->phone_number,
        ];

        Log::channel('user-service')
            ->info("Attempting user sync for user ID: $user->id", ['payload' => $payload]);

        $response = Http::withToken(config('services.user-service.key'))
            ->post(config('services.user-service.url'), $payload);

        if ($response->failed()) {
            Log::channel('user-service')
                ->info("Failed user sync for user ID: $user->id", ['response' => $response->json()]);
            return;
        }

        Log::channel('user-service')
            ->info("Successfully synced user ID: $user->id", ['response' => $response->json()]);
        // Maybe save some external ID from the service on the User model in the future
        // so we know it was recorded Example:
        $data = $response->json();
        $user->update(['external_id' => $data['id']]);
    }
}
