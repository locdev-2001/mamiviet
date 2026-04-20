$(document).ready(function() {
    // Initialize tooltips if needed
    $('[data-bs-toggle="tooltip"]').each(function() {
        new bootstrap.Tooltip(this);
    });

    // Add any additional functionality here
    console.log('Reward list page loaded');
});