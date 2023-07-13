<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->can('update', $this->route('user'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:50'],
            'email' => ['sometimes', 'email', 'max:100'],
            'phone_number' => ['sometimes', 'string', 'max:30'],
            'role' => ['sometimes', 'string', 'in:admin,user', function ($attr, $value, $fail) {
                if (auth()->user()->isRegularUser()) {
                    $fail('Not allowed');
                }

                return null;
            }]
        ];
    }
}
