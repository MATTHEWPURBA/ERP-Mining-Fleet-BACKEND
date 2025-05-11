<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BookingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Further authorization in controller
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

     public function rules(): array
     {
         $rules = [
             'vehicle_id' => 'required|exists:vehicles,id',
             'purpose' => 'required|string|max:255',
             'start_date' => 'required|date|after_or_equal:today',
             'end_date' => 'required|date|after:start_date',
             'passengers' => 'nullable|integer|min:1',
             'notes' => 'nullable|string'
         ];
         
         return $rules;
     }
}



// app/Http/Requests/BookingRequest.php