/**
 * Blog Category List Management
 * Handles delete functionality for blog categories
 */

$(document).ready(function() {
    // Blog category delete functionality
    blogCategoryList.init();
});

const blogCategoryList = {
    
    /**
     * Initialize the blog category list functionality
     */
    init: function() {
        this.bindEvents();
    },
    
    /**
     * Bind all event listeners
     */
    bindEvents: function() {
        this.bindDeleteButtons();
    },
    
    /**
     * Bind delete button click events
     */
    bindDeleteButtons: function() {
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            
            const $button = $(this);
            const categoryId = $button.data('id');
            const confirmMessage = $button.attr('title') || 'Are you sure you want to delete this category?';
            
            blogCategoryList.showDeleteConfirmation(categoryId, confirmMessage);
        });
    },
    
    /**
     * Show delete confirmation dialog
     * @param {string|number} categoryId - The category ID to delete
     * @param {string} confirmMessage - The confirmation message (optional)
     */
    showDeleteConfirmation: function(categoryId, confirmMessage = null) {
        // Use translation if available, otherwise use parameter or default message
        const message = confirmMessage || 
                       (window.blogCategoryTranslations && window.blogCategoryTranslations.confirm_delete) || 
                       'Are you sure you want to delete this category?';
        
        if (confirm(message)) {
            this.submitDeleteForm(categoryId);
        }
    },
    
    /**
     * Submit delete form using jQuery
     * @param {string|number} categoryId - The category ID to delete
     */
    submitDeleteForm: function(categoryId) {
        const $form = this.createDeleteForm(categoryId);
        
        // Append form to body and submit
        $('body').append($form);
        $form.submit();
    },
    
    /**
     * Create delete form using jQuery
     * @param {string|number} categoryId - The category ID to delete
     * @returns {jQuery} The created form element
     */
    createDeleteForm: function(categoryId) {
        const csrfToken = $('meta[name="csrf-token"]').attr('content');
        const actionUrl = `/admin/blog-categories/delete/${categoryId}`;
        
        const $form = $('<form>', {
            method: 'POST',
            action: actionUrl,
            style: 'display: none;'
        });
        
        // Add CSRF token
        const $csrfInput = $('<input>', {
            type: 'hidden',
            name: '_token',
            value: csrfToken
        });
        
        // Add method override for DELETE
        const $methodInput = $('<input>', {
            type: 'hidden',
            name: '_method',
            value: 'DELETE'
        });
        
        // Append inputs to form
        $form.append($csrfInput).append($methodInput);
        
        return $form;
    },
    
    /**
     * Show loading state on button
     * @param {jQuery} $button - The button element
     */
    showLoadingState: function($button) {
        const originalHtml = $button.html();
        $button.data('original-html', originalHtml);
        $button.html('<i class="fas fa-spinner fa-spin"></i>').prop('disabled', true);
    },
    
    /**
     * Hide loading state on button
     * @param {jQuery} $button - The button element
     */
    hideLoadingState: function($button) {
        const originalHtml = $button.data('original-html');
        if (originalHtml) {
            $button.html(originalHtml).prop('disabled', false);
        }
    },
    
    /**
     * Handle successful delete response
     * @param {Object} response - The server response
     */
    handleDeleteSuccess: function(response) {
        // You can add success handling here if needed
        // For now, let Laravel handle the redirect with success message
        const successMessage = (window.blogCategoryTranslations && window.blogCategoryTranslations.delete_success) || 
                              'Category deleted successfully';
        console.log(successMessage);
    },
    
    /**
     * Handle delete error
     * @param {Object} error - The error response
     */
    handleDeleteError: function(error) {
        console.error('Error deleting blog category:', error);
        
        const errorMessage = (window.blogCategoryTranslations && window.blogCategoryTranslations.delete_error) || 
                            'An error occurred while deleting the category. Please try again.';
        alert(errorMessage);
    }
};