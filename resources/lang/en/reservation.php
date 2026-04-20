<?php

return [
    'status' => [
        'pending' => 'Pending',
        'confirmed' => 'Confirmed',
        'cancelled' => 'Cancelled',
        'completed' => 'Completed',
    ],

    'validation' => [
        'name_required' => 'The name field is required.',
        'email_required' => 'The email field is required.',
        'email_invalid' => 'The email must be a valid email address.',
        'phone_required' => 'The phone field is required.',
        'persons_required' => 'The number of persons field is required.',
        'persons_min' => 'The minimum number of persons is 1.',
        'persons_max' => 'The maximum number of persons is 20.',
        'date_required' => 'The date field is required.',
        'date_future' => 'Please select a valid date.', // Note: Past dates are now allowed
        'time_required' => 'The time field is required.',
        'time_format' => 'The time must be in HH:MM format.',
        'time_operating_hours' => 'Reservations are only available between 11:00 and 22:00.',
        'status_required' => 'The status field is required.',
        'status_invalid' => 'The selected status is invalid.',
    ],

    'messages' => [
        'created' => 'Reservation created successfully. Staff will arrange a table for you.',
        'updated' => 'Reservation updated successfully.',
        'deleted' => 'Reservation deleted successfully.',
        'cancelled' => 'Reservation cancelled successfully.',
        'status_updated' => 'Reservation status updated successfully.',
        'not_found' => 'Reservation not found.',
        'cannot_update' => 'This reservation can no longer be updated.',
        'cannot_cancel' => 'This reservation can no longer be cancelled.',
        'unauthorized' => 'You are not authorized to perform this action.',
    ],

    'availability' => [
        'available' => 'Available for the requested time and number of persons.',
        'not_available' => 'Not available. Remaining capacity: :remaining persons.',
        'checking' => 'Checking availability...',
    ],

    'fields' => [
        'name' => 'Name',
        'email' => 'Email Address',
        'phone' => 'Phone Number',
        'persons' => 'Number of Persons',
        'date' => 'Date',
        'time' => 'Time',
        'status' => 'Status',
        'admin_notes' => 'Admin Notes',
        'created_at' => 'Created At',
        'updated_at' => 'Last Updated',
    ],

    'actions' => [
        'create' => 'Create Reservation',
        'update' => 'Update Reservation',
        'delete' => 'Delete Reservation',
        'cancel' => 'Cancel Reservation',
        'confirm' => 'Confirm Reservation',
        'complete' => 'Complete Reservation',
        'view' => 'View Reservation',
        'edit' => 'Edit Reservation',
    ],
];