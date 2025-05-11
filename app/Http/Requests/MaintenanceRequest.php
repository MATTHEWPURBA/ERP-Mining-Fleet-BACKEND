<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MaintenanceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return in_array($this->user()->role, ['Administrator', 'Approver']);
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'vehicle_id' => 'required|exists:vehicles,id',
            'type' => 'required|string|in:Scheduled,Unscheduled',
            'description' => 'required|string',
            'cost' => 'required|numeric|min:0',
            'date' => 'required|date',
            'next_date' => 'nullable|date|after:date'
        ];
    }

}


// app/Http/Requests/MaintenanceRequest.php