<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class VehicleRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()->role === 'Administrator';
    }


    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $rules = [
            'registration_no' => 'required|string|max:20|unique:vehicles,registration_no',
            'vehicle_type_id' => 'required|exists:vehicle_types,id',
            'location_id' => 'required|exists:locations,id',
            'status' => 'required|string|in:Available,Booked,Maintenance',
            'is_rented' => 'required|boolean'
        ];
        
        // For update requests
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['registration_no'] = 'required|string|max:20|unique:vehicles,registration_no,' . $this->route('vehicle')->id;
        }
        
        return $rules;
    }

}


// Backend/app/Http/Requests/VehicleRequest.php