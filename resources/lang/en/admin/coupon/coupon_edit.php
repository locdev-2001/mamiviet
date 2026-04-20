<?php

return [
    // Page titles and headers
    'page_title' => 'Edit Coupon',
    'page_header' => 'Edit Coupon',
    'back_to_list' => 'Back to List',

    // Form fields
    'name' => 'Coupon Name',
    'name_placeholder' => 'Enter coupon name',
    'help_name' => 'Enter a descriptive name for this coupon',
    
    'type' => 'Discount Type',
    'type_placeholder' => 'Select discount type',
    'percentage' => 'Percentage (%)',
    'fix' => 'Fixed Amount ($)',
    'help_type' => 'Choose between percentage discount or fixed amount discount',
    
    'value' => 'Discount Value',
    'value_help_percentage' => 'Enter discount percentage (1-100)',
    'value_help_fix' => 'Enter fixed discount amount in dollars',
    'value_help_default' => 'Enter discount value',
    
    'max_uses' => 'Maximum Uses',
    'max_uses_help' => 'Maximum number of times this coupon can be used',
    'unlimited_placeholder' => 'Leave empty for unlimited',
    'max_uses_help_optional' => 'Leave this field empty for unlimited uses',

    // Current coupon info
    'current_info' => 'Current Coupon Information',
    'coupon_code' => 'Coupon Code',
    'created_at' => 'Created Date',

    // Action buttons
    'update' => 'Update Coupon',
    'cancel' => 'Cancel',

    // Messages
    'update_success' => 'Coupon updated successfully!',
    'update_error' => 'Error occurred while updating coupon. Please try again.',
    'percentage_max_error' => 'Percentage value cannot exceed 100%',
];