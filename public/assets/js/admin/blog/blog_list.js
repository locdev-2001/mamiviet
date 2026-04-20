$(document).ready(function() {
    
    // Initialize page
    initializeBlogList();
    
    /**
     * Initialize blog list functionality
     */
    function initializeBlogList() {
        // handleDeleteButtons(); // Disabled - now handled by blog_destroy.js
        handleAddBlogButton();
        handleActionButtonHovers();
        handleResponsiveFeatures();
    }
    
    /**
     * Handle delete button clicks for both desktop and mobile views
     */
    function handleDeleteButtons() {
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            
            var itemId = $(this).data('id');
            var itemName = getItemName($(this));
            
            // Use jQuery to show confirmation dialog
            var confirmMessage = 'Are you sure you want to delete "' + itemName + '"?';
            
            if (confirm(confirmMessage)) {
                deleteBlogItem(itemId);
            }
        });
    }
    
    /**
     * Get item name from either table row or card view
     */
    function getItemName($deleteBtn) {
        var $tableRow = $deleteBtn.closest('tr');
        var $card = $deleteBtn.closest('.blog-item-card');
        
        if ($tableRow.length > 0) {
            // Desktop table view
            return $tableRow.find('td:first').text().trim();
        } else if ($card.length > 0) {
            // Mobile card view
            return $card.find('.card-title').text().trim();
        }
        
        return 'this item';
    }
    
    /**
     * Delete blog item via AJAX
     */
    function deleteBlogItem(itemId) {
        // Show loading state
        var $deleteBtn = $('.delete-btn[data-id="' + itemId + '"]');
        var originalText = $deleteBtn.html();
        
        $deleteBtn.prop('disabled', true)
                  .html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Example AJAX call (uncomment when delete API is ready):
        /*
        $.ajax({
            url: '/admin/blogs/delete/' + itemId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Remove item from DOM with animation
                    var $item = $deleteBtn.closest('tr, .blog-item-card');
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        checkEmptyState();
                    });
                } else {
                    showAlert('Error deleting item', 'error');
                    resetDeleteButton($deleteBtn, originalText);
                }
            },
            error: function(xhr, status, error) {
                showAlert('Error deleting item: ' + error, 'error');
                resetDeleteButton($deleteBtn, originalText);
            }
        });
        */
        
        // For now, just show a placeholder
        console.log('Delete item with ID:', itemId);
        setTimeout(function() {
            resetDeleteButton($deleteBtn, originalText);
        }, 1000);
    }
    
    /**
     * Reset delete button state
     */
    function resetDeleteButton($deleteBtn, originalText) {
        $deleteBtn.prop('disabled', false)
                  .html(originalText);
    }
    
    /**
     * Handle add blog button
     */
    function handleAddBlogButton() {
        $('#addBlogBtn').on('click', function(e) {
            // Don't prevent default - let the link work normally
            // The href attribute will handle the navigation
            
            // Add loading animation
            var $btn = $(this);
            var originalHtml = $btn.html();
            
            $btn.prop('disabled', true)
                .html('<i class="fas fa-spinner fa-spin me-2"></i>Loading...');
            
            // Allow the default link behavior to proceed
        });
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
        $('.blog-item-card .btn').hover(
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
            $('.blog-item-card .btn span').hide();
        } else if (windowWidth >= 576 && windowWidth < 992) {
            $('.blog-item-card .btn span').show();
        }
    }
    
    /**
     * Check if list is empty and show appropriate message
     */
    function checkEmptyState() {
        var $tableRows = $('.table tbody tr');
        var $cardItems = $('.blog-item-card');
        
        if ($tableRows.length === 0 && $cardItems.length === 0) {
            var emptyMessage = '<div class="text-center py-5">' +
                              '<i class="fas fa-inbox fa-3x text-muted mb-3"></i>' +
                              '<h5 class="text-muted">No data available!</h5>' +
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
    
    /**
     * Handle detail and edit button clicks
     */
    $(document).on('click', '.btn-info, .btn-warning', function(e) {
        // Only handle buttons that don't have an href attribute
        if ($(this).attr('href') && $(this).attr('href') !== '#') {
            // Let the link work normally if it has a proper href
            return true;
        }
        
        e.preventDefault();
        
        var $btn = $(this);
        var itemId = $btn.closest('tr, .blog-item-card').find('.delete-btn').data('id');
        var action = $btn.hasClass('btn-info') ? 'detail' : 'edit';
        
        // Add loading state to button
        var originalHtml = $btn.html();
        $btn.prop('disabled', true)
            .html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Navigate to appropriate page
        if (action === 'detail') {
            window.location.href = '/admin/blogs/detail/' + itemId;
        } else {
            // For edit action
            window.location.href = '/admin/blogs/edit/' + itemId;
        }
    });
    
});