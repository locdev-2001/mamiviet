$(document).ready(function() {
    
    // Initialize page
    initializeCouponList();
    
    /**
     * Initialize coupon list functionality
     */
    function initializeCouponList() {
        // Remove handleAddCouponButton() since we no longer need to handle the add button
        handleActionButtonHovers();
        handleResponsiveFeatures();
    }
    
    
    /**
     * Handle hover effects for action buttons
     */
    function handleActionButtonHovers() {
        // Desktop table buttons
        $('.btn-group .btn').hover(
            function() {
                $(this).addClass('shadow-sm');
            },
            function() {
                $(this).removeClass('shadow-sm');
            }
        );
        
        // Mobile card buttons
        $('.coupon-item-card .btn').hover(
            function() {
                $(this).addClass('shadow-sm');
            },
            function() {
                $(this).removeClass('shadow-sm');
            }
        );
    }
    
    /**
     * Handle responsive features
     */
    function handleResponsiveFeatures() {
        // Handle window resize
        $(window).on('resize', function() {
            handleResponsiveAdjustments();
        });
        
        // Initial responsive adjustments
        handleResponsiveAdjustments();
    }
    
    /**
     * Handle responsive adjustments
     */
    function handleResponsiveAdjustments() {
        var windowWidth = $(window).width();
        
        // Adjust button text visibility on very small screens
        if (windowWidth < 576) {
            $('.coupon-item-card .btn span').hide();
        } else if (windowWidth >= 576 && windowWidth < 992) {
            $('.coupon-item-card .btn span').show();
        }
    }
    
    
    /**
     * Check if list is empty and show appropriate message
     */
    function checkEmptyState() {
        var $tableRows = $('.table tbody tr');
        var $cardItems = $('.coupon-item-card');
        
        if ($tableRows.length === 0 && $cardItems.length === 0) {
            var emptyMessage = '<div class="text-center py-5">' +
                              '<i class="fas fa-inbox fa-3x text-muted mb-3"></i>' +
                              '<h5 class="text-muted">' + (window.couponTranslations?.no_data || 'No data available!') + '</h5>' +
                              '</div>';
            
            $('.card-body').html(emptyMessage);
        }
    }
    
    /**
     * Show alert message
     */
    function showAlert(message, type) {
        var alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                       message +
                       '<button type="button" class="btn-close" data-bs-dismiss="alert"></button>' +
                       '</div>';
        
        // Prepend to content area
        $('.content-area').prepend(alertHtml);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    }
    
    
});