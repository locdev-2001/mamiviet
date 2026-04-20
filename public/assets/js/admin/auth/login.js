$(document).ready(function() {
    const $loginForm = $('#loginForm');
    const $loginBtn = $('#loginBtn');
    const $loading = $('#loading');
    const $alert = $('#alert');

    // Messages will be passed from blade template
    let messages = window.loginMessages || {};

    // Get CSRF token
    const token = $('meta[name="csrf-token"]').attr('content');

    $loginForm.on('submit', function(e) {
        e.preventDefault();

        const email = $('#email').val();
        const password = $('#password').val();

        // Hide old alert
        hideAlert();

        // Show loading
        showLoading();

        $.ajax({
            url: '/api/admin/auth/login',
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': token
            },
            data: JSON.stringify({
                email: email,
                password: password
            }),
            success: function(data) {
                if (data.success) {
                    // Save token to localStorage
                    localStorage.setItem('admin_token', data.data.access_token);
                    localStorage.setItem('admin_user', JSON.stringify(data.data));

                    showAlert(messages.loginSuccess, 'success');

                    // Redirect after 1.5 seconds
                    setTimeout(function() {
                        window.location.href = '/admin/dashboard';
                    }, 1500);
                } else {
                    showAlert(data.message || messages.loginFailed, 'error');
                }
            },
            error: function(xhr, status, error) {
                console.error('Login error:', error);
                showAlert(messages.loginError, 'error');
            },
            complete: function() {
                hideLoading();
            }
        });
    });

    function showAlert(message, type) {
        $alert.text(message);
        $alert.attr('class', `alert alert-${type}`);
        $alert.show();
    }

    function hideAlert() {
        $alert.hide();
    }

    function showLoading() {
        $loginBtn.prop('disabled', true);
        $loginBtn.text(messages.processing);
        $loading.show();
    }

    function hideLoading() {
        $loginBtn.prop('disabled', false);
        $loginBtn.text(messages.login);
        $loading.hide();
    }
});