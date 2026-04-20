<?php

return [
    'page_title' => 'Create New Blog Category',
    'page_header' => 'Create New Blog Category',
    'form_title' => 'Blog Category Information',
    'back_to_list' => 'Back to Blog Category List',
    
    // Form fields
    'name' => 'Category Name',
    'name_placeholder' => 'Enter category name',
    'help_name' => 'Enter a descriptive name for the blog category',
    'description' => 'Description',
    'description_placeholder' => 'Enter category description',
    'help_description' => 'Enter a brief description of the blog category',
    'status' => 'Status',
    'active' => 'Active',
    'inactive' => 'Inactive',
    'help_status' => 'Set the visibility status of the blog category',
    
    // Buttons
    'reset' => 'Reset Form',
    'create' => 'Create Category',
    'cancel' => 'Cancel',
    
    // Messages
    'create_success' => 'Blog category created successfully!',
    'create_error' => 'An error occurred while creating the blog category. Please try again.',
    'validation_error' => 'Please fix the errors below and try again.',
    'reset_confirm' => 'Are you sure you want to reset the form? All data will be lost.',
    
    // Validation messages
    'name_required' => 'The category name is required.',
    'name_string' => 'The category name must be a string.',
    'name_max' => 'The category name may not be greater than 255 characters.',
    'description_string' => 'The description must be a string.',
    'is_active_boolean' => 'The status field must be true or false.',
];