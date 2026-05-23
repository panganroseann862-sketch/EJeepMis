<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'route_id' => [
                'required',
                'exists:routes,id',
                function ($attribute, $value, $fail) {
                    $route = \App\Models\Route::find($value);
                    if ($route && $route->status !== 'active') {
                        $fail('The selected route is not active and cannot be assigned to schedules.');
                    }
                },
            ],
            'ejeep_id' => [
                'required',
                'exists:ejeeps,id',
                function ($attribute, $value, $fail) {
                    $ejeep = \App\Models\Ejeep::find($value);
                    if (!$ejeep) {
                        $fail('The selected E-Jeep does not exist.');
                        return;
                    }
                    if ($ejeep->operational_status === 'maintenance') {
                        $fail('The selected E-Jeep is currently under maintenance and cannot be assigned.');
                        return;
                    }
                    if ($ejeep->operational_status !== 'active') {
                        $fail('The selected E-Jeep is not active and cannot be assigned to schedules.');
                    }
                },
            ],
            'driver_id' => [
                'required',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    $user = \App\Models\User::find($value);
                    if (!$user) {
                        $fail('The selected driver does not exist.');
                        return;
                    }
                    if ($user->role !== 'driver') {
                        $fail('The selected user must be a driver.');
                        return;
                    }
                    if ($user->status !== 'active') {
                        $fail('The selected driver is not active and cannot be assigned to schedules.');
                    }
                },
            ],
            'departure_time' => 'required|date_format:H:i',
            'day_of_week' => 'required|in:monday,tuesday,wednesday,thursday,friday,saturday,sunday',
            'status' => 'required|in:active,cancelled',
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
            'route_id.required' => 'Route is required.',
            'route_id.exists' => 'The selected route does not exist or is not active.',
            'ejeep_id.required' => 'E-Jeep is required.',
            'ejeep_id.exists' => 'The selected E-Jeep does not exist.',
            'driver_id.required' => 'Driver is required.',
            'driver_id.exists' => 'The selected driver does not exist or is not a valid driver account.',
            'departure_time.required' => 'Departure time is required.',
            'departure_time.date_format' => 'Departure time must be in HH:MM format.',
            'day_of_week.required' => 'Day of week is required.',
            'day_of_week.in' => 'Invalid day of week selected.',
            'status.required' => 'Schedule status is required.',
            'status.in' => 'Schedule status must be either active or cancelled.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Normalize day_of_week to lowercase
        if ($this->has('day_of_week')) {
            $this->merge([
                'day_of_week' => strtolower($this->day_of_week),
            ]);
        }
    }
}
