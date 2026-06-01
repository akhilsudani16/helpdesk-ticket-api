<?php

namespace App\Http\Requests\Api\V1;

use App\Enums\TicketPriority;
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
                'sometimes',
                'integer',
                'exists:users,id',
                Rule::prohibitedIf(! ($this->user()?->isAdmin() ?? false)),
            ],
        ];
    }
}
