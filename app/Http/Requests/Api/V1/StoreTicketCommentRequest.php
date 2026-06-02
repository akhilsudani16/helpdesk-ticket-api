<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketCommentRequest extends FormRequest
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
        $user = $this->user();

        return [
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'is_internal' => [
                'sometimes',
                'boolean',
                function ($attribute, $value, $fail) use ($user) {
                    // If customer tries to set is_internal to true, reject it
                    if ($user && $user->isCustomer() && $value === true) {
                        $fail( __('validation.customer_not_allow_internal_comment'));
                    }
                },
            ],
        ];
    }

    /**
     * Custom validation messages.
     */
    public function messages(): array
    {
        return [
            'body.required' => __('validation.body_is_required'),
            'body.min' => __('validation.comment_body_min'),
            'body.max' => __('validation.comment_body_max'),
            'is_internal.boolean' => __('validation.is_internal_must_be_boolean'),
        ];
    }
}
