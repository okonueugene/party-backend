<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CreatePostRequest extends FormRequest
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
            'content' => ['required_without_all:image,audio', 'string', 'max:5000'],
            'image' => [
                'nullable',
                'image',
                'mimes:jpeg,png,jpg,gif,webp',
                'max:5120', // 5MB
            ],
            'audio' => [
                'nullable',
                'file',
                'mimes:mp3,wav,ogg',
                'max:10240', // 10MB
            ],
            'county_id' => ['nullable', 'exists:counties,id'],
            'constituency_id' => [
                'nullable',
                'exists:constituencies,id',
                function ($attribute, $value, $fail) {
                    $countyId = $this->input('county_id');
                    if ($countyId && $value) {
                        $constituency = \App\Models\Constituency::find($value);
                        if ($constituency && $constituency->county_id != $countyId) {
                            $fail('The selected constituency does not belong to the selected county.');
                        }
                    }
                },
            ],
            'ward_id' => [
                'nullable',
                'exists:wards,id',
                function ($attribute, $value, $fail) {
                    $constituencyId = $this->input('constituency_id');
                    if ($constituencyId && $value) {
                        $ward = \App\Models\Ward::find($value);
                        if ($ward && $ward->constituency_id != $constituencyId) {
                            $fail('The selected ward does not belong to the selected constituency.');
                        }
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
            'content.required_without_all' => 'Post must have either content, image, or audio.',
            'image.max' => 'Image size must not exceed 5MB.',
            'audio.max' => 'Audio size must not exceed 10MB.',
        ];
    }
}

