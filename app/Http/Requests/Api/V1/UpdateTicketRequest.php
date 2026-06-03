<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class UpdateTicketRequest extends FormRequest
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
        $user = Auth::user();

        $rules = [
            'title' => ['sometimes', 'string', 'min:5', 'max:120'],
            'description' => ['sometimes', 'string', 'min:20'],
        ];

        if ($user && ($user->isAdmin() || $user->isAgent())) {
            $rules['status'] = ['sometimes', Rule::in(TicketStatus::values())];
            $rules['priority'] = ['sometimes', Rule::in(TicketPriority::values())];
            $rules['assigned_to'] = [
                'sometimes',
                'nullable',
                Rule::exists('users', 'id')
                    ->where(function ($query) {
                        $query->where('role', UserRole::AGENT->value);
                    })
            ];
        }

        return $rules;
    }

    /**
     * Get validated data with forbidden fields removed.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);

        if ($this->user()?->isCustomer()) {
            unset($validated['status'], $validated['priority'], $validated['assigned_to']);
        }

        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->user()?->isCustomer()) {
            $input = $this->all();

            unset($input['status'], $input['priority'], $input['assigned_to']);

            $this->replace($input);
        }
    }
}
