<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
use App\Enums\TicketStatus;
use App\Enums\UserRole;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
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
    // TODO: add validation for agent-only edit status

    return [
        'title' => ['sometimes', 'string', 'min:5', 'max:120',
            function ($attribute, $value, $fail) {
                if(!Auth::user()->isCustomer()) {
                    $fail('You are not authorized to update this title');
                }
            }
        ],
        'description' => ['sometimes', 'string', 'min:20',
            function ($attribute, $value, $fail) {
                if(!Auth::user()->isCustomer()) {
                    $fail('You are not authorized to update this description');
                }
            }
        ],
        'status' => ['required', Rule::in(TicketStatus::values())],
        'priority' => ['required', Rule::in(TicketPriority::values())],
        'assigned_to' => [
            'nullable',
            function ($attribute, $value, $fail) {
            if(Auth::user()->isAgent()) {
                $fail('You are not authorized to update this assigned agent');
            }
            },
            Rule::exists('users', 'id')
                ->where(function ($query) {
                    $query->where('role', UserRole::AGENT->value);
                })
        ],
    ];
}
}

//public function rules(): array
//{
//    return [
//        'title' => ['required', 'string', 'min:5', 'max:120'],
//        'description' => ['required', 'string', 'min:20'],
//        'status' => ['required', Rule::in(TicketStatus::values())],
//        'priority' => ['required', Rule::in(TicketPriority::values())],
//        'assigned_to' => [
//            'nullable',
//            Rule::exists('users', 'id')
//                ->where(function ($query) {
//                    $query->where('role', UserRole::AGENT->value);
//                })
//        ],
//    ];
//}
//}
