$(document).ready(function() {
    // Form validation
    const $form = $('#rewardEditForm');
    const $submitBtn = $('#submitBtn');
    
    $form.on('submit', function(e) {
        // Show loading state
        $submitBtn.html('<i class="fas fa-spinner fa-spin me-2"></i>' + window.rewardEditTranslations.updating);
        $submitBtn.prop('disabled', true);
        
        // Basic client-side validation
        const name = $('#name').val().trim();
        const couponId = $('#coupon_id').val();
        const probability = parseFloat($('#probability').val());
        
        if (!name) {
            e.preventDefault();
            alert(window.rewardEditTranslations.name_required);
            resetSubmitButton();
            return;
        }
        
        if (!couponId) {
            e.preventDefault();
            alert(window.rewardEditTranslations.coupon_required);
            resetSubmitButton();
            return;
        }
        
        if (isNaN(probability) || probability < 0 || probability > 100) {
            e.preventDefault();
            alert(window.rewardEditTranslations.probability_invalid);
            resetSubmitButton();
            return;
        }
    });
    
    function resetSubmitButton() {
        $submitBtn.html('<i class="fas fa-save me-2"></i>' + window.rewardEditTranslations.update);
        $submitBtn.prop('disabled', false);
    }
    
    // Auto-hide alerts after 5 seconds
    $('.alert').each(function() {
        const $alert = $(this);
        setTimeout(function() {
            $alert.fadeOut(500, function() {
                $(this).remove();
            });
        }, 5000);
    });
    
    // Input formatting
    $('#probability').on('input', function() {
        let value = parseFloat($(this).val());
        if (value > 100) {
            $(this).val(100);
        } else if (value < 0) {
            $(this).val(0);
        }
    });
    
    // Enhanced form interactions
    $('.form-control').on('focus', function() {
        $(this).closest('.mb-4').addClass('focused');
    }).on('blur', function() {
        $(this).closest('.mb-4').removeClass('focused');
    });
    
    // Real-time validation feedback
    $('#name').on('input', function() {
        const $this = $(this);
        const value = $this.val().trim();
        
        if (value.length === 0) {
            $this.removeClass('is-valid').addClass('is-invalid');
        } else if (value.length > 255) {
            $this.removeClass('is-valid').addClass('is-invalid');
        } else {
            $this.removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    $('#coupon_id').on('change', function() {
        const $this = $(this);
        const value = $this.val();
        
        if (!value) {
            $this.removeClass('is-valid').addClass('is-invalid');
        } else {
            $this.removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    $('#probability').on('input', function() {
        const $this = $(this);
        const value = parseFloat($this.val());
        
        if (isNaN(value) || value < 0 || value > 100) {
            $this.removeClass('is-valid').addClass('is-invalid');
        } else {
            $this.removeClass('is-invalid').addClass('is-valid');
        }
    });
    
    // Smooth scroll to error if validation fails
    $form.on('invalid', function() {
        const $firstInvalid = $('.is-invalid').first();
        if ($firstInvalid.length) {
            $('html, body').animate({
                scrollTop: $firstInvalid.offset().top - 100
            }, 300);
            $firstInvalid.focus();
        }
    });
});