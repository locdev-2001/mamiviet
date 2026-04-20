/**
 * Mail Configuration Page JavaScript
 * Handles password toggle functionality with database password display
 */

$(document).ready(function() {
    const passwordField = $('#mail_password');
    const toggleIcon = $('#toggleMailPasswordIcon');
    let realPassword = passwordField.data('real-password') || '';
    let isShowingReal = false;
    
    // Initialize - show dots if there's a real password
    if (realPassword && passwordField.attr('type') === 'password') {
        passwordField.val('••••••••••••'); // Show dots initially
        isShowingReal = false;
    }
    
    // Password toggle functionality
    $('#toggleMailPassword').on('click', function() {
        if (passwordField.attr('type') === 'password') {
            // Show real password as text
            passwordField.attr('type', 'text');
            passwordField.val(realPassword);
            toggleIcon.removeClass('fa-eye').addClass('fa-eye-slash');
            isShowingReal = true;
        } else {
            // Hide password - show as dots
            passwordField.attr('type', 'password');
            if (realPassword) {
                passwordField.val('••••••••••••');
                isShowingReal = false;
            }
            toggleIcon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Handle form submission - ensure real password is submitted
    $('form').on('submit', function() {
        if (realPassword && !isShowingReal) {
            passwordField.val(realPassword);
        }
    });
    
    // Handle input changes - update real password when user types
    passwordField.on('input', function() {
        const currentValue = $(this).val();
        if (currentValue !== '••••••••••••' && currentValue !== realPassword) {
            // User is typing new password
            realPassword = currentValue;
            isShowingReal = true;
        }
    });
});