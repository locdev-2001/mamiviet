/**
 * Blog Create - Summernote Editor Initialization
 * Handles the initialization and configuration of Summernote rich text editor
 */

// Global variable to store cursor position
window.lastKnownCursorPosition = null;

$(document).ready(function() {
    // Check if Summernote is loaded
    if (typeof $.fn.summernote === 'undefined') {
        console.error('Summernote not loaded. Check CDN connection.');
        // Fallback: Show regular textarea
        $('.summernote').show();
        return;
    }
    
    // Initialize Summernote with jQuery
    initializeSummernote();
    
    // Track cursor position continuously
    $(document).on('summernote.change summernote.keyup summernote.mouseup summernote.focus', '.summernote', function() {
        try {
            var range = $(this).summernote('createRange');
            if (range) {
                window.lastKnownCursorPosition = {
                    editor: $(this),
                    range: range
                };
                console.log('Cursor position saved');
            }
        } catch (e) {
            console.warn('Could not save cursor position:', e);
        }
    });
});

/**
 * Initialize Summernote editor with custom configuration
 */
function initializeSummernote() {
    try {
        $('.summernote').summernote({
            height: 300,
            focus: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link', 'picture', 'video']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            // Fix modal issues with Bootstrap 5
            dialogsInBody: true,
            dialogsFade: true,
            disableDragAndDrop: false,
            callbacks: {
                onImageUpload: function(files) {
                    console.log('onImageUpload triggered for', files.length, 'files');
                    
                    // Always use batch upload for consistency
                    if (files.length > 1) {
                        uploadBatchImages(files, $(this));
                    } else {
                        uploadImage(files[0], $(this));
                    }
                },
                onInit: function() {
                    console.log('Summernote initialized successfully');
                    // Fix modal z-index issues
                    setTimeout(function() {
                        $('.note-modal').css('z-index', 1055);
                    }, 100);
                },
                onDialogShown: function() {
                    // Ensure modal backdrop doesn't interfere
                    $('.modal-backdrop').css('z-index', 1054);
                    $('.note-modal').css('z-index', 1055);
                }
            }
        });
        
        console.log('Summernote initialization completed');
        
    } catch (error) {
        console.error('Error initializing Summernote:', error);
        // Show regular textarea as fallback
        $('.summernote').removeClass('summernote').show();
    }
}

/**
 * Handle image upload for Summernote editor
 * @param {File} file - The image file to upload
 * @param {jQuery} $editor - jQuery object of the editor
 */
function uploadImage(file, $editor) {
    // Debug: Log file info
    console.log('Uploading file:', file.name, 'Size:', file.size, 'Type:', file.type);
    
    // Create form data for image upload
    var formData = new FormData();
    formData.append('image', file);
    
    // Get CSRF token from multiple sources
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || 
                   $('input[name="_token"]').val() || 
                   window.Laravel?.csrfToken;
    
    if (!csrfToken) {
        console.error('CSRF token not found!');
        showUploadError('CSRF token missing. Please refresh the page.');
        return;
    }
    
    console.log('CSRF token found:', csrfToken.substring(0, 10) + '...');
    
    // Debug: Log FormData contents
    for (let pair of formData.entries()) {
        console.log('FormData:', pair[0], typeof pair[1] === 'object' ? pair[1].name || 'File object' : pair[1]);
    }
    
    // Show loading indicator
    showUploadLoading();
    
    // Use XMLHttpRequest to ensure proper FormData handling
    var xhr = new XMLHttpRequest();
    var url = window.blogImageUploadUrl || '/admin/blogs/image-upload';
    
    xhr.open('POST', url, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        hideUploadLoading();
        
        console.log('XHR Response:', {
            status: xhr.status,
            responseText: xhr.responseText,
            contentType: xhr.getResponseHeader('content-type')
        });
        
        try {
            var response = JSON.parse(xhr.responseText);
            
            if (xhr.status === 200 && response.success) {
                console.log('Upload successful:', response);
                
                // Sử dụng global cursor position
                if (window.lastKnownCursorPosition && window.lastKnownCursorPosition.range) {
                    try {
                        console.log('Attempting to restore cursor position...');
                        
                        // Restore range
                        $editor.summernote('restoreRange', window.lastKnownCursorPosition.range);
                        
                        // Focus editor
                        $editor.summernote('focus');
                        
                        // Insert image using insertImage (should respect cursor)
                        $editor.summernote('insertImage', response.url, function($image) {
                            $image.css({
                                'max-width': '100%',
                                'height': 'auto'
                            });
                            $image.addClass('img-responsive');
                        });
                        
                        console.log('Image inserted at cursor position');
                    } catch (e) {
                        console.error('Failed to insert at cursor position:', e);
                        // Fallback to default behavior
                        $editor.summernote('insertImage', response.url);
                    }
                } else {
                    console.log('No saved cursor position, using default insertion');
                    $editor.summernote('insertImage', response.url);
                }
                
                showUploadSuccess('Image uploaded successfully!');
            } else {
                console.error('Upload failed:', response);
                var errorMsg = response.message || 'Upload failed';
                if (response.errors) {
                    var errors = response.errors;
                    errorMsg += ': ' + Object.keys(errors).map(key => errors[key].join(', ')).join('; ');
                }
                showUploadError(errorMsg);
            }
        } catch (e) {
            console.error('Failed to parse response:', e);
            showUploadError('Invalid response from server');
        }
    };
    
    xhr.onerror = function() {
        hideUploadLoading();
        console.error('XHR Error:', xhr);
        showUploadError('Network error occurred');
    };
    
    xhr.ontimeout = function() {
        hideUploadLoading();
        console.error('XHR Timeout');
        showUploadError('Upload timeout');
    };
    
    // Set timeout
    xhr.timeout = 30000; // 30 seconds
    
    // Send FormData
    console.log('Sending FormData via XMLHttpRequest...');
    xhr.send(formData);
}

/**
 * Show loading indicator during upload
 */
function showUploadLoading() {
    // You can implement a loading spinner here
    $('.summernote').summernote('disable');
}

/**
 * Hide loading indicator after upload
 */
function hideUploadLoading() {
    $('.summernote').summernote('enable');
}

/**
 * Show upload success message
 * @param {string} message - Success message to display
 */
function showUploadSuccess(message) {
    // You can customize this to use your preferred notification system
    if (typeof toastr !== 'undefined') {
        toastr.success(message);
    } else {
        console.log('Success:', message);
    }
}

/**
 * Show upload error message
 * @param {string} message - Error message to display
 */
function showUploadError(message) {
    // You can customize this to use your preferred notification system
    if (typeof toastr !== 'undefined') {
        toastr.error(message);
    } else {
        alert(message);
    }
}

/**
 * Handle batch upload of multiple images using single request
 * @param {FileList} files - Array of image files to upload
 * @param {jQuery} $editor - jQuery object of the editor
 */
function uploadBatchImages(files, $editor) {
    console.log('Starting batch upload for', files.length, 'files via single request');
    
    if (files.length === 0) {
        showUploadError('Không có file nào được chọn');
        return;
    }
    
    // Save current cursor position before starting batch upload
    var savedCursorPosition = null;
    try {
        var range = $editor.summernote('createRange');
        if (range) {
            savedCursorPosition = range;
            console.log('Saved cursor position for batch upload');
        }
    } catch (e) {
        console.warn('Could not save cursor position for batch upload:', e);
    }
    
    // Show batch upload loading
    showBatchUploadLoading(files.length);
    
    // Create form data for batch upload
    var formData = new FormData();
    
    // Add all files to FormData with 'images[]' key
    for (var i = 0; i < files.length; i++) {
        formData.append('images[]', files[i]);
        console.log('Added file', i + 1, ':', files[i].name, 'to FormData');
    }
    
    // Get CSRF token
    var csrfToken = $('meta[name="csrf-token"]').attr('content') || 
                   $('input[name="_token"]').val() || 
                   window.Laravel?.csrfToken;
    
    if (!csrfToken) {
        hideBatchUploadLoading();
        showUploadError('CSRF token không tìm thấy. Vui lòng refresh trang.');
        return;
    }
    
    // Use XMLHttpRequest for batch upload
    var xhr = new XMLHttpRequest();
    var url = window.blogBatchImageUploadUrl || '/admin/blogs/batch-image-upload';
    
    xhr.open('POST', url, true);
    xhr.setRequestHeader('X-CSRF-TOKEN', csrfToken);
    xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    
    xhr.onload = function() {
        hideBatchUploadLoading();
        
        console.log('Batch upload response:', {
            status: xhr.status,
            responseText: xhr.responseText
        });
        
        try {
            var response = JSON.parse(xhr.responseText);
            
            if (xhr.status === 200 && response.success) {
                console.log('Batch upload successful:', response);
                
                // Insert successful images into editor
                if (response.images && response.images.length > 0) {
                    insertBatchImages($editor, response.images, savedCursorPosition);
                }
                
                // Show success message
                var message = response.message || `Upload thành công ${response.images.length} ảnh`;
                showUploadSuccess(message);
                
            } else {
                console.error('Batch upload failed:', response);
                var errorMsg = response.message || 'Upload thất bại';
                if (response.errors && response.errors.length > 0) {
                    errorMsg += ': ' + response.errors.join(', ');
                }
                showUploadError(errorMsg);
            }
        } catch (e) {
            console.error('Failed to parse batch upload response:', e);
            showUploadError('Phản hồi không hợp lệ từ server');
        }
    };
    
    xhr.onerror = function() {
        hideBatchUploadLoading();
        console.error('Batch upload XHR Error:', xhr);
        showUploadError('Lỗi mạng khi upload batch');
    };
    
    xhr.ontimeout = function() {
        hideBatchUploadLoading();
        console.error('Batch upload timeout');
        showUploadError('Upload batch timeout');
    };
    
    xhr.timeout = 60000; // 60 seconds for batch upload
    
    // Send batch FormData
    console.log('Sending batch FormData via single XMLHttpRequest...');
    xhr.send(formData);
}


/**
 * Insert batch uploaded images into the editor at cursor position
 * @param {jQuery} $editor - jQuery object of the editor
 * @param {Array} images - Array of successful upload results from batch upload
 * @param {Object} savedCursorPosition - Saved cursor position
 */
function insertBatchImages($editor, images, savedCursorPosition) {
    console.log('Inserting', images.length, 'batch uploaded images into editor');
    
    try {
        // Restore cursor position if available
        if (savedCursorPosition) {
            $editor.summernote('restoreRange', savedCursorPosition);
            $editor.summernote('focus');
        }
        
        // Create HTML string with all images at once
        var imagesHtml = '';
        
        images.forEach(function(image, index) {
            var imgTag = '<img src="' + image.url + '" ' +
                        'style="max-width: 100%; height: auto; margin: 5px;" ' +
                        'class="img-responsive" ' +
                        'alt="' + (image.filename || 'Uploaded image') + '">';
            
            imagesHtml += imgTag;
            
            // Add spacing between images (except after the last one)
            if (index < images.length - 1) {
                imagesHtml += ' ';
            }
        });
        
        // Insert all images at once using pasteHTML
        if (savedCursorPosition) {
            $editor.summernote('pasteHTML', imagesHtml);
        } else {
            // Fallback to insertImage one by one
            images.forEach(function(image) {
                $editor.summernote('insertImage', image.url, function($img) {
                    $img.css({
                        'max-width': '100%',
                        'height': 'auto',
                        'margin': '5px'
                    });
                    $img.addClass('img-responsive');
                    $img.attr('alt', image.filename || 'Uploaded image');
                });
            });
        }
        
        console.log('Successfully inserted', images.length, 'batch images');
        
    } catch (e) {
        console.error('Failed to insert batch images:', e);
        // Fallback: insert images without cursor positioning
        images.forEach(function(image) {
            $editor.summernote('insertImage', image.url);
        });
    }
}

/**
 * Show batch upload loading indicator
 * @param {number} fileCount - Number of files being uploaded
 */
function showBatchUploadLoading(fileCount) {
    console.log('Showing batch upload loading for', fileCount, 'files');
    $('.summernote').summernote('disable');
    
    // You can implement a custom progress indicator here
    if (typeof toastr !== 'undefined') {
        toastr.info(`Đang upload ${fileCount} ảnh...`, 'Vui lòng đợi', {
            timeOut: 0,
            extendedTimeOut: 0,
            closeButton: false,
            progressBar: true
        });
    }
}

/**
 * Hide batch upload loading indicator
 */
function hideBatchUploadLoading() {
    $('.summernote').summernote('enable');
    
    // Clear any loading toasts
    if (typeof toastr !== 'undefined') {
        toastr.clear();
    }
}