// Simple MVC form handling for Add Category
$(document).ready(function() {
    // Parent categories are now loaded from server-side
    
    // Image preview functionality
    $('#categoryImage').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
                $('#previewImg').attr('src', e.target.result);
                $('#imagePreview').show();
            }
            reader.readAsDataURL(file);
        }
    });
    
    // Remove image preview
    $('#removeImage').on('click', function() {
        $('#categoryImage').val('');
        $('#imagePreview').hide();
    });

    // Simple form validation before submit
    $('#addCategoryForm').on('submit', function(e) {
        console.log('Form submitted!');
        console.log('Form action:', $(this).attr('action'));
        console.log('Form method:', $(this).attr('method'));
        
        // Show loading state
        const $saveBtn = $('#saveCategoryBtn');
        $saveBtn.prop('disabled', true).html(
            '<span class="spinner-border spinner-border-sm me-2"></span>Saving...'
        );
        
        // Let form submit normally for now
        return true;
    });
});

// Parent categories are loaded from server-side, no AJAX needed