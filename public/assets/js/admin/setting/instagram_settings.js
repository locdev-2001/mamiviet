/**
 * Instagram Settings Page JavaScript
 * Handles token visibility toggle functionality
 */

$(document).ready(function() {
    // Mặc định ẩn token khi load trang
    let isTokenVisible = false;
    let originalToken = $('#token').val();
    
    // Ẩn token bằng cách thay thế bằng dấu *
    function hideToken() {
        if (originalToken) {
            $('#token').val('*'.repeat(originalToken.length));
        }
    }
    
    // Hiện token gốc
    function showToken() {
        $('#token').val(originalToken);
    }
    
    // Khởi tạo: ẩn token khi load trang
    hideToken();
    
    // Xử lý click nút toggle
    $('#toggleToken').on('click', function() {
        isTokenVisible = !isTokenVisible;
        
        if (isTokenVisible) {
            // Hiện token
            showToken();
            $('#toggleTokenIcon').removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            // Ẩn token
            hideToken();
            $('#toggleTokenIcon').removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Khi người dùng thay đổi nội dung token
    $('#token').on('input', function() {
        originalToken = $(this).val();
        isTokenVisible = true;
        $('#toggleTokenIcon').removeClass('fa-eye').addClass('fa-eye-slash');
    });
    
    // Trước khi submit form, đảm bảo hiện token gốc
    $('form').on('submit', function() {
        if (!isTokenVisible) {
            showToken();
        }
    });
});