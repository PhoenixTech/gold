<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSaveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth()->check() && auth()->user()->hasRole('developer|admin');
    }

    protected function prepareForValidation(): void
    {
        $role = User::normalizeRole($this->input('role'));
        if ($role !== null) {
            $this->merge(['role' => $role]);
        }
    }

    public function rules(): array
    {
        $userId = $this->input('id');
        if (! $userId && $this->route('item')) {
            $routeItem = $this->route('item');
            $userId = $routeItem instanceof User ? $routeItem->id : (User::where('email', $routeItem)->value('id') ?? $routeItem);
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'min:10'],
            'role' => ['required', 'string', Rule::in(User::$roles)],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
            'password' => ['string', 'min:8', 'confirmed', 'nullable'],
        ];
    }
}
