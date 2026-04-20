// Simple MVC form handling for Edit Category
$(document).ready(function() {
    // Category data and parent categories are now loaded from server-side
    
    // Image preview functionality
    $('#categoryImage').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
                $('#currentImage').hide();
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Remove image preview
    $('#removeImage').on('click', function() {
        $('#categoryImage').val('');
        $('#imagePreview').hide();
        if ($('#currentImg').length) {
            $('#currentImage').show();
        }
    });
    
    // Simple form validation before submit
    $('#editCategoryForm').on('submit', function(e) {
        let isValid = true;
        
        // Clear previous errors
        $('.form-control').removeClass('is-invalid');
        $('.invalid-feedback').text('');
        
        // Validate required fields
        if (!$('#categoryName').val().trim()) {
            $('#categoryName').addClass('is-invalid');
            $('#nameError').text('Category name is required');
            isValid = false;
        }
        
        const position = parseInt($('#categoryPosition').val());
        if (isNaN(position) || position < 0) {
            $('#categoryPosition').addClass('is-invalid');
            $('#positionError').text('Position must be 0 or greater');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            return false;
        }
        
        // Show loading state
        const $updateBtn = $('#updateCategoryBtn');
        $updateBtn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Updating...'
        );
    });
});

// All data is now loaded from server-side, no AJAX needed