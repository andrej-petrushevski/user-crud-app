<?php

namespace App\Http\Controllers;

use App\Http\Requests\IndexUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Str;

class UserController extends Controller
{
    public function index(IndexUserRequest $request): JsonResponse
    {
        $users = User::all();

        return UserResource::collection($users)->response();
    }

    public function store(StoreUserRequest $request): JsonResponse
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

        return UserResource::make($user)->response();
    }
}
