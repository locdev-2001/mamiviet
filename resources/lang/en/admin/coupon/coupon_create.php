<?php

return [
    'page_title' => 'Create New Coupon',
    'page_header' => 'Create New Coupon',
    'form_title' => 'Coupon Information',
    'back_to_list' => 'Back to Coupon List',
    
    // Form fields
    'name' => 'Coupon Name',
    'name_placeholder' => 'Enter coupon name',
    'help_name' => 'Enter a descriptive name for the coupon',
    'type' => 'Discount Type',
    'type_placeholder' => 'Select discount type',
    'help_type' => 'Choose between percentage or fixed amount discount',
    'percentage' => 'Percentage',
    'fix' => 'Fixed Amount',
    'value' => 'Discount Value',
    'value_placeholder' => 'Enter discount value',
    'max_uses' => 'Maximum Uses',
    'max_uses_placeholder' => 'Enter maximum number of uses',
    'unlimited_placeholder' => 'Leave empty for unlimited',
    
    // Help texts
    'value_help_percentage' => 'Enter percentage value (1-100)',
    'value_help_fix' => 'Enter fixed discount amount in dollars',
    'value_help_default' => 'Enter discount value',
    'max_uses_help' => 'Maximum number of times this coupon can be used',
    'max_uses_help_optional' => 'Leave this field empty for unlimited uses',
    
    // Buttons
    'reset' => 'Reset Form',
    'create' => 'Create Coupon',
    'cancel' => 'Cancel',
    
    // Messages
    'create_success' => 'Coupon created successfully!',
    'create_error' => 'An error occurred while creating the coupon. Please try again.',
    'reset_confirm' => 'Are you sure you want to reset the form? All data will be lost.',
    'percentage_max_error' => 'Percentage value cannot exceed 100%.',
];