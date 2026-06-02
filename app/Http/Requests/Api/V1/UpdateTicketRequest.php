<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
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
        $user = $this->user();

        $rules = [
            'title' => ['sometimes', 'string', 'min:5', 'max:120'],
            'description' => ['sometimes', 'string', 'min:20'],
        ];

        // Check if customer is trying to update restricted fields
        if ($user && $user->isCustomer()) {
            $rules['status'] = ['prohibited'];
            $rules['priority'] = ['prohibited'];
            $rules['assigned_to'] = ['prohibited'];
        } elseif ($user && ($user->isAdmin() || $user->isAgent())) {
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
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.prohibited' => __('validation.status_is_prohibited'),
            'priority.prohibited' => __('validation.priority_is_prohibited'),
            'assigned_to.prohibited' => __('validation.assigned_to_is_prohibited'),
        ];
    }


}
