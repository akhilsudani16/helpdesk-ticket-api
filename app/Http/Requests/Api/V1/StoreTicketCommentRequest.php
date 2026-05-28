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
        return [
            'body' => ['required', 'string', 'min:3', 'max:2000'],
            'is_internal' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Get validated data with forbidden fields removed.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        $user = $this->user();

        if ($user && $user->isCustomer()) {
            unset($validated['is_internal']);
        }

        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user && $user->isCustomer()) {
            $input = $this->all();
            unset($input['is_internal']);
            $this->replace($input);
        }
    }
}
