<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
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
            'phone' => [
                'required',
                'string',
                'regex:/^(\+254|0)?[17]\d{8}$/',
                Rule::unique('users', 'phone'),
            ],
            'county_id' => ['required', 'exists:counties,id'],
            'constituency_id' => [
                'required',
                'exists:constituencies,id',
                function ($attribute, $value, $fail) {
                    $countyId = $this->input('county_id');
                    $constituency = \App\Models\Constituency::find($value);
                    if ($constituency && $constituency->county_id != $countyId) {
                        $fail('The selected constituency does not belong to the selected county.');
                    }
                },
            ],
            'ward_id' => [
                'required',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    $constituencyId = $this->input('constituency_id');
                    $ward = \App\Models\Ward::find($value);
                    if ($ward && $ward->constituency_id != $constituencyId) {
                        $fail('The selected ward does not belong to the selected constituency.');
                    }
                },
            ],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'phone.regex' => 'The phone number must be a valid Kenyan phone number.',
            'phone.unique' => 'This phone number is already registered.',
            'county_id.exists' => 'The selected county is invalid.',
            'constituency_id.exists' => 'The selected constituency is invalid.',
            'ward_id.exists' => 'The selected ward is invalid.',
        ];
    }
}

