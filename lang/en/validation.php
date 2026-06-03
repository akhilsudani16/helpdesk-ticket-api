<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Only the validation rules actually used in this
    | project are included here.
    |
    */

    // Used in StoreTicketCommentRequest
    'boolean' => 'The :attribute field must be true or false.',

    // Used in StoreAuthTokenRequest
    'email' => 'The :attribute field must be a valid email address.',

    // Used in StoreTicketRequest, UpdateTicketRequest, ReplaceTicketRequest
    'exists' => 'The selected :attribute is invalid.',

    // Used in StoreTicketRequest, UpdateTicketRequest, ReplaceTicketRequest, StoreAuthTokenRequest
    'in' => 'The selected :attribute is invalid.',

    // Used in StoreTicketRequest, UpdateTicketRequest, ReplaceTicketRequest
    'integer' => 'The :attribute field must be an integer.',

    // Used in StoreTicketRequest, UpdateTicketRequest, ReplaceTicketRequest, StoreTicketCommentRequest
    'max' => [
        'string' => 'The :attribute field must not be greater than :max characters.',
    ],

    // Used in StoreTicketRequest, UpdateTicketRequest, ReplaceTicketRequest, StoreTicketCommentRequest
    'min' => [
        'string' => 'The :attribute field must be at least :min characters.',
    ],

    // Used in UpdateTicketRequest, ReplaceTicketRequest
    'nullable' => 'The :attribute field may be null.',

    // Used in StoreTicketRequest
    'prohibited_if' => 'The :attribute field is prohibited when :other is :value.',

    // Used in StoreTicketRequest, UpdateTicketRequest, ReplaceTicketRequest, StoreAuthTokenRequest, StoreTicketCommentRequest
    'required' => 'The :attribute field is required.',

    // Used in UpdateTicketRequest, StoreTicketCommentRequest
    'sometimes' => 'The :attribute field is optional.',

    // Used in StoreTicketRequest, UpdateTicketRequest, ReplaceTicketRequest, StoreAuthTokenRequest, StoreTicketCommentRequest
    'string' => 'The :attribute field must be a string.',

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | Here you may specify custom validation messages for attributes using the
    | convention "rule.attribute" to name the lines. This makes it quick to
    | specify a specific custom language line for a given attribute rule.
    |
    */

    'custom' => [
        'attribute-name' => [
            'rule-name' => 'custom-message',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Custom Validation Attributes
    |--------------------------------------------------------------------------
    |
    | The following language lines are used to swap our attribute placeholder
    | with something more reader friendly such as "E-Mail Address" instead
    | of "email". This simply helps us make our message more expressive.
    |
    */

    'attributes' => [
        'email' => 'email address',
        'password' => 'password',
        'device_name' => 'device name',
        'title' => 'title',
        'description' => 'description',
        'status' => 'status',
        'priority' => 'priority',
        'user_id' => 'user',
        'assigned_to' => 'assigned agent',
        'body' => 'comment',
        'is_internal' => 'internal flag',
    ],

];
