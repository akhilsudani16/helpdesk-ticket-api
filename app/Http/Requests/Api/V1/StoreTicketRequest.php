<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreTicketRequest extends FormRequest
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
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['required', 'string', 'min:20'],
            'priority' => ['required', 'in:low,medium,high,urgent'],
            'user_id' => ['sometimes', 'integer', 'exists:users,id'],
        ];
    }

    /**
     * Get validated data with forbidden fields removed.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        $user = $this->user();

        if ($user && !$user->isAdmin()) {
            unset($validated['user_id']);
        }

        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $user = $this->user();

        if ($user && !$user->isAdmin()) {
            $input = $this->all();
            unset($input['user_id']);
            $this->replace($input);
        }
    }
}
