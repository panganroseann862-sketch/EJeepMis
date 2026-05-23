<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GenerateDailyReportRequest extends FormRequest
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
            'date' => 'required|date|before_or_equal:today',
            'format' => 'sometimes|in:csv,json',
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'date.required' => 'Please select a date for the report',
            'date.date' => 'Please provide a valid date',
            'date.before_or_equal' => 'Cannot generate reports for future dates',
            'format.in' => 'Report format must be either CSV or JSON',
        ];
    }
}
