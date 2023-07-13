<?php

namespace App\Http\Controllers;

use App\Contracts\UserServiceInterface;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        if (auth()->user()->cannot('viewAny', User::class)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $users = User::all();

        return UserResource::collection($users)->response();
    }

    public function store(StoreUserRequest $request, UserServiceInterface $userService): JsonResponse
    {
        $userData = $request->validated();

        $user = User::query()->create([
            'name' => $userData['name'],
            'email' => $userData['email'],
            'password' => $userData['password'],
            'phone_number' => $userData['phone_number'],
            'api_key' => Str::random(20),
            'role' => $userData['role']
        ]);

        $userService->createUser($user);

        return UserResource::make($user)->response();
    }

    public function show(User $user): JsonResponse
    {
        if (auth()->user()->cannot('view', $user)) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return UserResource::make($user)->response();
    }

    public function update(UpdateUserRequest $request, User $user): JsonResponse
    {
        $user->update($request->validated());

        return UserResource::make($user)->response();
    }
}
