<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RecordPassengerCountRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'passenger_count' => 'required|integer|min:0',
            'boarding_count' => 'required|integer|min:0',
            'alighting_count' => 'required|integer|min:0',
            'stop_id' => [
                'required',
                'exists:stops,id',
                function ($attribute, $value, $fail) {
                    $trip = $this->route('trip');
                    if ($trip) {
                        $stop = \App\Models\Stop::find($value);
                        if ($stop && $stop->route_id !== $trip->route_id) {
                            $fail('The selected stop does not belong to this trip\'s route.');
                        }
                    }
                },
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
            'passenger_count.required' => 'Passenger count is required.',
            'passenger_count.integer' => 'Passenger count must be a valid number.',
            'passenger_count.min' => 'Passenger count cannot be negative.',
            
            'boarding_count.required' => 'Boarding count is required.',
            'boarding_count.integer' => 'Boarding count must be a valid number.',
            'boarding_count.min' => 'Boarding count cannot be negative.',
            
            'alighting_count.required' => 'Alighting count is required.',
            'alighting_count.integer' => 'Alighting count must be a valid number.',
            'alighting_count.min' => 'Alighting count cannot be negative.',
            
            'stop_id.required' => 'Stop selection is required.',
            'stop_id.exists' => 'The selected stop does not exist.',
        ];
    }

    /**
     * Handle a failed validation attempt.
     *
     * @param \Illuminate\Contracts\Validation\Validator $validator
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function failedValidation(\Illuminate\Contracts\Validation\Validator $validator)
    {
        $response = response()->json([
            'success' => false,
            'message' => $validator->errors()->first(),
            'errors' => $validator->errors(),
        ], 422);

        throw new \Illuminate\Validation\ValidationException($validator, $response);
    }
}
