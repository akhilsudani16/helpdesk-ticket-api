<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReplaceTicketRequest extends FormRequest
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

        // Customers cannot use the PUT method at all but add validation as a safety measure
        if ($user && $user->isCustomer()) {
            return [
                'title' => ['required', 'string', 'min:5', 'max:120'],
                'description' => ['required', 'string', 'min:20'],
                'status' => ['prohibited'],
                'priority' => ['prohibited'],
                'assigned_to' => ['prohibited'],
            ];
        }

        return [
            'title' => ['required', 'string', 'min:5', 'max:120'],
            'description' => ['required', 'string', 'min:20'],
            'status' => ['required', Rule::in(TicketStatus::values())],
            'priority' => ['required', Rule::in(TicketPriority::values())],
            'assigned_to' => [
                'nullable',
                Rule::exists('users', 'id')
                    ->where(function ($query) {
                        $query->where('role', UserRole::AGENT->value);
                    })
            ],
        ];
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
