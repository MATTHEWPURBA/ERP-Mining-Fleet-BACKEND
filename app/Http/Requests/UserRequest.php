<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'role' => 'required|string|in:Administrator,Approver,User',
            'department' => 'nullable|string|max:255',
            'location_id' => 'required|exists:locations,id',
            'supervisor_id' => 'nullable|exists:users,id'
        ];
        
        // For update requests
        if ($this->isMethod('PUT') || $this->isMethod('PATCH')) {
            $rules['email'] = 'required|email|max:255|unique:users,email,' . $this->route('user')->id;
            $rules['password'] = 'nullable|string|min:8';
        } else {
            $rules['password'] = 'required|string|min:8';
        }
        
        return $rules;
    }
}
