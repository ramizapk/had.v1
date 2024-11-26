<?php

namespace App\Http\Requests\CustomerRequest;

use Illuminate\Foundation\Http\FormRequest;

class CustomerRegisterRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'phone_number' => ['required', 'string', 'digits:9', 'starts_with:7'],  // 'unique:customers,phone_number'
            'latitude' => ['nullable', 'numeric', 'between:-90,90'], // مطلوب إذا لم يتم إدخال address
            'longitude' => ['nullable', 'numeric', 'between:-180,180'], // مطلوب إذا لم يتم إدخال address
            'address_name' => ['nullable', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
        ];

    }
}
