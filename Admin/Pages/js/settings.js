// Add this to your plugin's JavaScript file or enqueue it on the settings page

jQuery(document).ready(function($) {
    // Setup AJAX nonce
    var mlf_ajax = {
        nonce: myLoginFormAjax.nonce,
        ajax_url: myLoginFormAjax.ajax_url
    };
    
    // Clear cache action
    window.clearPluginCache = function() {
        if (!confirm(mlf_strings.confirm_clear_cache)) {
            return;
        }
        
        showLoading(true);
        
        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_clear_plugin_cache',
                nonce: mlf_ajax.nonce
            },
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    showNotice('success', response.data.message);
                    setTimeout(function() {
                        location.reload();
                    }, 1500);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showLoading(false);
                showNotice('error', mlf_strings.ajax_error);
            }
        });
    };
    
    // Reset settings action
    window.resetSettings = function() {
        if (!confirm(mlf_strings.confirm_reset_settings)) {
            return;
        }
        
        showLoading(true);
        
        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_reset_settings',
                nonce: mlf_ajax.nonce,
                confirm: 'yes'
            },
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    showNotice('success', response.data.message);
                    if (response.data.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showLoading(false);
                showNotice('error', mlf_strings.ajax_error);
            }
        });
    };
    
    // Export settings action
    window.exportSettings = function() {
        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_export_settings',
                nonce: mlf_ajax.nonce
            },
            xhrFields: {
                responseType: 'blob'
            },
            success: function(response, status, xhr) {
                // Create download link
                var blob = new Blob([response], {type: 'application/json'});
                var link = document.createElement('a');
                var url = URL.createObjectURL(blob);
                var filename = 'mlf-settings-export-' + new Date().toISOString().slice(0,10) + '.json';
                
                link.href = url;
                link.download = filename;
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                URL.revokeObjectURL(url);
                
                showNotice('success', mlf_strings.export_success);
            },
            error: function() {
                showNotice('error', mlf_strings.export_error);
            }
        });
    };
    
    // Import settings action
    $('#import-file').on('change', function() {
        var file = this.files[0];
        if (!file) {
            return;
        }
        
        // Validate file
        if (file.type !== 'application/json') {
            showNotice('error', mlf_strings.invalid_file_type);
            this.value = '';
            return;
        }
        
        if (file.size > 2 * 1024 * 1024) {
            showNotice('error', mlf_strings.file_too_large);
            this.value = '';
            return;
        }
        
        if (!confirm(mlf_strings.confirm_import_settings)) {
            this.value = '';
            return;
        }
        
        showLoading(true);
        
        var formData = new FormData();
        formData.append('action', 'mlf_import_settings');
        formData.append('nonce', mlf_ajax.nonce);
        formData.append('import_file', file);
        
        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    showNotice('success', response.data.message);
                    if (response.data.reload) {
                        setTimeout(function() {
                            location.reload();
                        }, 1500);
                    }
                } else {
                    showNotice('error', response.data.message);
                }
                $('#import-file').val('');
            },
            error: function() {
                showLoading(false);
                showNotice('error', mlf_strings.import_error);
                $('#import-file').val('');
            }
        });
    });
    
    // Test connection action (optional)
    window.testConnection = function(type) {
        showLoading(true);
        
        $.ajax({
            url: mlf_ajax.ajax_url,
            type: 'POST',
            data: {
                action: 'mlf_test_connection',
                nonce: mlf_ajax.nonce,
                test_type: type || 'general'
            },
            success: function(response) {
                showLoading(false);
                if (response.success) {
                    var message = response.data.message;
                    if (response.data.results) {
                        message += '\n\n' + JSON.stringify(response.data.results, null, 2);
                    }
                    showNotice('success', message, 5000);
                } else {
                    showNotice('error', response.data.message);
                }
            },
            error: function() {
                showLoading(false);
                showNotice('error', mlf_strings.ajax_error);
            }
        });
    };
    
    // Helper: Show loading state
    function showLoading(show) {
        if (show) {
            $('body').append('<div id="mlf-loading" class="mlf-loading-overlay"><div class="mlf-loading-spinner"></div></div>');
        } else {
            $('#mlf-loading').remove();
        }
    }
    
    // Helper: Show notice message
    function showNotice(type, message, duration) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + ' is-dismissible"><p>' + message + '</p></div>');
        
        $('.wrap.my-login-form-settings').before(notice);
        
        if (duration) {
            setTimeout(function() {
                notice.fadeOut(function() {
                    $(this).remove();
                });
            }, duration);
        }
        
        // Make notice dismissible
        notice.on('click', '.notice-dismiss', function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        });
    }
});

// CSS styles for loading overlay and notices
var mlf_styles = `
<style>
.mlf-loading-overlay {
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.7);
    z-index: 999999;
    display: flex;
    align-items: center;
    justify-content: center;
}
.mlf-loading-spinner {
    width: 50px;
    height: 50px;
    border: 5px solid #f3f3f3;
    border-top: 5px solid #3498db;
    border-radius: 50%;
    animation: mlf-spin 1s linear infinite;
}
@keyframes mlf-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
.button-block {
    display: block;
    width: 100%;
    margin-bottom: 10px;
    text-align: center;
}
.button-warning {
    background: #dc3232 !important;
    border-color: #c41c1c !important;
    color: white !important;
}
.button-warning:hover {
    background: #c41c1c !important;
}
</style>
`;

$('head').append(mlf_styles);