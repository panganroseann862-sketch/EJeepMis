<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateWeeklyReportRequest extends FormRequest
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
        return [
            'start_date' => 'required|date|before_or_equal:today',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:today',
            'format' => 'sometimes|in:csv,json',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'start_date.required' => 'Please select a start date for the report',
            'start_date.date' => 'Please provide a valid start date',
            'start_date.before_or_equal' => 'Cannot generate reports for future dates',
            'end_date.required' => 'Please select an end date for the report',
            'end_date.date' => 'Please provide a valid end date',
            'end_date.after_or_equal' => 'End date must be on or after the start date',
            'end_date.before_or_equal' => 'Cannot generate reports for future dates',
            'format.in' => 'Report format must be either CSV or JSON',
        ];
    }
}
