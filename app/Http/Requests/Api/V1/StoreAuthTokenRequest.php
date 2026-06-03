<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\DeviceName;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAuthTokenRequest extends FormRequest
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
     * @return array<string, ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['required', 'string', Rule::in(DeviceName::values())],
        ];
    }

    /**
     * Custom validation messages for request rules.
     */
    public function messages(): array
    {
        return [
            'device_name.in' => 'The selected device name is invalid. Allowed devices are: '.implode(', ', DeviceName::values()),
        ];
    }
}
