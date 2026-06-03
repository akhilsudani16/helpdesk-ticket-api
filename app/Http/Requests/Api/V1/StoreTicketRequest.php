<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
            'priority' => ['required', Rule::in(TicketPriority::values())],
            'user_id' => [
                'required',
                'integer',
                'exists:users,id',
                function ($attribute, $value, $fail) {
                    // Non-admins can only create tickets for themselves
                    if (!$this->user()->isAdmin() && $value != $this->user()->id) {
                        $fail('You cannot create tickets for other users.');
                    }

                    // Admins can only create tickets for customers
                    if ($this->user()->isAdmin()) {
                        $user = User::find($value);
                        if ($user && ($user->isAdmin() || $user->isAgent())) {
                            $fail('Tickets can only be created for customers.');
                        }
                    }
                },
            ],
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // If user is a customer and doesn't provide user_id, use their own
        if (!$this->user()->isAdmin() && !$this->has('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id
            ]);
        }
        // If admin doesn't provide user_id, use their own
        elseif ($this->user()->isAdmin() && !$this->has('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id
            ]);
        }
    }

    /**
     * Get custom validation messages.
     */
    public function messages(): array
    {
        return [
            'user_id.prohibited' => 'You cannot create tickets for other users.',
            'user_id.exists' => 'The selected user does not exist.',
        ];
    }
}
