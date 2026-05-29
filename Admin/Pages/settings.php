<?php
/**
 * Settings Template
 * 
 * @package MyLoginForm\Admin
 */

// Prevent Direct Access
defined('ABSPATH') || exit;

// Globalize WordPress database object
global $wpdb;

// Ensure variables are defined with defaults
$settings = $settings ?? array();
$system_info = $system_info ?? array();

// Set default values for all settings to prevent undefined array key warnings
$default_settings = array(
    'default_redirect' => 'home',
    'woocommerce_integration' => 0,
    'enable_recaptcha' => 0,
    'recaptcha_site_key' => '',
    'recaptcha_secret_key' => '',
    'enable_2fa' => 0,
    'max_login_attempts' => 5,
    'lockout_time' => 900,
    'session_timeout' => 3600,
    'email_verification' => 0,
    'welcome_email' => 1,
    'admin_notifications' => 1,
    'custom_css' => '',
    'custom_js' => '',
    'delete_data_on_uninstall' => 0,
);

// Merge with existing settings to ensure all keys exist
$settings = wp_parse_args($settings, $default_settings);

// Get MySQL version safely
$mysql_version = 'Unknown';
if ($wpdb && method_exists($wpdb, 'get_var')) {
    $mysql_version = $wpdb->get_var("SELECT VERSION()");
    if (!$mysql_version) {
        $mysql_version = $wpdb->db_version(); // Fallback method
    }
}

// Set default system info
$default_system_info = array(
    'woocommerce_active' => class_exists('WooCommerce'),
    'plugin_version' => defined('MLF_VERSION') ? MLF_VERSION : '1.0.0',
    'wordpress_version' => get_bloginfo('version'),
    'php_version' => phpversion(),
    'mysql_version' => $mysql_version,
    'wp_memory_limit' => WP_MEMORY_LIMIT,
    'max_execution_time' => ini_get('max_execution_time'),
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'forms_count' => 0,
    'users_count' => count_users()['total_users'],
    'active_theme' => wp_get_theme()->get('Name'),
);

$system_info = wp_parse_args($system_info, $default_system_info);

// Show success message if settings were saved
if (isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true') {
    echo '<div class="notice notice-success is-dismissible"><p>' . __('Settings saved successfully!', 'my-login-form') . '</p></div>';
}
?>

<div class="wrap my-login-form-settings">
    <div class="settings-header">
        <h1 class="wp-heading-inline"><i class="fas fa-cog"></i> <?php _e('Plugin Settings', 'my-login-form'); ?></h1>
        <a href="<?php echo admin_url('admin.php?page=my-login-form-dashboard'); ?>" class="page-title-action">
            <i class="fas fa-arrow-left"></i> <?php _e('Back to Dashboard', 'my-login-form'); ?>
        </a>
    </div>
    
    <hr class="wp-header-end">
    
    <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
        <?php wp_nonce_field('my_login_form_save_settings'); ?>
        <input type="hidden" name="action" value="my_login_form_save_settings">
        
        <div class="settings-layout">
            <!-- Main Settings -->
            <div class="settings-main">
                <!-- General Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-sliders-h"></i> <?php _e('General Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label for="default_redirect"><?php _e('Default Redirect After Login', 'my-login-form'); ?></label>
                            <select name="default_redirect" id="default_redirect" class="regular-text">
                                <option value="home" <?php selected($settings['default_redirect'], 'home'); ?>>
                                    <?php _e('Home Page', 'my-login-form'); ?>
                                </option>
                                <option value="my_profile" <?php selected($settings['default_redirect'], 'my_profile'); ?>>
                                    <?php _e('My Profile Page', 'my-login-form'); ?>
                                </option>
                            </select>
                            <p class="description"><?php _e('Where users are redirected after successful login'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="woocommerce_integration" value="1" 
                                       <?php checked($settings['woocommerce_integration'], 1); ?>
                                       <?php echo !$system_info['woocommerce_active'] ? 'disabled' : ''; ?>>
                                <span><?php _e('Enable WooCommerce Integration', 'my-login-form'); ?></span>
                            </label>
                            <?php if ($system_info['woocommerce_active']): ?>
                                <p class="description success">✅ <?php _e('WooCommerce is active. Integration enabled.', 'my-login-form'); ?></p>
                            <?php else: ?>
                                <p class="description warning">⚠️ <?php _e('WooCommerce is not installed or activated.', 'my-login-form'); ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- Security Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-shield-alt"></i> <?php _e('Security Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="enable_recaptcha" id="enable_recaptcha" value="1" 
                                       <?php checked($settings['enable_recaptcha'], 1); ?>>
                                <span><?php _e('Enable Google reCAPTCHA', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('Protect forms from spam and bots', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field sub-field" style="margin-left: 25px; <?php echo !$settings['enable_recaptcha'] ? 'display: none;' : ''; ?>">
                            <label for="recaptcha_site_key"><?php _e('reCAPTCHA Site Key', 'my-login-form'); ?></label>
                            <input type="text" name="recaptcha_site_key" id="recaptcha_site_key" 
                                   value="<?php echo esc_attr($settings['recaptcha_site_key']); ?>" 
                                   class="regular-text">
                        </div>
                        
                        <div class="form-field sub-field" style="margin-left: 25px; <?php echo !$settings['enable_recaptcha'] ? 'display: none;' : ''; ?>">
                            <label for="recaptcha_secret_key"><?php _e('reCAPTCHA Secret Key', 'my-login-form'); ?></label>
                            <input type="password" name="recaptcha_secret_key" id="recaptcha_secret_key" 
                                   value="<?php echo esc_attr($settings['recaptcha_secret_key']); ?>" 
                                   class="regular-text">
                        </div>
                        
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="enable_2fa" value="1" 
                                       <?php checked($settings['enable_2fa'], 1); ?>>
                                <span><?php _e('Enable Two-Factor Authentication (2FA)', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('Add an extra layer of security to user accounts', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="max_login_attempts"><?php _e('Maximum Login Attempts', 'my-login-form'); ?></label>
                            <input type="number" name="max_login_attempts" id="max_login_attempts" 
                                   value="<?php echo esc_attr($settings['max_login_attempts']); ?>" 
                                   class="small-text" min="1" max="20">
                            <p class="description"><?php _e('Number of failed login attempts before lockout', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="lockout_time"><?php _e('Lockout Time (seconds)', 'my-login-form'); ?></label>
                            <input type="number" name="lockout_time" id="lockout_time" 
                                   value="<?php echo esc_attr($settings['lockout_time']); ?>" 
                                   class="small-text" min="60" max="86400">
                            <p class="description"><?php _e('How long to lock out after too many attempts (default: 15 minutes)', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="session_timeout"><?php _e('Session Timeout (seconds)', 'my-login-form'); ?></label>
                            <input type="number" name="session_timeout" id="session_timeout" 
                                   value="<?php echo esc_attr($settings['session_timeout']); ?>" 
                                   class="small-text" min="60" max="86400">
                            <p class="description"><?php _e('How long to keep users logged in (default: 1 hour)', 'my-login-form'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Email Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-envelope"></i> <?php _e('Email Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="email_verification" value="1" 
                                       <?php checked($settings['email_verification'], 1); ?>>
                                <span><?php _e('Require Email Verification', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('Users must verify their email address before logging in', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="welcome_email" value="1" 
                                       <?php checked($settings['welcome_email'], 1); ?>>
                                <span><?php _e('Send Welcome Email', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('Send a welcome email to new users after registration', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="admin_notifications" value="1" 
                                       <?php checked($settings['admin_notifications'], 1); ?>>
                                <span><?php _e('Admin Notifications', 'my-login-form'); ?></span>
                            </label>
                            <p class="description"><?php _e('Notify admin when new users register', 'my-login-form'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Custom CSS & JS -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-code"></i> <?php _e('Custom Code', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label for="custom_css"><?php _e('Global Custom CSS', 'my-login-form'); ?></label>
                            <textarea name="custom_css" id="custom_css" rows="10" class="code-editor" 
                                      placeholder="<?php _e('/* Add global CSS that applies to all forms */', 'my-login-form'); ?>"><?php echo esc_textarea($settings['custom_css']); ?></textarea>
                            <p class="description"><?php _e('CSS added here will be applied to all forms site-wide', 'my-login-form'); ?></p>
                        </div>
                        
                        <div class="form-field">
                            <label for="custom_js"><?php _e('Global Custom JavaScript', 'my-login-form'); ?></label>
                            <textarea name="custom_js" id="custom_js" rows="10" class="code-editor" 
                                      placeholder="<?php _e('// Add global JavaScript for all forms', 'my-login-form'); ?>"><?php echo esc_textarea($settings['custom_js']); ?></textarea>
                            <p class="description"><?php _e('JavaScript added here will run on all forms site-wide', 'my-login-form'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Advanced Settings -->
                <div class="settings-card">
                    <div class="card-header">
                        <h2><i class="fas fa-exclamation-triangle"></i> <?php _e('Advanced Settings', 'my-login-form'); ?></h2>
                    </div>
                    <div class="card-body">
                        <div class="form-field">
                            <label class="checkbox-label">
                                <input type="checkbox" name="delete_data_on_uninstall" value="1" 
                                       <?php checked($settings['delete_data_on_uninstall'], 1); ?>>
                                <span><?php _e('Delete Data on Uninstall', 'my-login-form'); ?></span>
                            </label>
                            <p class="description warning">
                                <i class="fas fa-exclamation-triangle"></i> 
                                <?php _e('Warning: All plugin data will be permanently deleted when the plugin is uninstalled.', 'my-login-form'); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="settings-sidebar">
                <!-- System Information -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-info-circle"></i> <?php _e('System Information', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="system-info-table">
                            <tr>
                                <td><?php _e('Plugin Version', 'my-login-form'); ?>:</td>
                                <td><strong><?php echo esc_html($system_info['plugin_version']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('WordPress', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['wordpress_version']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('PHP Version', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['php_version']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('MySQL Version', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['mysql_version']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Memory Limit', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['wp_memory_limit']); ?></td>
                            </tr>
                            <tr>
                                <td><?php _e('Max Execution Time', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['max_execution_time']); ?>s</strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Upload Limit', 'my-login-form'); ?>:</td>
                                <td><?php echo esc_html($system_info['upload_max_filesize']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Statistics -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-chart-bar"></i> <?php _e('Statistics', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <table class="system-info-table">
                            <tr>
                                <td><?php _e('Total Forms', 'my-login-form'); ?>:</td>
                                <td><strong><?php echo number_format_i18n($system_info['forms_count']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Total Users', 'my-login-form'); ?>:</td>
                                <td><strong><?php echo number_format_i18n($system_info['users_count']); ?></strong></td>
                            </tr>
                            <tr>
                                <td><?php _e('Active Theme', 'my-login-form'); ?>:</strong></td>
                                <td><?php echo esc_html($system_info['active_theme']); ?></strong></td>
                            </tr>
                        </table>
                    </div>
                </div>
                
                <!-- Quick Actions -->
                <div class="settings-card">
                    <div class="card-header">
                        <h3><i class="fas fa-bolt"></i> <?php _e('Quick Actions', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="quick-actions">
                            <button type="button" onclick="clearPluginCache()" class="button button-block">
                                <i class="fas fa-trash-alt"></i> <?php _e('Clear Plugin Cache', 'my-login-form'); ?>
                            </button>
                            <button type="button" onclick="resetSettings()" class="button button-block button-warning">
                                <i class="fas fa-undo-alt"></i> <?php _e('Reset to Defaults', 'my-login-form'); ?>
                            </button>
                            <button type="button" onclick="exportSettings()" class="button button-block">
                                <i class="fas fa-download"></i> <?php _e('Export Settings', 'my-login-form'); ?>
                            </button>
                            <label for="import-file" class="button button-block">
                                <i class="fas fa-upload"></i> <?php _e('Import Settings', 'my-login-form'); ?>
                                <input type="file" id="import-file" style="display: none;" accept=".json">
                            </label>
                        </div>
                    </div>
                </div>
                
                <!-- Documentation -->
                <div class="settings-card docs-card">
                    <div class="card-header">
                        <h3><i class="fas fa-book"></i> <?php _e('Documentation', 'my-login-form'); ?></h3>
                    </div>
                    <div class="card-body">
                        <div class="docs-links">
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-play-circle"></i> <?php _e('Getting Started Guide', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-video"></i> <?php _e('Video Tutorials', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-question-circle"></i> <?php _e('FAQ', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-bug"></i> <?php _e('Report a Bug', 'my-login-form'); ?>
                            </a>
                            <a href="#" target="_blank" class="doc-link">
                                <i class="fas fa-star"></i> <?php _e('Rate this Plugin', 'my-login-form'); ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Submit Button -->
        <div class="settings-footer">
            <button type="submit" class="button button-primary button-hero">
                <i class="fas fa-save"></i> <?php _e('Save All Settings', 'my-login-form'); ?>
            </button>
            <p class="description"><?php _e('Settings will be applied immediately after saving', 'my-login-form'); ?></p>
        </div>
    </form>
</div>

<style>
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
.system-info-table {
    width: 100%;
}
.system-info-table td {
    padding: 5px 0;
}
.system-info-table td:first-child {
    font-weight: bold;
    width: 40%;
}
.docs-links {
    display: flex;
    flex-direction: column;
    gap: 10px;
}
.doc-link {
    display: flex;
    align-items: center;
    gap: 8px;
    text-decoration: none;
}
.doc-link:hover {
    text-decoration: underline;
}
</style>