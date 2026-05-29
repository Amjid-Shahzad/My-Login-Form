<?php
/**
 * Form Shortcode Handler - Fixed to read saved fields from database
 *
 * @package MyLoginForm\Shortcodes
 */

namespace MyLoginForm\Shortcodes;

// Prevent Direct Access
defined('ABSPATH') || exit;

class FormsShortcodes {
    
    private static $instance = null;
    
    private function __construct() {
        $this->init();
    }
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        add_shortcode('my_login_form', array($this, 'render_form'));
    }
    
    public function render_form($atts) {
        // Parse shortcode attributes
        $atts = shortcode_atts(array(
            'id' => 0,
            'key' => '',
            'type' => 'login',
        ), $atts, 'my_login_form');
        
        // Get form by ID or key from database directly
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        
        if (!empty($atts['id'])) {
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d AND status = 'active'", intval($atts['id'])));
        } elseif (!empty($atts['key'])) {
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE form_key = %s AND status = 'active'", sanitize_text_field($atts['key'])));
        } else {
            // Get default form by type
            $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE form_type = %s AND status = 'active' LIMIT 1", sanitize_text_field($atts['type'])));
        }
        
        if (!$form) {
            return '<div class="my-login-form-error">' . __('Form not found. Please check the shortcode ID or key.', 'my-login-form') . '</div>';
        }
        
        // Start output buffering
        ob_start();
        
        // Render the form
        $this->render_form_html($form);
        
        return ob_get_clean();
    }
    
    /**
     * Render form HTML - Uses saved fields from database
     */
    private function render_form_html($form) {
        // Decode saved fields and settings
        $fields = json_decode($form->fields, true);
        $settings = json_decode($form->settings, true);
        
        // Ensure arrays
        if (!is_array($fields)) {
            $fields = array();
        }
        if (!is_array($settings)) {
            $settings = array();
        }
        
        // Generate unique form ID
        $form_instance_id = 'mlf-form-' . $form->id . '-' . uniqid();
        
        // Enqueue assets
        $this->enqueue_form_assets($form);
        
        // Extract settings with defaults
        $show_labels = isset($settings['show_labels']) ? (bool)$settings['show_labels'] : true;
        $button_text = isset($settings['button_text']) ? $settings['button_text'] : __('Submit', 'my-login-form');
        $button_class = isset($settings['button_class']) ? $settings['button_class'] : 'button button-primary';
        $bg_color = isset($settings['bg_color']) ? $settings['bg_color'] : '#ffffff';
        $text_color = isset($settings['text_color']) ? $settings['text_color'] : '#333333';
        $btn_color = isset($settings['btn_color']) ? $settings['btn_color'] : '#0073aa';
        $border_color = isset($settings['border_color']) ? $settings['border_color'] : '#dddddd';
        $social_providers = isset($settings['social_providers']) ? $settings['social_providers'] : array();
        $show_remember_me = isset($settings['show_remember_me']) ? (bool)$settings['show_remember_me'] : false;
        $enable_password_strength = isset($settings['enable_password_strength']) ? (bool)$settings['enable_password_strength'] : false;
        $redirect_after_login = isset($settings['redirect_after_login']) ? $settings['redirect_after_login'] : '';
        $custom_redirect_url = isset($settings['custom_redirect_url']) ? $settings['custom_redirect_url'] : '';
        
        // Debug output (view page source to see)
        echo '<!-- FORM DEBUG: Form ID: ' . $form->id . ' -->';
        echo '<!-- FORM DEBUG: Form Type: ' . $form->form_type . ' -->';
        echo '<!-- FORM DEBUG: Social Providers: ' . print_r($social_providers, true) . ' -->';
        ?>
        
        <style>
            .my-login-form-<?php echo $form->id; ?> {
                background-color: <?php echo esc_attr($bg_color); ?>;
                color: <?php echo esc_attr($text_color); ?>;
                border: 1px solid <?php echo esc_attr($border_color); ?>;
                padding: 20px;
                border-radius: 8px;
                max-width: 500px;
                margin: 20px auto;
            }
            .my-login-form-<?php echo $form->id; ?> .form-group {
                margin-bottom: 15px;
            }
            .my-login-form-<?php echo $form->id; ?> label {
                display: block;
                margin-bottom: 5px;
                font-weight: 500;
                color: <?php echo esc_attr($text_color); ?>;
            }
            .my-login-form-<?php echo $form->id; ?> .required {
                color: #dc3545;
                margin-left: 4px;
            }
            .my-login-form-<?php echo $form->id; ?> input:not([type="checkbox"]),
            .my-login-form-<?php echo $form->id; ?> select,
            .my-login-form-<?php echo $form->id; ?> textarea {
                width: 100%;
                padding: 8px 12px;
                border: 1px solid <?php echo esc_attr($border_color); ?>;
                border-radius: 4px;
                box-sizing: border-box;
            }
            .my-login-form-<?php echo $form->id; ?> button[type="submit"] {
                background-color: <?php echo esc_attr($btn_color); ?>;
                color: #ffffff;
                padding: 10px 20px;
                border: none;
                border-radius: 4px;
                cursor: pointer;
                width: 100%;
                font-size: 16px;
            }
            .my-login-form-<?php echo $form->id; ?> button[type="submit"]:hover {
                opacity: 0.9;
            }
            .my-login-form-<?php echo $form->id; ?> .checkbox-label {
                display: flex;
                align-items: center;
                gap: 8px;
                cursor: pointer;
            }
            .my-login-form-<?php echo $form->id; ?> .form-footer {
                margin-top: 20px;
                text-align: center;
            }
            .my-login-form-<?php echo $form->id; ?> .form-footer a {
                color: <?php echo esc_attr($btn_color); ?>;
                text-decoration: none;
            }
            .my-login-form-<?php echo $form->id; ?> .message {
                padding: 10px;
                margin-bottom: 15px;
                border-radius: 4px;
            }
            .my-login-form-<?php echo $form->id; ?> .message.error {
                background: #f8d7da;
                color: #721c24;
                border: 1px solid #f5c6cb;
            }
            .my-login-form-<?php echo $form->id; ?> .message.success {
                background: #d4edda;
                color: #155724;
                border: 1px solid #c3e6cb;
            }
            .social-login-section {
                margin-top: 20px;
                padding-top: 20px;
                border-top: 1px solid <?php echo esc_attr($border_color); ?>;
            }
            .social-login-title {
                text-align: center;
                margin-bottom: 15px;
                color: #666;
                font-size: 14px;
            }
            .social-buttons {
                display: flex;
                gap: 10px;
                justify-content: center;
                flex-wrap: wrap;
            }
            .social-button {
                padding: 8px 16px;
                border: none;
                border-radius: 4px;
                color: white;
                cursor: pointer;
                font-size: 14px;
                transition: opacity 0.3s ease;
                display: inline-flex;
                align-items: center;
                gap: 8px;
            }
            .social-button:hover {
                opacity: 0.9;
            }
            <?php if (!empty($form->css_file)): ?>
            /* Custom CSS file loaded separately */
            <?php endif; ?>
        </style>
        
        <div class="my-login-form-container my-login-form-<?php echo $form->id; ?>" data-form-id="<?php echo $form->id; ?>">
            
            <?php if (!empty($form->html_file)): ?>
                <div class="my-login-form-custom-html">
                    <?php 
                    $html_path = MY_LOGIN_FORM_DIR . 'Public/Forms/html/' . $form->html_file;
                    if (file_exists($html_path)) {
                        include $html_path;
                    }
                    ?>
                </div>
            <?php endif; ?>
            
            <div id="form-message-<?php echo $form->id; ?>" class="message" style="display: none;"></div>
            
            <form method="post" class="my-login-form" data-form-id="<?php echo $form->id; ?>">
                <?php wp_nonce_field('my_login_form_submit', 'my_login_form_nonce'); ?>
                <input type="hidden" name="action" value="my_login_form_submit">
                <input type="hidden" name="form_id" value="<?php echo $form->id; ?>">
                <input type="hidden" name="form_type" value="<?php echo esc_attr($form->form_type); ?>">
                <?php if (!empty($redirect_after_login)): ?>
                <input type="hidden" name="redirect_to" value="<?php echo esc_attr($redirect_after_login === 'custom' ? $custom_redirect_url : $redirect_after_login); ?>">
                <?php endif; ?>
                
                <?php if (!empty($fields)): ?>
                    <?php foreach ($fields as $field_name => $field_config): 
                        // Handle field configuration
                        if (is_array($field_config)) {
                            $field_type = isset($field_config['type']) ? $field_config['type'] : 'text';
                            $field_label = isset($field_config['label']) ? $field_config['label'] : ucfirst(str_replace('_', ' ', $field_name));
                            $field_required = isset($field_config['required']) ? (bool)$field_config['required'] : false;
                            $field_placeholder = isset($field_config['placeholder']) ? $field_config['placeholder'] : '';
                            $field_class = isset($field_config['class']) ? $field_config['class'] : '';
                        } else {
                            // Simple string format
                            $field_type = 'text';
                            $field_label = ucfirst(str_replace('_', ' ', $field_name));
                            $field_required = false;
                            $field_placeholder = '';
                            $field_class = '';
                        }
                    ?>
                        <div class="form-group my-login-form-group-<?php echo esc_attr($field_name); ?>">
                            <?php if ($show_labels && $field_type !== 'checkbox'): ?>
                                <label for="field_<?php echo esc_attr($field_name); ?>">
                                    <?php echo esc_html($field_label); ?>
                                    <?php if ($field_required): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                            <?php endif; ?>
                            
                            <?php if ($field_type === 'textarea'): ?>
                                <textarea id="field_<?php echo esc_attr($field_name); ?>"
                                          name="<?php echo esc_attr($field_name); ?>"
                                          class="<?php echo esc_attr($field_class); ?>"
                                          placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                          rows="4"
                                          <?php echo $field_required ? 'required' : ''; ?>></textarea>
                                
                            <?php elseif ($field_type === 'checkbox'): ?>
                                <label class="checkbox-label">
                                    <input type="checkbox"
                                           name="<?php echo esc_attr($field_name); ?>"
                                           class="<?php echo esc_attr($field_class); ?>"
                                           value="1"
                                           <?php echo $field_required ? 'required' : ''; ?>>
                                    <?php echo esc_html($field_label); ?>
                                    <?php if ($field_required): ?>
                                        <span class="required">*</span>
                                    <?php endif; ?>
                                </label>
                                
                            <?php elseif ($field_type === 'select'): ?>
                                <select id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($field_class); ?>"
                                        <?php echo $field_required ? 'required' : ''; ?>>
                                    <option value=""><?php _e('Select', 'my-login-form'); ?></option>
                                    <option value="option1">Option 1</option>
                                    <option value="option2">Option 2</option>
                                </select>
                                
                            <?php elseif ($field_type === 'password'): ?>
                                <input type="password"
                                       id="field_<?php echo esc_attr($field_name); ?>"
                                       name="<?php echo esc_attr($field_name); ?>"
                                       class="<?php echo esc_attr($field_class); ?>"
                                       placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                       <?php echo $field_required ? 'required' : ''; ?>>
                                       
                            <?php else: ?>
                                <input type="<?php echo esc_attr($field_type); ?>"
                                       id="field_<?php echo esc_attr($field_name); ?>"
                                       name="<?php echo esc_attr($field_name); ?>"
                                       class="<?php echo esc_attr($field_class); ?>"
                                       placeholder="<?php echo esc_attr($field_placeholder); ?>"
                                       <?php echo $field_required ? 'required' : ''; ?>>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="form-group">
                        <p style="color: #856404; background-color: #fff3cd; padding: 10px; border-radius: 4px;">
                            <?php _e('No fields configured for this form. Please go to Form Designer, select this form, and add some fields.', 'my-login-form'); ?>
                        </p>
                    </div>
                <?php endif; ?>
                
                <?php if ($show_remember_me && $form->form_type === 'login'): ?>
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="remember_me" value="1">
                        <?php _e('Remember Me', 'my-login-form'); ?>
                    </label>
                </div>
                <?php endif; ?>
                
                <div class="form-group" style="margin-top: 20px;">
                    <button type="submit" class="<?php echo esc_attr($button_class); ?>">
                        <?php echo esc_html($button_text); ?>
                    </button>
                </div>
            </form>
            
            <!-- SOCIAL LOGIN SECTION - Moved outside form but still in container -->
            <?php if (!empty($social_providers)): ?>
            <div class="social-login-section">
                <div class="social-login-title">
                    <?php _e('Or continue with', 'my-login-form'); ?>
                </div>
                <div class="social-buttons">
                    <?php foreach ($social_providers as $provider): 
                        $provider = strtolower(trim($provider));
                    ?>
                        <button type="button" class="social-button social-<?php echo esc_attr($provider); ?>"
                                data-provider="<?php echo esc_attr($provider); ?>"
                                style="background: <?php echo esc_attr($this->get_provider_color($provider)); ?>;">
                            <i class="fab fa-<?php echo esc_attr($this->get_social_icon($provider)); ?>"></i>
                            <?php echo ucfirst($provider); ?>
                        </button>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($form->form_type === 'login'): ?>
            <div class="form-footer" style="margin-top: 20px; text-align: center;">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>">
                    <?php _e('Lost your password?', 'my-login-form'); ?>
                </a>
                <?php if (get_option('users_can_register')): ?>
                    <span style="margin: 0 10px;">|</span>
                    <a href="<?php echo esc_url(wp_registration_url()); ?>">
                        <?php _e('Register', 'my-login-form'); ?>
                    </a>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        
        <?php if (!empty($form->js_file)): ?>
            <!-- Custom JS file loaded separately -->
        <?php endif; ?>
        
        <script>
        jQuery(document).ready(function($) {
            $('.my-login-form').on('submit', function(e) {
                e.preventDefault();
                var $form = $(this);
                var $container = $form.closest('.my-login-form-container');
                var $message = $container.find('.message');
                var formData = $form.serialize();
                
                $message.removeClass('error success').hide();
                
                $.ajax({
                    url: '<?php echo admin_url('admin-ajax.php'); ?>',
                    type: 'POST',
                    data: formData,
                    dataType: 'json',
                    success: function(response) {
                        if (response.success) {
                            $message.addClass('success').html(response.data.message).show();
                            if (response.data.redirect) {
                                setTimeout(function() {
                                    window.location.href = response.data.redirect;
                                }, 2000);
                            } else if (response.data.clear_form) {
                                $form[0].reset();
                            }
                        } else {
                            $message.addClass('error').html(response.data).show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $message.addClass('error').html('An error occurred. Please try again.').show();
                        console.error('AJAX Error:', error);
                    }
                });
            });
            
            $('.social-button').on('click', function() {
                var provider = $(this).data('provider');
                window.location.href = '<?php echo admin_url('admin-ajax.php'); ?>?action=my_login_social_login&provider=' + provider;
            });
        });
        </script>
        
        <?php
    }
    
    /**
     * Enqueue form assets (CSS/JS from Public/Forms folders)
     */
    private function enqueue_form_assets($form) {
        // Enqueue Font Awesome for icons
        wp_enqueue_style('font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css', array(), '6.4.0');
        
        // Enqueue custom CSS file if exists
        if (!empty($form->css_file)) {
            $css_file = MY_LOGIN_FORM_DIR . 'Public/Forms/css/' . $form->css_file;
            if (file_exists($css_file)) {
                wp_enqueue_style(
                    'my-login-form-css-' . $form->id,
                    MY_LOGIN_FORM_URL . 'Public/Forms/css/' . $form->css_file,
                    array(),
                    filemtime($css_file)
                );
            }
        }
        
        // Enqueue custom JS file if exists
        if (!empty($form->js_file)) {
            $js_file = MY_LOGIN_FORM_DIR . 'Public/Forms/js/' . $form->js_file;
            if (file_exists($js_file)) {
                wp_enqueue_script(
                    'my-login-form-js-' . $form->id,
                    MY_LOGIN_FORM_URL . 'Public/Forms/js/' . $form->js_file,
                    array('jquery'),
                    filemtime($js_file),
                    true
                );
            }
        }
        
        wp_localize_script('jquery', 'my_login_form_ajax', array(
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_login_form_ajax'),
        ));
    }
    
    /**
     * Get provider color for social buttons
     */
    private function get_provider_color($provider) {
        $colors = array(
            'google' => '#DB4437',
            'facebook' => '#4267B2',
            'twitter' => '#1DA1F2',
            'github' => '#333333',
            'linkedin' => '#0077B5',
            'instagram' => '#E4405F',
            'microsoft' => '#00A4EF',
            'apple' => '#000000',
            'amazon' => '#FF9900',
        );
        return isset($colors[$provider]) ? $colors[$provider] : '#666666';
    }
    
    /**
     * Get social icon class
     */
    private function get_social_icon($provider) {
        $icons = array(
            'google' => 'google',
            'facebook' => 'facebook-f',
            'twitter' => 'twitter',
            'github' => 'github',
            'linkedin' => 'linkedin-in',
            'instagram' => 'instagram',
            'microsoft' => 'windows',
            'apple' => 'apple',
            'amazon' => 'amazon',
        );
        return isset($icons[$provider]) ? $icons[$provider] : $provider;
    }
}

// Initialize the shortcode
FormsShortcodes::get_instance();