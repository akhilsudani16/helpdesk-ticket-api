<?php

return [

    /*
    |--------------------------------------------------------------------------
    | API Response Messages
    |--------------------------------------------------------------------------
    |
    | The following language lines are used for API responses throughout
    | the application. You can modify these messages as needed.
    |
    */

    // Ticket messages
    'tickets' => [
        'retrieved' => 'Tickets retrieved successfully.',
        'created' => 'Ticket created successfully.',
        'updated' => 'Ticket updated successfully.',
        'replaced' => 'Ticket replaced successfully.',
        'deleted' => 'Ticket deleted successfully.',
        'show' => 'Ticket retrieved successfully.',
        'cannot_create_ticket' => 'You cannot create a ticket',
    ],

    // Comment messages
    'comments' => [
        'retrieved' => 'Comments retrieved successfully.',
        'created' => 'Comment created successfully.',
        'deleted' => 'Comment deleted successfully.',
    ],

    // User messages
    'users' => [
        'retrieved' => 'Users retrieved successfully.',
        'show' => 'User retrieved successfully.',
        'cannot_view_customer'=> 'You cannot view the customer',
    ],

    // Auth messages
    'auth' => [
        'token_created' => 'Token created successfully.',
        'token_deleted' => 'Token revoked successfully.',
        'login_success' => 'Login successful.',
        'logout_success' => 'Logout successful.',
        'invalid_credentials' => 'Invalid credentials.',
        'unauthorized' => 'Unauthorized.',
        'forbidden' => 'You do not have permission to perform this action.',
    ],

    // Error messages
    'errors' => [
        'not_found' => 'Resource not found',
        'validation_failed' => 'Validation failed',
        'server_error' => 'Internal server error',
        'forbidden' => 'Forbidden',
        'unauthorized' => 'Unauthorized',
        'customers_use_patch' => 'Customers must use PATCH for partial updates',
        'cannot_delete_ticket' => 'You cannot delete the ticket',
        'customers_cannot_create_comments' => 'Customers cannot view internal comments.',
    ],

    // Health check
    'health' => [
        'ok' => 'API is running',
    ],

    // General messages
    'success' => 'Request successful',
    'no_content' => 'No content',

];
