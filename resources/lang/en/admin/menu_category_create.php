<?php

return [
    // Page Title & Headers
    'page_title' => 'Add Category',
    'page_header' => 'Add New Category',
    'breadcrumb_home' => 'Dashboard',
    'breadcrumb_categories' => 'Categories',
    'breadcrumb_create' => 'Add Category',
    
    // Form Labels
    'form_name_label' => 'Category Name',
    'form_name_placeholder' => 'Enter category name',
    'form_description_label' => 'Description',
    'form_description_placeholder' => 'Enter category description (optional)',
    'form_parent_label' => 'Parent Category',
    'form_parent_none' => 'None (Main Category)',
    'form_image_label' => 'Category Image',
    'form_position_label' => 'Position',
    'form_position_help' => 'Display order position (0 = first)',
    'form_status_label' => 'Status',
    'form_status_active' => 'Active',
    'form_status_inactive' => 'Inactive',
    
    // Buttons
    'btn_back' => 'Back',
    'btn_cancel' => 'Cancel',
    'btn_save' => 'Save Category',
    'btn_saving' => 'Saving...',
    'btn_remove_image' => 'Remove Image',
    
    // Messages
    'success_created' => 'Category created successfully!',
    'error_validation' => 'Please check the form for errors.',
    'error_server' => 'An error occurred while saving. Please try again.',
    'error_unauthorized' => 'You are not authorized to perform this action.',
    'error_network' => 'Network error. Please check your connection.',
    
    // Validation Messages
    'validation_name_required' => 'Category name is required.',
    'validation_name_max' => 'Category name cannot exceed 255 characters.',
    'validation_description_max' => 'Description cannot exceed 1000 characters.',
    'validation_position_numeric' => 'Position must be a number.',
    'validation_position_min' => 'Position must be 0 or greater.',
    
    // Required Field Indicator
    'required_field' => 'Required field',
    'optional_field' => 'Optional',
    
    // Loading States
    'loading_save' => 'Saving category...',
    'loading_form' => 'Loading form...',
    
    // Form Sections
    'section_basic_info' => 'Basic Information',
    'section_settings' => 'Settings',
    
    // Help Text
    'help_name' => 'Enter a unique name for this category',
    'help_description' => 'Provide a brief description of what this category contains',
    'help_parent' => 'Select a parent category to create a sub-category',
    'help_image' => 'Upload an image for this category (JPG, PNG, GIF, SVG - max 2MB)',
    'help_position' => 'Lower numbers appear first in the list',
    'help_status' => 'Only active categories will be visible to customers',
];