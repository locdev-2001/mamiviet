$(document).ready(function() {
    // Initialize ingredients functionality
    initIngredients();
    
    // Initialize image preview functionality
    initImagePreview();
    
    // Initialize form validation
    initFormValidation();
    
    // Initialize price validation
    initPriceValidation();
});

function initIngredients() {
    // Add ingredient functionality
    $('#add-ingredient').on('click', function() {
        var container = $('#ingredients-container');
        var newIngredient = $(`
            <div class="ingredient-item mb-2">
                <div class="input-group">
                    <input type="text" 
                           class="form-control" 
                           name="ingredients[]" 
                           placeholder="${window.menuCreateTranslations.ingredient_placeholder}">
                    <button type="button" class="btn btn-outline-danger remove-ingredient">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
            </div>
        `);
        container.append(newIngredient);
    });
    
    // Remove ingredient functionality
    $(document).on('click', '.remove-ingredient', function() {
        var ingredientItem = $(this).closest('.ingredient-item');
        var container = $('#ingredients-container');
        
        // Keep at least one ingredient field
        if (container.find('.ingredient-item').length > 1) {
            ingredientItem.fadeOut(300, function() {
                $(this).remove();
            });
        } else {
            // Clear the input instead of removing
            ingredientItem.find('input').val('');
        }
    });
}

function initImagePreview() {
    var maxFiles = 5;
    var maxFileSize = 2 * 1024 * 1024; // 2MB
    var allowedTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
    
    $('#images').on('change', function(e) {
        var files = e.target.files;
        var previewContainer = $('#image-preview');
        
        // Clear previous previews
        previewContainer.empty();
        
        // Validate file count
        if (files.length > maxFiles) {
            showAlert('error', window.menuCreateTranslations.max_files_exceeded);
            $(this).val('');
            return;
        }
        
        $.each(files, function(index, file) {
            // Validate file type
            if (allowedTypes.indexOf(file.type) === -1) {
                showAlert('error', window.menuCreateTranslations.invalid_file_type);
                return;
            }
            
            // Validate file size
            if (file.size > maxFileSize) {
                showAlert('error', window.menuCreateTranslations.file_too_large);
                return;
            }
            
            // Create preview
            var reader = new FileReader();
            reader.onload = function(e) {
                var imagePreview = $(`
                    <div class="col-6 col-md-4 col-lg-3">
                        <div class="image-item" data-index="${index}">
                            <img src="${e.target.result}" alt="Preview" class="img-fluid">
                            <button type="button" class="remove-image" title="${window.menuCreateTranslations.remove_image}">
                                <i class="fas fa-times"></i>
                            </button>
                        </div>
                    </div>
                `);
                previewContainer.append(imagePreview);
            };
            reader.readAsDataURL(file);
        });
    });
    
    // Remove image preview
    $(document).on('click', '.remove-image', function() {
        var imageItem = $(this).closest('.col-6, .col-md-4, .col-lg-3');
        var index = $(this).closest('.image-item').data('index');
        
        imageItem.fadeOut(300, function() {
            $(this).remove();
        });
        
        // Clear file input when removing images
        $('#images').val('');
    });
}

function initFormValidation() {
    var form = $('#menuCreateForm');
    
    form.on('submit', function(e) {
        var isValid = true;
        
        // Clear previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();
        
        // Validate required fields
        var requiredFields = ['name', 'menu_category_id', 'price'];
        $.each(requiredFields, function(i, field) {
            var input = $('[name="' + field + '"]');
            if (!input.val() || input.val().trim() === '') {
                input.addClass('is-invalid');
                input.after('<div class="invalid-feedback">This field is required.</div>');
                isValid = false;
            }
        });
        
        // Validate price
        var price = parseFloat($('#price').val());
        var discountPrice = parseFloat($('#discount_price').val());
        
        if (price <= 0) {
            $('#price').addClass('is-invalid');
            $('#price').after('<div class="invalid-feedback">Price must be greater than 0.</div>');
            isValid = false;
        }
        
        if (discountPrice && discountPrice >= price) {
            $('#discount_price').addClass('is-invalid');
            $('#discount_price').after('<div class="invalid-feedback">Discount price must be less than regular price.</div>');
            isValid = false;
        }
        
        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            var firstError = $('.is-invalid').first();
            if (firstError.length) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 500);
            }
        } else {
            // Show loading state
            var submitBtn = $('button[type="submit"]');
            submitBtn.addClass('btn-loading');
            submitBtn.prop('disabled', true);
        }
    });
}

function initPriceValidation() {
    // Real-time price validation
    $('#discount_price').on('input', function() {
        var price = parseFloat($('#price').val());
        var discountPrice = parseFloat($(this).val());
        
        if (discountPrice && price && discountPrice >= price) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
            $(this).after('<div class="invalid-feedback">Discount price must be less than regular price.</div>');
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
    });
    
    $('#price').on('input', function() {
        var price = parseFloat($(this).val());
        var discountPrice = parseFloat($('#discount_price').val());
        
        if (price <= 0) {
            $(this).addClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
            $(this).after('<div class="invalid-feedback">Price must be greater than 0.</div>');
        } else {
            $(this).removeClass('is-invalid');
            $(this).siblings('.invalid-feedback').remove();
        }
        
        // Revalidate discount price
        if (discountPrice && price && discountPrice >= price) {
            $('#discount_price').addClass('is-invalid');
            $('#discount_price').siblings('.invalid-feedback').remove();
            $('#discount_price').after('<div class="invalid-feedback">Discount price must be less than regular price.</div>');
        } else {
            $('#discount_price').removeClass('is-invalid');
            $('#discount_price').siblings('.invalid-feedback').remove();
        }
    });
}

function showAlert(type, message) {
    var alertClass = type === 'error' ? 'alert-danger' : 'alert-success';
    var alert = $(`
        <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
            ${message}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    `);
    
    // Remove existing alerts
    $('.content-area .alert').remove();
    
    // Add new alert at the top of content area
    $('.content-area').prepend(alert);
    
    // Auto dismiss after 5 seconds
    setTimeout(function() {
        alert.alert('close');
    }, 5000);
}