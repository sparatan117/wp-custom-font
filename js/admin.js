jQuery(document).ready(function($) {
    $('.delete-font').on('click', function(e) {
        e.preventDefault();
        
        if (!confirm('Are you sure you want to delete this font?')) {
            return;
        }

        var button = $(this);
        var fontFile = button.data('font');
        
        $.ajax({
            url: cfuAjax.ajaxurl,
            type: 'POST',
            data: {
                action: 'cfu_delete_font',
                font: fontFile,
                nonce: cfuAjax.nonce
            },
            beforeSend: function() {
                button.prop('disabled', true);
            },
            success: function(response) {
                if (response.success) {
                    button.closest('li').fadeOut(300, function() {
                        $(this).remove();
                        if ($('.uploaded-fonts li').length === 0) {
                            $('.uploaded-fonts').parent().find('h3').remove();
                            $('.uploaded-fonts').remove();
                        }
                    });
                } else {
                    alert('Failed to delete font. Please try again.');
                    button.prop('disabled', false);
                }
            },
            error: function() {
                alert('An error occurred. Please try again.');
                button.prop('disabled', false);
            }
        });
    });
}); 