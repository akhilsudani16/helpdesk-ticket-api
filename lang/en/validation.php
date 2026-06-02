<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Validation Language Lines
    |--------------------------------------------------------------------------
    |
    | The following language lines contain the default error messages used by
    | the validator class. Some of these rules have multiple versions such
    | as the size rules. Feel free to tweak each of these messages here.
    |
    */

    // Ticket Controller validation messages
    'ticket_found' => 'Ticket found successfully.',
    'ticket_create' => 'Ticket created successfully.',
    'ticket_update' => 'Ticket updated successfully.',
    'ticket_user_update' => 'Customers must use PATCH for partial updates.',
    'ticket_replaced' => 'Ticket replaced successfully.',
    'ticket_deleted' => 'Ticket deleted successfully.',


    // TicketComment Controller validation messages
    'comment_create' => 'Comment created successfully.',
    'internal_comment_permission' => 'You cannot create internal comments.',
    'comment_not_belongs_to_ticket' => 'Comment does not belong to this ticket.',
    'comment_deleted' => 'Comment deleted successfully.',

    // request validation messages

    'status_is_prohibited' => 'You cannot update the status field.',
    'priority_is_prohibited' => 'You cannot update the priority field.',
    'assigned_to_is_prohibited' => 'You cannot update the assigned_to field.',

    'device_name_is_invalid' => 'The selected device name is invalid. Allowed devices are: ',

    'customer_not_allow_internal_comment' => 'You are not allowed to create internal comments.',
    'body_is_required' => 'Comment body is required.',
    'comment_body_min' => 'Comment must be at least 3 characters.',
    'comment_body_max' => 'Comment cannot exceed 2000 characters.',
    'is_internal_must_be_boolean'  => 'The is_internal field must be true or false.',


    // Ticket Policy validation messages

    // Ticket Comment policy
    'ticket_view_comment_permission_denied' => 'You do not have permission to view comments on this ticket.',
    'internal_comment_permission_denied' => 'You do not have permission to view internal comments.',
    'ticket_comment_create_permission_denied' => 'You do not have permission to create comments on this ticket.',
    'internal_comment_create_permission_denied' => 'Only administrators and agents can create internal comments.',
    'comment_delete_permission_denied' => 'You can only delete your own comments.',

    // Ticket policy
    'ticket_view_permission_denied' => 'You do not have permission to view this ticket.',
    'ticket_update_permission_denied' => 'You do not have permission to update this ticket.',
    'ticket_only_open_delete_permission' => 'You can only delete tickets with status "open". This ticket is ',
    'ticket_delete_permission_denied' => 'You do not have permission to delete this ticket.',

    // User policy
    'user_view_permission_denied' => 'You do not have permission to view this user.',

    // Ticket Filter
    'unsupported_include' => 'Unsupported include parameter: ',
    'allowed' => '. Allowed: ',
    'unsupported_filter' => 'Unsupported filter(s): ',
];
