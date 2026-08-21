<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserSaveRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->check() and auth()->user()->hasRole('developer|admin');
    }

    protected function prepareForValidation(): void
    {
        $role = User::normalizeRole($this->input('role'));
        if ($role !== null) {
            $this->merge(['role' => $role]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'min:10'],
            'role' => ['required', 'string', Rule::in(User::$roles)],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,'.$this->id],
            'password' => ['string', 'min:8', 'confirmed', 'nullable'],
        ];
    }
}
