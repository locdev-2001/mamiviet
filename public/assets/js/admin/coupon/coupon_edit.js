$(document).ready(function() {
    
    // Update icon and help text based on type selection
    function updateValueDisplay() {
        var selectedType = $('#couponType').val();
        var valueIcon = $('#value-icon');
        var valueHelp = $('#value-help');
        
        if (selectedType === 'percentage') {
            valueIcon.removeClass().addClass('fas fa-percentage');
            valueHelp.text(window.couponEditTranslations.value_help_percentage);
        } else if (selectedType === 'fix') {
            valueIcon.removeClass().addClass('fas fa-dollar-sign');
            valueHelp.text(window.couponEditTranslations.value_help_fix);
        } else {
            valueIcon.removeClass().addClass('fas fa-hashtag');
            valueHelp.text(window.couponEditTranslations.value_help_default);
        }
    }
    
    // Initial load
    updateValueDisplay();
    
    // On type change
    $('#couponType').on('change', function() {
        updateValueDisplay();
    });
    
    // Form validation on submit
    $('#editCouponForm').on('submit', function(e) {
        var type = $('#couponType').val();
        var value = parseInt($('#couponValue').val());
        
        if (type === 'percentage' && value > 100) {
            e.preventDefault();
            alert(window.couponEditTranslations.percentage_max_error);
            return false;
        }
        
        // Add loading state to update button
        var updateBtn = $('#updateCouponBtn');
        var originalText = updateBtn.html();
        updateBtn.prop('disabled', true).html('<i class="fas fa-spinner fa-spin me-2"></i>Updating...');
        
        // If form validation passes, let it submit normally
        // The loading state will persist until page redirect
    });
    
    // Auto-dismiss alerts after 5 seconds
    setTimeout(function() {
        $('.alert').fadeOut('slow');
    }, 5000);
    
    // Add smooth scrolling to form validation errors
    if ($('.is-invalid').length > 0) {
        $('html, body').animate({
            scrollTop: $('.is-invalid').first().offset().top - 100
        }, 500);
    }
    
    // Show current values on page load for better UX
    function highlightChangedFields() {
        $('input, select').on('change input', function() {
            $(this).addClass('field-changed');
        });
    }
    
    highlightChangedFields();
    
});