$(document).ready(function() {
    // Auto-initialize on pages that need authentication
    const protectedPages = [
        '/admin/dashboard',
        '/admin/',
        '/admin/menu-categories/list/',
        '/admin/menu-categories/create/',
        '/admin/menu-categories/edit/{id}/',
        '/admin/menu/list/',
        '/admin/menu/create/'
    ];
    // Auth Middleware - Check token and handle authentication

    // Check if token exists in localStorage on page load
    function checkAuthToken() {
        const token = localStorage.getItem('auth_token');
        const adminToken = localStorage.getItem('admin_token');

        // If no token found, redirect to login
        if (!token && !adminToken) {
            window.location.href = '/admin/login';
            return false;
        }
        return true;
    }

    // Redirect to dashboard if already logged in and trying to access login page
    function redirectIfLoggedIn() {
        console.log('redirect admin')
        const token = localStorage.getItem('auth_token');
        const adminToken = localStorage.getItem('admin_token');

        if (token || adminToken) {
            window.location.href = '/admin/dashboard';
        }
    }

    // Setup AJAX defaults with token
    function setupAjaxDefaults() {
        const token = localStorage.getItem('auth_token') || localStorage.getItem('admin_token');

        if (token) {
            $.ajaxSetup({
                headers: {
                    'Authorization': 'Bearer ' + token,
                    'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                }
            });
        }
    }

    // Global AJAX error handler for 401 responses
    $(document).ajaxError(function(event, xhr, settings) {
        if (xhr.status === 401) {
            // Remove invalid tokens
            localStorage.removeItem('auth_token');
            localStorage.removeItem('admin_token');

            // Redirect to login page
            window.location.href = '/admin/login';
        }
    });

    // Monitor localStorage for token changes
    function monitorTokenChanges() {
        let lastAdminToken = localStorage.getItem('admin_token');
        let lastAuthToken = localStorage.getItem('auth_token');

        setInterval(function() {
            const currentAdminToken = localStorage.getItem('admin_token');
            const currentAuthToken = localStorage.getItem('auth_token');

            // Check if tokens were removed manually
            if ((lastAdminToken && !currentAdminToken) || (lastAuthToken && !currentAuthToken)) {
                console.log('Token removed, redirecting to login...');
                window.location.href = '/admin/login';
                return;
            }

            // Update last known tokens
            lastAdminToken = currentAdminToken;
            lastAuthToken = currentAuthToken;
        }, 1000); // Check every second
    }

    // Initialize authentication middleware
    function initAuthMiddleware() {
        // Check token on page load
        if (!checkAuthToken()) {
            return;
        }

        // Setup AJAX defaults with token
        setupAjaxDefaults();

        // Start monitoring token changes
        monitorTokenChanges();
    }


    const currentPath = window.location.pathname;

    // Handle login page - only check if already logged in
    if (currentPath.includes('/admin/login')) {
        const adminToken = localStorage.getItem('admin_token');
        const authToken = localStorage.getItem('auth_token');

        if (adminToken || authToken) {
            window.location.href = '/admin/dashboard';
        }
        return;
    }

    // Only run on protected pages
    if (protectedPages.some(page => currentPath.includes(page))) {
        initAuthMiddleware();
    }

    // Expose functions globally for manual initialization
    window.AuthMiddleware = {
        init: initAuthMiddleware,
        checkToken: checkAuthToken,
        setupAjax: setupAjaxDefaults,
        redirectIfLoggedIn: redirectIfLoggedIn
    };
});
