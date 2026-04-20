/**
 * Mail Template Edit - Summernote Editor Initialization
 * Handles the initialization and configuration of Summernote rich text editor for mail template editing
 */

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

    console.log('Mail Template Edit initialized successfully');
});

/**
 * Initialize Summernote editor with custom configuration for mail template editing
 */
function initializeSummernote() {
    try {
        $('.summernote').summernote({
            height: 300,
            focus: true,
            toolbar: [
                ['style', ['style']],
                ['font', ['bold', 'italic', 'underline', 'clear']],
                ['fontname', ['fontname']],
                ['fontsize', ['fontsize']],
                ['color', ['color']],
                ['para', ['ul', 'ol', 'paragraph']],
                ['table', ['table']],
                ['insert', ['link']],
                ['view', ['fullscreen', 'codeview', 'help']]
            ],
            // Fix modal issues with Bootstrap 5
            dialogsInBody: true,
            dialogsFade: true,
            disableDragAndDrop: true, // Disabled for mail templates to prevent accidental drops

            callbacks: {
                onInit: function() {
                    console.log('Summernote initialized successfully for mail template edit');
                    // Fix modal z-index issues
                    setTimeout(function() {
                        $('.note-modal').css('z-index', 1055);
                    }, 100);
                },
                onDialogShown: function() {
                    // Ensure modal backdrop doesn't interfere
                    $('.modal-backdrop').css('z-index', 1054);
                    $('.note-modal').css('z-index', 1055);
                },
                onChange: function(contents, $editable) {
                    console.log('Mail template content changed during edit');
                }
            },
            // Mail template specific settings
            styleTags: [
                'p', 'h1', 'h2', 'h3', 'h4', 'h5', 'h6',
                {
                    title: 'Mail Header',
                    tag: 'div',
                    className: 'mail-header',
                    value: 'div'
                },
                {
                    title: 'Mail Body',
                    tag: 'div',
                    className: 'mail-body',
                    value: 'div'
                },
                {
                    title: 'Mail Footer',
                    tag: 'div',
                    className: 'mail-footer',
                    value: 'div'
                }
            ]
        });

        console.log('Summernote initialization completed for mail template edit');

    } catch (error) {
        console.error('Error initializing Summernote for mail template edit:', error);
        // Show regular textarea as fallback
        $('.summernote').removeClass('summernote').show();
    }

    // Only sync Summernote content with textarea - no validation logic
    $('#editMailTemplateForm').on('submit', function(e) {
        console.log('Edit form submitting - syncing Summernote content');

        // Sync Summernote content to textarea for server-side processing
        $('.summernote').each(function() {
            if ($(this).summernote && typeof $(this).summernote === 'function') {
                var content = $(this).summernote('code');
                console.log('Summernote content:', content);
                $(this).val(content);
                console.log('Textarea value after sync:', $(this).val());
            }
        });

        // Pure MVC: Let form submit normally, server handles all validation
    });
}
