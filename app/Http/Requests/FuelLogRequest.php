<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FuelLogRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Any authenticated user can add fuel logs
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
            'liters' => 'required|numeric|min:0.1',
            'cost' => 'required|numeric|min:0',
            'odometer' => 'required|integer|min:0',
            'date' => 'required|date|before_or_equal:today'
        ];
    }

}



// app/Http/Requests/FuelLogRequest.php