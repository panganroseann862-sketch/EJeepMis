<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRouteRequest extends FormRequest
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
            'route_name' => 'required|string|max:100',
            'route_code' => [
                'required',
                'string',
                'max:20',
                'alpha_num',
                Rule::unique('routes', 'route_code')->ignore($this->route('route')),
            ],
            'description' => 'nullable|string|max:500',
            'status' => 'required|in:active,inactive',
            'stops' => 'nullable|array|min:1',
            'stops.*.stop_name' => 'required|string|max:100',
            'stops.*.location_description' => 'nullable|string|max:500',
            'stops.*.latitude' => 'nullable|numeric|between:-90,90',
            'stops.*.longitude' => 'nullable|numeric|between:-180,180',
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
            'route_name.required' => 'Route name is required.',
            'route_name.max' => 'Route name must not exceed 100 characters.',
            'route_code.required' => 'Route code is required.',
            'route_code.unique' => 'This route code is already in use.',
            'route_code.alpha_num' => 'Route code must contain only letters and numbers.',
            'route_code.max' => 'Route code must not exceed 20 characters.',
            'status.required' => 'Route status is required.',
            'status.in' => 'Route status must be either active or inactive.',
            'stops.min' => 'A route must have at least one stop.',
            'stops.*.stop_name.required' => 'Stop name is required for all stops.',
            'stops.*.stop_name.max' => 'Stop name must not exceed 100 characters.',
            'stops.*.latitude.between' => 'Latitude must be between -90 and 90.',
            'stops.*.longitude.between' => 'Longitude must be between -180 and 180.',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Ensure stops array maintains proper sequence
        if ($this->has('stops') && is_array($this->stops)) {
            $stops = array_values($this->stops);
            $this->merge(['stops' => $stops]);
        }
    }
}
