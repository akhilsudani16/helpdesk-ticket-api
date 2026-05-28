<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Foundation\Http\FormRequest;

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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $user = $this->user();
        
        $rules = [
            'title' => ['sometimes', 'string', 'min:5', 'max:120'],
            'description' => ['sometimes', 'string', 'min:20'],
        ];

        // Only admin and agents can update these fields
        if ($user && ($user->isAdmin() || $user->isAgent())) {
            $rules['status'] = ['sometimes', 'in:open,in_progress,resolved,closed'];
            $rules['priority'] = ['sometimes', 'in:low,medium,high,urgent'];
            $rules['assigned_to'] = ['sometimes', 'nullable', 'exists:users,id'];
        }

        return $rules;
    }

    /**
     * Get validated data with forbidden fields removed.
     */
    public function validated($key = null, $default = null)
    {
        $validated = parent::validated($key, $default);
        $user = $this->user();
        
        // Remove forbidden fields for customers
        if ($user && $user->isCustomer()) {
            unset($validated['status'], $validated['priority'], $validated['assigned_to']);
        }
        
        return $validated;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Remove forbidden fields before validation for customers
        $user = $this->user();
        
        if ($user && $user->isCustomer()) {
            // Get all input
            $input = $this->all();
            
            // Remove forbidden fields
            unset($input['status'], $input['priority'], $input['assigned_to']);
            
            // Replace the input
            $this->replace($input);
        }
    }
}
