$(document).ready(function() {
    // Initialize
    updateSelectedCount();
    updateButtonStates();
    initializeResponsiveFeatures();
    restoreActiveTab();

    // Handle "Assign to All Users" checkbox
    $('#assignToAll').on('change', function() {
        if ($(this).is(':checked')) {
            // Disable user selection when "assign to all" is checked
            $('#multipleUsersSection').css({
                'opacity': '0.5',
                'pointer-events': 'none'
            });
            
            // Uncheck all user checkboxes
            $('.user-checkbox').prop('checked', false);
            
            // Update selected count display
            $('#selectedCount')
                .html(window.assignTranslations.all_users_selected)
                .removeClass()
                .addClass('badge bg-primary');
        } else {
            // Re-enable user selection
            $('#multipleUsersSection').css({
                'opacity': '1',
                'pointer-events': 'auto'
            });
            updateSelectedCount();
        }
        updateButtonStates();
    });

    // Handle user search
    $('#userSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;

        $('.user-item').each(function() {
            const userName = $(this).data('user-name');
            const userEmail = $(this).data('user-email');
            
            if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                $(this).show();
                visibleCount++;
            } else {
                $(this).hide();
            }
        });

        // Show "no users found" message if needed
        if (visibleCount === 0 && searchTerm !== '') {
            if ($('#noUsersMessage').length === 0) {
                const noUsersMessage = $('<div>')
                    .attr('id', 'noUsersMessage')
                    .addClass('text-center text-muted py-3')
                    .html('<i class="fas fa-search me-2"></i>' + window.assignTranslations.no_users_found);
                $('#usersList').append(noUsersMessage);
            }
        } else {
            $('#noUsersMessage').remove();
        }
    });

    // Handle individual user checkbox changes
    $('.user-checkbox').on('change', function() {
        // If any user is selected manually, uncheck "assign to all"
        if ($(this).is(':checked')) {
            $('#assignToAll').prop('checked', false);
            $('#multipleUsersSection').css({
                'opacity': '1',
                'pointer-events': 'auto'
            });
        }
        updateSelectedCount();
        updateButtonStates();
    });

    // Handle single user select change
    $('#singleUserSelect').on('change', function() {
        updateButtonStates();
    });

    // Update selected users count
    function updateSelectedCount() {
        if ($('#assignToAll').is(':checked')) {
            return;
        }
        
        const count = $('.user-checkbox:checked').length;
        
        $('#selectedCount')
            .html(count + ' ' + window.assignTranslations.users_selected)
            .removeClass()
            .addClass(count > 0 ? 'badge bg-success' : 'badge bg-secondary');
    }

    // Update button states
    function updateButtonStates() {
        // Single user tab button
        const singleUserSelected = $('#singleUserSelect').val() !== '';
        $('#singleAssignBtn').prop('disabled', !singleUserSelected);
        
        // Multiple users tab button
        const multipleUsersSelected = $('#assignToAll').is(':checked') || 
                                    $('.user-checkbox:checked').length > 0;
        $('#multipleAssignBtn').prop('disabled', !multipleUsersSelected);
    }

    // Handle form submission
    $('#assignCouponForm').on('submit', function(e) {
        const activeTab = $('.tab-pane.active');
        
        // Update active tab input before submission
        const currentTab = activeTab.attr('id') === 'single-user' ? 'single-user' : 'multiple-users';
        $('#activeTabInput').val(currentTab);
        
        // Clean up any previous dynamic inputs
        $('input[name="user_id[]"][type="hidden"]').remove();
        
        if (activeTab.attr('id') === 'single-user') {
            // For single user tab
            const selectedUserId = $('#singleUserSelect').val();
            if (selectedUserId) {
                // Disable all checkboxes to prevent them from being submitted
                $('.user-checkbox').prop('disabled', true);
                
                // Add hidden input for single user
                $(this).append($('<input>')
                    .attr('type', 'hidden')
                    .attr('name', 'user_id[]')
                    .val(selectedUserId)
                );
                
                // Ensure assign_to_all is not checked
                $('#assignToAll').prop('checked', false);
                
                console.log('Single user selected:', selectedUserId);
            } else {
                e.preventDefault();
                alert('Please select a user.');
                return false;
            }
        } else {
            // For multiple users tab
            const assignToAll = $('#assignToAll').is(':checked');
            const selectedUserCheckboxes = $('.user-checkbox:checked');
            const selectedUsers = selectedUserCheckboxes.length;
            
            console.log('Multiple users tab - Assign to all:', assignToAll, 'Selected users:', selectedUsers);
            
            if (!assignToAll && selectedUsers === 0) {
                e.preventDefault();
                alert('Please select users or check "Assign to All Users".');
                return false;
            }
            
            // If assign to all is checked, clear individual selections
            if (assignToAll) {
                $('.user-checkbox').prop('checked', false);
                console.log('Assign to all - cleared individual selections');
            }
            
            // Disable single user select to prevent it from being submitted
            $('#singleUserSelect').prop('disabled', true);
        }

        // Show loading state
        const submitBtn = activeTab.attr('id') === 'single-user' ? $('#singleAssignBtn') : $('#multipleAssignBtn');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true)
                 .html('<i class="fas fa-spinner fa-spin me-2"></i>Processing...');
        
        // Set timeout to re-enable button if form doesn't submit properly
        setTimeout(function() {
            // Re-enable disabled elements
            $('.user-checkbox').prop('disabled', false);
            $('#singleUserSelect').prop('disabled', false);
            
            if (submitBtn.prop('disabled')) {
                submitBtn.prop('disabled', false).html(originalText);
            }
        }, 10000);
    });

    // Handle tab switches
    $('#assignTabs button[data-bs-toggle="tab"]').on('shown.bs.tab', function(e) {
        // Update active tab tracking
        const activeTab = $(e.target).data('tab');
        $('#activeTabInput').val(activeTab);
        
        // Store in localStorage for persistence
        localStorage.setItem('assign_coupon_active_tab', activeTab);
        
        updateButtonStates();
        adjustMobileLayout();
        
        console.log('Active tab changed to:', activeTab);
    });

    // Initialize responsive features
    function initializeResponsiveFeatures() {
        // Handle window resize
        $(window).on('resize', debounce(function() {
            adjustMobileLayout();
            adjustUsersContainer();
        }, 250));

        // Initial layout adjustment
        adjustMobileLayout();
        adjustUsersContainer();

        // Improve mobile touch experience
        if (isMobileDevice()) {
            $('.user-item').on('touchstart', function() {
                $(this).addClass('touched');
            }).on('touchend', function() {
                setTimeout(() => {
                    $(this).removeClass('touched');
                }, 200);
            });
        }

        // Add smooth scrolling for tabs on mobile
        $('.nav-tabs').on('scroll', function() {
            $(this).addClass('scrolling');
            clearTimeout($(this).data('scrollTimer'));
            $(this).data('scrollTimer', setTimeout(() => {
                $(this).removeClass('scrolling');
            }, 150));
        });
    }

    // Adjust layout for mobile devices
    function adjustMobileLayout() {
        const isMobile = $(window).width() <= 768;
        const isSmallMobile = $(window).width() <= 576;
        
        if (isMobile) {
            // Adjust button layout
            $('.d-grid.d-md-flex').removeClass('d-md-flex').addClass('d-grid');
            
            // Compact form elements
            $('.form-select-lg').removeClass('form-select-lg').addClass('form-select');
            $('.btn-lg').removeClass('btn-lg');
            
            // Adjust spacing
            $('.mb-4').removeClass('mb-4').addClass('mb-3');
        } else {
            // Restore desktop layout
            $('.d-grid').not('.permanent-grid').removeClass('d-grid').addClass('d-md-flex');
            $('.form-select').not('.keep-regular').removeClass('form-select').addClass('form-select-lg');
            $('.btn').not('.keep-regular').addClass('btn-lg');
            $('.mb-3').not('.keep-mb-3').removeClass('mb-3').addClass('mb-4');
        }

        if (isSmallMobile) {
            // Extra compact for very small screens
            $('.card-body').css('padding', '0.75rem');
            $('.users-container').css('padding', '0.5rem');
        } else {
            $('.card-body').css('padding', '');
            $('.users-container').css('padding', '');
        }
    }

    // Adjust users container height based on content
    function adjustUsersContainer() {
        const container = $('.users-container');
        const windowHeight = $(window).height();
        const containerOffset = container.offset()?.top || 0;
        const isMobile = $(window).width() <= 768;
        
        let maxHeight;
        if (isMobile) {
            maxHeight = Math.min(250, windowHeight - containerOffset - 200);
        } else {
            maxHeight = Math.min(400, windowHeight - containerOffset - 150);
        }
        
        container.css('max-height', maxHeight + 'px');
    }

    // Debounce function for performance
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Check if device is mobile
    function isMobileDevice() {
        return /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent) || 
               $(window).width() <= 768;
    }

    // Enhanced search with better mobile experience
    $('#userSearch').on('input', function() {
        const searchTerm = $(this).val().toLowerCase().trim();
        let visibleCount = 0;

        $('.user-item').each(function() {
            const userName = $(this).data('user-name');
            const userEmail = $(this).data('user-email');
            
            if (userName.includes(searchTerm) || userEmail.includes(searchTerm)) {
                $(this).show().addClass('search-match');
                visibleCount++;
            } else {
                $(this).hide().removeClass('search-match');
            }
        });

        // Update search results indicator
        updateSearchResults(visibleCount, searchTerm);
        
        // Adjust container height after search
        adjustUsersContainer();
    });

    // Update search results
    function updateSearchResults(count, searchTerm) {
        if (count === 0 && searchTerm !== '') {
            if ($('#noUsersMessage').length === 0) {
                const noUsersMessage = $('<div>')
                    .attr('id', 'noUsersMessage')
                    .addClass('text-center text-muted py-3')
                    .html('<i class="fas fa-search me-2"></i>' + window.assignTranslations.no_users_found);
                $('#usersList').append(noUsersMessage);
            }
        } else {
            $('#noUsersMessage').remove();
        }

        // Add search count indicator
        if (searchTerm !== '') {
            let searchInfo = $('#searchInfo');
            if (searchInfo.length === 0) {
                searchInfo = $('<small>')
                    .attr('id', 'searchInfo')
                    .addClass('form-text text-muted mt-1');
                $('#userSearch').after(searchInfo);
            }
            searchInfo.text(`${count} users found`);
        } else {
            $('#searchInfo').remove();
        }
    }

    // Restore active tab from localStorage or server-side old() data
    function restoreActiveTab() {
        // First, check if there's old() data from server (validation errors)
        const serverActiveTab = $('#activeTabInput').val();
        
        // If no server data, check localStorage
        const storedTab = localStorage.getItem('assign_coupon_active_tab');
        
        // Determine which tab should be active
        let targetTab = serverActiveTab;
        if (!targetTab || targetTab === 'single-user') {
            targetTab = storedTab || 'single-user';
        }
        
        console.log('Restoring tab:', targetTab, {
            serverActiveTab: serverActiveTab,
            storedTab: storedTab
        });
        
        // If target is not single-user, activate the correct tab
        if (targetTab === 'multiple-users') {
            // Remove active class from single-user tab
            $('#single-user-tab').removeClass('active');
            $('#single-user').removeClass('show active');
            
            // Add active class to multiple-users tab
            $('#multiple-users-tab').addClass('active');
            $('#multiple-users').addClass('show active');
            
            // Update hidden input
            $('#activeTabInput').val('multiple-users');
            
            console.log('Activated multiple-users tab');
        } else {
            // Ensure single-user is active (default)
            $('#single-user-tab').addClass('active');
            $('#single-user').addClass('show active');
            $('#multiple-users-tab').removeClass('active');
            $('#multiple-users').removeClass('show active');
            
            // Update hidden input
            $('#activeTabInput').val('single-user');
            
            console.log('Activated single-user tab');
        }
        
        // Update button states after tab restore
        setTimeout(function() {
            updateButtonStates();
        }, 100);
    }
});