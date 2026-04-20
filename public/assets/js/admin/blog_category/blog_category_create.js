$(document).ready(function() {
    
    // Form validation and interactivity
    const form = $('#addBlogCategoryForm');
    const nameInput = $('#categoryName');
    const descriptionInput = $('#categoryDescription');
    const statusSelect = $('#categoryStatus');
    const resetBtn = $('#resetBtn');
    
    // Character counter for name field (optional enhancement)
    nameInput.on('input', function() {
        const maxLength = 255;
        const currentLength = $(this).val().length;
        
        // Remove any existing character counter
        $(this).siblings('.character-counter').remove();
        
        if (currentLength > maxLength * 0.8) {
            const remaining = maxLength - currentLength;
            const counterClass = remaining < 0 ? 'text-danger' : 'text-warning';
            $(this).after(`<small class="character-counter ${counterClass}">${remaining} characters remaining</small>`);
        }
    });
    
    // Auto-resize textarea
    descriptionInput.on('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight) + 'px';
    });
    
    // Form validation on submit
    form.on('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').empty();
        
        // Validate name field
        if (nameInput.val().trim() === '') {
            nameInput.addClass('is-invalid');
            $('#nameError').text('Category name is required.');
            isValid = false;
        } else if (nameInput.val().trim().length > 255) {
            nameInput.addClass('is-invalid');
            $('#nameError').text('Category name cannot exceed 255 characters.');
            isValid = false;
        }
        
        // Validate description length (optional, but good practice)
        if (descriptionInput.val().length > 1000) {
            descriptionInput.addClass('is-invalid');
            $('#descriptionError').text('Description cannot exceed 1000 characters.');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            
            // Scroll to first error
            const firstError = $('.is-invalid').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 300);
                firstError.focus();
            }
            
            return false;
        }
        
        // Add loading state to save button
        const saveBtn = form.find('button[type="submit"]');
        const originalText = saveBtn.html();
        saveBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Creating...');
        
        // Disable form inputs to prevent double submission
        form.find('input, select, textarea').prop('disabled', true);
        
        // If form validation passes, let it submit normally
        // The loading state will persist until page redirect
    });
    
    // Reset form functionality
    resetBtn.on('click', function(e) {
        e.preventDefault();
        
        if (confirm(window.blogCategoryCreateTranslations.reset_confirm)) {
            // Reset all form fields
            form[0].reset();
            
            // Clear validation states
            $('.is-invalid').removeClass('is-invalid');
            $('.invalid-feedback').empty();
            $('.character-counter').remove();
            
            // Reset textarea height
            descriptionInput.css('height', 'auto');
            
            // Focus on first input
            nameInput.focus();
            
            // Show success message
            showNotification('Form has been reset successfully.', 'info');
        }
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Focus management for accessibility
    nameInput.focus();
    
    // Prevent form submission on Enter key in input fields (except textarea)
    form.find('input').on('keypress', function(e) {
        if (e.which === 13) { // Enter key
            e.preventDefault();
            
            // Move to next input or submit if it's the last one
            const inputs = form.find('input, select, textarea').not(':disabled');
            const currentIndex = inputs.index(this);
            
            if (currentIndex < inputs.length - 1) {
                inputs.eq(currentIndex + 1).focus();
            } else {
                form.submit();
            }
        }
    });
    
    // Show notification function
    function showNotification(message, type = 'success') {
        const alertClass = type === 'success' ? 'alert-success' : 
                          type === 'error' ? 'alert-danger' : 
                          type === 'warning' ? 'alert-warning' : 'alert-info';
        
        const notification = $(`
            <div class="alert ${alertClass} alert-dismissible fade show position-fixed" 
                 style="top: 20px; right: 20px; z-index: 9999; min-width: 300px;" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `);
        
        $('body').append(notification);
        
        // Auto-dismiss after 3 seconds
        setTimeout(function() {
            notification.fadeOut('slow', function() {
                $(this).remove();
            });
        }, 3000);
    }
    
    // Handle browser back button - sử dụng jQuery để bind event
    $(window).on('beforeunload', function(e) {
        const form = $('#addBlogCategoryForm')[0];
        const formData = new FormData(form);
        let hasChanges = false;
        
        // Check if form has unsaved changes
        for (let [key, value] of formData.entries()) {
            if (value.trim() !== '' && key !== '_token') {
                hasChanges = true;
                break;
            }
        }
        
        if (hasChanges) {
            const message = 'You have unsaved changes. Are you sure you want to leave?';
            e.originalEvent.returnValue = message;
            return message;
        }
    });
    
    // Remove beforeunload listener when form is submitted
    form.on('submit', function() {
        $(window).off('beforeunload');
    });
    
    // Responsive enhancements
    function handleResponsiveChanges() {
        const isMobile = $(window).width() <= 768;
        
        if (isMobile) {
            // Adjust form layout for mobile
            $('.sticky-top').removeClass('sticky-top').addClass('mobile-sidebar');
        } else {
            // Restore desktop layout
            $('.mobile-sidebar').removeClass('mobile-sidebar').addClass('sticky-top');
        }
    }
    
    // Handle window resize với jQuery
    $(window).on('resize', handleResponsiveChanges);
    handleResponsiveChanges(); // Initial call
    
});

// Additional utility functions
function validateField(field, rules) {
    const value = field.val().trim();
    let isValid = true;
    let errorMessage = '';
    
    $.each(rules, function(index, rule) {
        if (!isValid) return false; // Skip if already invalid
        
        switch(rule.type) {
            case 'required':
                if (value === '') {
                    isValid = false;
                    errorMessage = rule.message || 'This field is required.';
                }
                break;
            case 'maxLength':
                if (value.length > rule.value) {
                    isValid = false;
                    errorMessage = rule.message || `Maximum ${rule.value} characters allowed.`;
                }
                break;
            case 'minLength':
                if (value.length < rule.value) {
                    isValid = false;
                    errorMessage = rule.message || `Minimum ${rule.value} characters required.`;
                }
                break;
        }
    });
    
    // Update field appearance
    if (isValid) {
        field.removeClass('is-invalid').addClass('is-valid');
        field.siblings('.invalid-feedback').empty();
    } else {
        field.removeClass('is-valid').addClass('is-invalid');
        field.siblings('.invalid-feedback').text(errorMessage);
    }
    
    return isValid;
}