<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEjeepRequest extends FormRequest
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
     */
    public function rules(): array
    {
        $ejeepId = $this->route('ejeep')->id;

        return [
            'vehicle_number' => [
                'required',
                'string',
                'max:50',
                Rule::unique('ejeeps', 'vehicle_number')->ignore($ejeepId),
            ],
            'plate_number' => [
                'required',
                'string',
                'max:20',
                Rule::unique('ejeeps', 'plate_number')->ignore($ejeepId),
            ],
            'passenger_capacity' => 'required|integer|min:1|max:100',
            'operational_status' => 'required|in:active,maintenance,inactive',
            'maintenance_notes' => 'nullable|string',
            'last_maintenance_date' => 'nullable|date',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     */
    public function messages(): array
    {
        return [
            'vehicle_number.required' => 'Vehicle number is required.',
            'vehicle_number.unique' => 'This vehicle number is already registered.',
            'vehicle_number.max' => 'Vehicle number cannot exceed 50 characters.',
            'plate_number.required' => 'Plate number is required.',
            'plate_number.unique' => 'This plate number is already registered.',
            'plate_number.max' => 'Plate number cannot exceed 20 characters.',
            'passenger_capacity.required' => 'Passenger capacity is required.',
            'passenger_capacity.integer' => 'Passenger capacity must be a number.',
            'passenger_capacity.min' => 'Passenger capacity must be at least 1.',
            'passenger_capacity.max' => 'Passenger capacity cannot exceed 100.',
            'operational_status.required' => 'Operational status is required.',
            'operational_status.in' => 'Operational status must be active, maintenance, or inactive.',
            'last_maintenance_date.date' => 'Last maintenance date must be a valid date.',
        ];
    }
}
