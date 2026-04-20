$(document).ready(function() {
    
    // Initialize delete functionality
    initializeDeleteButtons();
    
    /**
     * Initialize delete button handlers
     */
    function initializeDeleteButtons() {
        handleDeleteButtonClicks();
    }
    
    /**
     * Handle delete button clicks for both desktop and mobile views
     */
    function handleDeleteButtonClicks() {
        // Remove any existing handlers first to avoid conflicts
        $(document).off('click', '.delete-btn');
        
        $(document).on('click', '.delete-btn', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            var itemId = $(this).data('id');
            var itemName = getItemName($(this));
            
            // Get localized confirmation message
            var confirmMessage = getConfirmMessage(itemName);
            
            console.log('Delete confirmation message:', confirmMessage); // Debug log
            
            if (confirm(confirmMessage)) {
                deleteRewardItem(itemId, $(this));
            }
        });
    }
    
    /**
     * Get item name from either table row or card view
     */
    function getItemName($deleteBtn) {
        var $tableRow = $deleteBtn.closest('tr');
        var $card = $deleteBtn.closest('.reward-item-card');
        
        if ($tableRow.length > 0) {
            // Desktop table view - get text from second td, remove tags
            return $tableRow.find('td:nth-child(2)').text().trim();
        } else if ($card.length > 0) {
            // Mobile card view
            return $card.find('.card-title').text().trim();
        }
        
        return 'this item';
    }
    
    /**
     * Get localized confirmation message
     */
    function getConfirmMessage(itemName) {
        console.log('Window translations:', window.rewardTranslations); // Debug log
        console.log('Item name:', itemName); // Debug log
        
        // Try to get localized message with item name from window translations
        if (window.rewardTranslations && window.rewardTranslations.confirm_delete_item) {
            var message = window.rewardTranslations.confirm_delete_item.replace(':name', itemName);
            console.log('Using confirm_delete_item:', message); // Debug log
            return message;
        }
        
        // Fallback to generic message if confirm_delete_item is not available
        if (window.rewardTranslations && window.rewardTranslations.confirm_delete) {
            var message = window.rewardTranslations.confirm_delete;
            console.log('Using confirm_delete:', message); // Debug log
            return message;
        }
        
        // Final fallback to default message with quotes around item name
        var defaultMessage = 'Are you sure you want to delete "' + itemName + '"?';
        console.log('Using default message:', defaultMessage); // Debug log
        return defaultMessage;
    }
    
    /**
     * Delete reward item via AJAX - Using web route
     */
    function deleteRewardItem(itemId, $deleteBtn) {
        // Show loading state
        var originalHtml = $deleteBtn.html();
        
        $deleteBtn.prop('disabled', true)
                  .html('<i class="fas fa-spinner fa-spin"></i>');
        
        // Get CSRF token
        var csrfToken = $('meta[name="csrf-token"]').attr('content');
        
        // Use the web route for deleting reward (MVC approach)
        $.ajax({
            url: '/admin/rewards/delete/' + itemId,
            type: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': csrfToken,
                'Accept': 'application/json'
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showAlert(getLocalizedMessage('delete_success'), 'success');
                    
                    // Remove item from DOM with animation
                    var $item = $deleteBtn.closest('tr, .reward-item-card');
                    $item.fadeOut(300, function() {
                        $(this).remove();
                        checkEmptyState();
                    });
                } else {
                    // Show error message
                    showAlert(getLocalizedMessage('delete_error'), 'error');
                    resetDeleteButton($deleteBtn, originalHtml);
                }
            },
            error: function(xhr, status, error) {
                var errorMessage = getLocalizedMessage('delete_error');
                
                // Try to get error message from response
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                } else if (xhr.status === 404) {
                    errorMessage = 'Reward not found.';
                } else if (xhr.status === 403) {
                    errorMessage = 'You do not have permission to delete this reward.';
                }
                
                showAlert(errorMessage, 'error');
                resetDeleteButton($deleteBtn, originalHtml);
            }
        });
    }
    
    /**
     * Reset delete button to original state
     */
    function resetDeleteButton($deleteBtn, originalHtml) {
        $deleteBtn.prop('disabled', false)
                  .html(originalHtml);
    }
    
    /**
     * Get localized message
     */
    function getLocalizedMessage(key) {
        var messages = {
            'delete_success': 'Reward deleted successfully!',
            'delete_error': 'Error occurred while deleting reward!',
            'no_data': 'No data available!'
        };
        
        // Try to get from window translations first
        if (window.rewardTranslations && window.rewardTranslations[key]) {
            return window.rewardTranslations[key];
        }
        
        return messages[key] || 'An error occurred.';
    }
    
    /**
     * Check if list is empty and show appropriate message
     */
    function checkEmptyState() {
        var $tableRows = $('.table tbody tr');
        var $cardItems = $('.reward-item-card');
        
        if ($tableRows.length === 0 && $cardItems.length === 0) {
            var emptyMessage = '<div class="text-center py-5">' +
                              '<i class="fas fa-inbox fa-3x text-muted mb-3"></i>' +
                              '<h5 class="text-muted">' + getLocalizedMessage('no_data') + '</h5>' +
                              '</div>';
            
            $('.card-body').html(emptyMessage);
        }
    }
    
    /**
     * Show alert message
     */
    function showAlert(message, type) {
        var alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
        var iconClass = type === 'error' ? 'fas fa-exclamation-triangle' : 'fas fa-check-circle';
        
        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                       '<i class="' + iconClass + ' me-2"></i>' +
                       message +
                       '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>' +
                       '</div>';
        
        // Remove existing alerts
        $('.alert').remove();
        
        // Prepend to main content area
        $('.row:first').before(alertHtml);
        
        // Auto dismiss after 5 seconds
        setTimeout(function() {
            $('.alert').fadeOut(300, function() {
                $(this).remove();
            });
        }, 5000);
        
        // Scroll to top to show alert
        $('html, body').animate({ scrollTop: 0 }, 300);
    }
    
});