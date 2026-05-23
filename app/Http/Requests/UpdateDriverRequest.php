<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateDriverRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() && $this->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $driverId = $this->route('driver')->id;

        return [
            'username' => [
                'required',
                'string',
                'max:50',
                Rule::unique('users', 'username')->ignore($driverId),
                'regex:/^[a-zA-Z0-9_]+$/',
            ],
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($driverId),
            ],
            'password' => [
                'nullable',
                'string',
                'confirmed',
                Password::min(8),
            ],
            'first_name' => [
                'required',
                'string',
                'max:100',
            ],
            'last_name' => [
                'required',
                'string',
                'max:100',
            ],
            'phone' => [
                'nullable',
                'string',
                'max:20',
            ],
            'status' => [
                'required',
                'in:active,inactive',
            ],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'username.required' => 'Username is required.',
            'username.unique' => 'This username is already taken.',
            'username.regex' => 'Username can only contain letters, numbers, and underscores.',
            'username.max' => 'Username cannot exceed 50 characters.',
            'email.required' => 'Email address is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'This email address is already registered.',
            'password.min' => 'Password must be at least 8 characters.',
            'password.confirmed' => 'Password confirmation does not match.',
            'first_name.required' => 'First name is required.',
            'first_name.max' => 'First name cannot exceed 100 characters.',
            'last_name.required' => 'Last name is required.',
            'last_name.max' => 'Last name cannot exceed 100 characters.',
            'phone.max' => 'Phone number cannot exceed 20 characters.',
            'status.required' => 'Status is required.',
            'status.in' => 'Status must be either active or inactive.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove password from validation if not provided
        if (empty($this->password)) {
            $this->request->remove('password');
            $this->request->remove('password_confirmation');
        }
    }
}
