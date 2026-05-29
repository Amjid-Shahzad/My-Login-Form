<?php
/**
 * My Login Form - Form Database Class
 *
 * @package MyLoginForm\Database
 */

namespace MyLoginForm\Database;

// Prevent Direct Access
defined('ABSPATH') || exit;

class FormsDatabase {

    private $wpdb;
    private $forms_table;
    
    /**
     * Form types
     */
    const FORM_TYPES = [ 'custom', 'login', 'register', 'forgot_password', 'welcome', 'opt_in' ];

    /**
     * Form status
     */
    const FORM_STATUS = [ 'active', 'inactive', 'draft', 'trash' ];

    /**
     * Redirect after login options
     */
    const REDIRECT_OPTIONS = [ 'dashboard', 'profile', 'home', 'custom' ];

    /**
     * Social login providers
     */
    private $social_providers = [ 'google', 'facebook', 'twitter', 'github', 'linkedin' ];
    
    /**
     * Layout options
     */
    private $layout_options = [ 'grid', 'flex' ];

    private static $instance = null;

    private function __construct() {
        global $wpdb;
        $this->wpdb = $wpdb;
        $this->forms_table = $wpdb->prefix . 'my_login_forms';
        
        // Create table immediately on construct
        $this->create_forms_table();
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init() {
        // Check for updates and default forms
        $this->maybe_update_forms_table();
        $this->create_default_forms_if_not_exist();
    }

    public function create_forms_table() {
    $charset_collate = $this->wpdb->get_charset_collate();
    
    $sql = "CREATE TABLE IF NOT EXISTS {$this->forms_table} (
        id INT(11) NOT NULL AUTO_INCREMENT,
        form_key VARCHAR(100) NOT NULL,
        form_type VARCHAR(50) NOT NULL DEFAULT 'custom',
        name VARCHAR(255) NOT NULL,
        description TEXT DEFAULT NULL,
        settings LONGTEXT DEFAULT NULL,
        fields LONGTEXT DEFAULT NULL,
        css_file VARCHAR(255) DEFAULT '',
        js_file VARCHAR(255) DEFAULT '',
        html_file VARCHAR(255) DEFAULT '',
        redirect_url VARCHAR(500) DEFAULT '',
        social_login TINYINT(1) NOT NULL DEFAULT 0,
        social_providers TEXT DEFAULT NULL,
        redirect_after_login VARCHAR(50) DEFAULT 'dashboard',
        redirect_after_logout VARCHAR(50) DEFAULT 'home',
        status VARCHAR(20) DEFAULT 'active',
        is_default TINYINT(1) DEFAULT 0,
        is_system TINYINT(1) DEFAULT 0,
        is_builtin TINYINT(1) DEFAULT 0,
        sort_order INT(11) DEFAULT 0,
        views_count BIGINT(20) DEFAULT 0,
        submissions_count BIGINT(20) DEFAULT 0,
        form_containers LONGTEXT DEFAULT NULL,
        form_layout LONGTEXT DEFAULT NULL,
        form_styles LONGTEXT DEFAULT NULL,
        created_by INT(11) DEFAULT NULL,
        updated_by INT(11) DEFAULT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        UNIQUE KEY unique_form_key (form_key),
        KEY form_type (form_type),
        KEY status (status),
        KEY is_default (is_default),
        KEY sort_order (sort_order)
    ) $charset_collate;";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    // DEBUG: Check if table was created
    $table_check = $this->wpdb->get_var("SHOW TABLES LIKE '{$this->forms_table}'");
    error_log('Table creation check - Table exists: ' . ($table_check ? 'YES' : 'NO'));
    
    return $this->table_exists();
}

    private function create_default_forms_if_not_exist() {
        // Only create if table exists and has no forms
        if (!$this->table_exists()) {
            return;
        }
        
        $count = $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->forms_table}");
        
        if ($count == 0) {
            $this->create_default_forms();
        }
    }

    private function create_default_forms() {
        // Default forms - NOT marked as system (is_system = 0, is_builtin = 0)
        // This makes them fully customizable by users
    
        $default_forms = [
            'login' => [
                'form_key' => 'login',
                'form_type' => 'login',
                'name' => __('Login Form', 'my-login-form'),
                'is_default' => 1,
                'is_system' => 0,
                'is_builtin' => 0,
                'sort_order' => 1,
                'status' => 'active',
                'settings' => wp_json_encode([
                    'layout' => 'flex',
                    'show_labels' => true,
                    'show_remember_me' => true,
                    'show_lost_password' => true,
                    'show_register_link' => true,
                    'button_text' => __('Log In', 'my-login-form'),
                    'button_class' => 'btn btn-primary',
                ]),
                'fields' => wp_json_encode([
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email Address', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your email', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => __('Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your password', 'my-login-form'),
                        'class' => 'form-control'
                    ]
                ]),
            ],
        
            'register' => [
                'form_key' => 'register',
                'form_type' => 'register',
                'name' => __('Registration Form', 'my-login-form'),
                'is_default' => 1,
                'is_system' => 0,
                'is_builtin' => 0,
                'sort_order' => 2,
                'status' => 'active',
                'settings' => wp_json_encode([
                    'layout' => 'flex',
                    'show_labels' => true,
                    'require_email_confirmation' => false,
                    'auto_login_after_register' => true,
                    'enable_password_strength' => true,
                    'button_text' => __('Register', 'my-login-form'),
                    'button_class' => 'btn btn-success',
                ]),
                'fields' => wp_json_encode([   
                    'first_name' => [
                        'type' => 'text',
                        'label' => __('First Name', 'my-login-form'),
                        'required' => false,
                        'placeholder' => __('Enter your first name', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'last_name' => [
                        'type' => 'text',
                        'label' => __('Last Name', 'my-login-form'),
                        'required' => false,
                        'placeholder' => __('Enter your last name', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email Address', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your email', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => __('Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Choose a password', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'confirm_password' => [
                        'type' => 'password',
                        'label' => __('Confirm Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Confirm your password', 'my-login-form'),
                        'class' => 'form-control'
                    ]
                ]),
            ],
            
            'forgot-password' => [
                'form_key' => 'forgot-password',
                'form_type' => 'forgot_password',
                'name' => __('Forgot Password Form', 'my-login-form'),
                'is_default' => 1,
                'is_system' => 0,
                'is_builtin' => 0,
                'sort_order' => 3,
                'status' => 'active',
                'settings' => wp_json_encode([
                    'layout' => 'flex',
                    'show_labels' => true,
                    'reset_method' => 'email',
                    'button_text' => __('Reset Password', 'my-login-form'),
                    'button_class' => 'btn btn-warning',
                    'success_message' => __('Password reset link has been sent to your email.', 'my-login-form'),
                ]),
                'fields' => wp_json_encode([
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email Address', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your email', 'my-login-form'),
                        'class' => 'form-control'
                    ]
                ]),
            ],
            
            'welcome' => [
                'form_key' => 'welcome',
                'form_type' => 'welcome',
                'name' => __('Welcome Form', 'my-login-form'),
                'is_default' => 1,
                'is_system' => 0,
                'is_builtin' => 0,
                'sort_order' => 4,
                'status' => 'active',
                'settings' => wp_json_encode([
                    'layout' => 'flex',
                    'show_logo' => true,
                    'welcome_title' => __('Welcome to Our Website', 'my-login-form'),
                    'welcome_message' => __('Please login or create an account to continue.', 'my-login-form'),
                    'show_login_button' => true,
                    'show_register_button' => true,
                    'login_button_text' => __('Login', 'my-login-form'),
                    'register_button_text' => __('Create Account', 'my-login-form'),
                    'button_class' => 'btn btn-primary',
                ]),
                'fields' => wp_json_encode([
                    'first_name' => [
                        'type' => 'text',
                        'label' => __('First Name', 'my-login-form'),
                        'required' => false,
                        'placeholder' => __('Enter your first name', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'last_name' => [
                        'type' => 'text',
                        'label' => __('Last Name', 'my-login-form'),
                        'required' => false,
                        'placeholder' => __('Enter your last name', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email Address', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your email', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => __('Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Choose a password', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                ]),
            ],
            
            'opt-in' => [
                'form_key' => 'opt-in',
                'form_type' => 'opt-in',
                'name' => __('Opt-in Form', 'my-login-form'),
                'is_default' => 1,
                'is_system' => 0,
                'is_builtin' => 0,
                'sort_order' => 5,
                'status' => 'active',
                'settings' => wp_json_encode([
                    'layout' => 'flex',
                    'popup_trigger' => 'button',
                    'popup_trigger_text' => __('Login', 'my-login-form'),
                    'popup_width' => '400px',
                    'close_on_click_outside' => true,
                    'show_close_button' => true,
                    'show_labels' => true,
                    'show_remember_me' => true,
                    'show_lost_password' => true,
                    'show_register_link' => true,
                    'button_text' => __('Log In', 'my-login-form'),
                    'button_class' => 'btn btn-primary',
                ]),
                'fields' => wp_json_encode([
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email Address', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your email', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => __('Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your password', 'my-login-form'),
                        'class' => 'form-control'
                    ]
                ]),
            ],
        ];
    
        foreach ($default_forms as $form) {
            $existing = $this->get_form_by_key($form['form_key']);
            if (!$existing) {
                $this->wpdb->insert($this->forms_table, $form);
            }
        }
    }

    private function maybe_update_forms_table() {
        $columns = $this->wpdb->get_col("DESC {$this->forms_table}");
        
        // Add form_key if missing
        if (!in_array('form_key', $columns)) {
            $this->wpdb->query("ALTER TABLE {$this->forms_table} ADD form_key VARCHAR(100) NOT NULL AFTER id");
            $this->wpdb->query("ALTER TABLE {$this->forms_table} ADD UNIQUE KEY unique_form_key (form_key)");
        }
        
        // Add html_file if missing
        if (!in_array('html_file', $columns)) {
            $this->wpdb->query("ALTER TABLE {$this->forms_table} ADD html_file VARCHAR(255) DEFAULT '' AFTER js_file");
        }
        
        // Add new columns if missing
        $new_columns = [
            'description' => "ADD description TEXT DEFAULT NULL AFTER name",
            'social_providers' => "ADD social_providers TEXT DEFAULT NULL AFTER social_login",
            'form_containers' => "ADD form_containers LONGTEXT DEFAULT NULL AFTER submissions_count",
            'form_layout' => "ADD form_layout LONGTEXT DEFAULT NULL AFTER form_containers",
            'form_styles' => "ADD form_styles LONGTEXT DEFAULT NULL AFTER form_layout",
        ];
        
        foreach ($new_columns as $column => $sql) {
            if (!in_array($column, $columns)) {
                $this->wpdb->query("ALTER TABLE {$this->forms_table} {$sql}");
            }
        }
    }

    public function get_form($id) {
        if (!$this->table_exists()) {
            return null;
        }
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->forms_table} WHERE id = %d", $id)
        );
    }

    public function get_form_by_key($form_key) {
        if (!$this->table_exists()) {
            return null;
        }
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->forms_table} WHERE form_key = %s", $form_key)
        );
    }

    public function increment_views_count($id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        return $this->wpdb->query(
            $this->wpdb->prepare(
                "UPDATE {$this->forms_table} SET views_count = views_count + 1 WHERE id = %d",
                $id
            )
        );
    }

    public function get_all_forms($args = array()) {
        if (!$this->table_exists()) {
            return array();
        }
        
        $defaults = array(
            'status' => 'active',
            'orderby' => 'sort_order',
            'order' => 'ASC'
        );
        
        $args = wp_parse_args($args, $defaults);
        
        $where = "WHERE status = 'active'";
        
        if (!empty($args['form_type'])) {
            $where .= $this->wpdb->prepare(" AND form_type = %s", $args['form_type']);
        }
        
        $orderby = esc_sql($args['orderby']);
        $order = strtoupper($args['order']) === 'DESC' ? 'DESC' : 'ASC';
        
        $sql = "SELECT * FROM {$this->forms_table} {$where} ORDER BY {$orderby} {$order}";
        
        return $this->wpdb->get_results($sql);
    }

    public function get_default_form($form_type = 'login') {
        if (!$this->table_exists()) {
            return null;
        }
        
        return $this->wpdb->get_row(
            $this->wpdb->prepare(
                "SELECT * FROM {$this->forms_table} 
                WHERE form_type = %s AND status = 'active' 
                ORDER BY is_default DESC, sort_order ASC LIMIT 1",
                $form_type
            )
        );
    }

    public function update_form($id, $data) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $sanitized = array();
        
        if (isset($data['name'])) {
            $sanitized['name'] = sanitize_text_field($data['name']);
        }
        if (isset($data['description'])) {
            $sanitized['description'] = sanitize_textarea_field($data['description']);
        }
        if (isset($data['settings'])) {
            $sanitized['settings'] = is_array($data['settings']) ? wp_json_encode($data['settings']) : $data['settings'];
        }
        if (isset($data['fields'])) {
            $sanitized['fields'] = is_array($data['fields']) ? wp_json_encode($data['fields']) : $data['fields'];
        }
        if (isset($data['html_file'])) {
            $sanitized['html_file'] = sanitize_text_field($data['html_file']);
        }
        if (isset($data['css_file'])) {
            $sanitized['css_file'] = sanitize_text_field($data['css_file']);
        }
        if (isset($data['js_file'])) {
            $sanitized['js_file'] = sanitize_text_field($data['js_file']);
        }
        if (isset($data['social_providers'])) {
            $sanitized['social_providers'] = is_array($data['social_providers']) ? wp_json_encode($data['social_providers']) : $data['social_providers'];
        }
        if (isset($data['form_containers'])) {
            $sanitized['form_containers'] = is_array($data['form_containers']) ? wp_json_encode($data['form_containers']) : $data['form_containers'];
        }
        if (isset($data['form_layout'])) {
            $sanitized['form_layout'] = is_array($data['form_layout']) ? wp_json_encode($data['form_layout']) : $data['form_layout'];
        }
        if (isset($data['form_styles'])) {
            $sanitized['form_styles'] = is_array($data['form_styles']) ? wp_json_encode($data['form_styles']) : $data['form_styles'];
        }
        if (isset($data['status'])) {
            $sanitized['status'] = sanitize_text_field($data['status']);
        }
        if (isset($data['redirect_after_login'])) {
            $sanitized['redirect_after_login'] = sanitize_text_field($data['redirect_after_login']);
        }
        if (isset($data['redirect_after_logout'])) {
            $sanitized['redirect_after_logout'] = sanitize_text_field($data['redirect_after_logout']);
        }
        if (isset($data['sort_order'])) {
            $sanitized['sort_order'] = intval($data['sort_order']);
        }
        
        return $this->wpdb->update(
            $this->forms_table,
            $sanitized,
            array('id' => $id)
        );
    }

    public function delete_form($id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        // Allow deletion of any form (including defaults since they're not system)
        return $this->wpdb->delete($this->forms_table, array('id' => $id));
    }

    public function table_exists() {
        $table = $this->wpdb->get_var(
            $this->wpdb->prepare("SHOW TABLES LIKE %s", $this->forms_table)
        );
        return $table === $this->forms_table;
    }

    public function get_forms_count() {
        if (!$this->table_exists()) {
            return 0;
        }
        return (int) $this->wpdb->get_var("SELECT COUNT(*) FROM {$this->forms_table}");
    }

    public function duplicate_form($id) {
        if (!$this->table_exists()) {
            return false;
        }
        
        $form = $this->wpdb->get_row(
            $this->wpdb->prepare("SELECT * FROM {$this->forms_table} WHERE id = %d", $id),
            ARRAY_A
        );
        
        if (!$form) {
            return false;
        }
        
        unset($form['id']);
        unset($form['created_at']);
        unset($form['updated_at']);
        
        $form['name'] = $form['name'] . ' (Copy)';
        $form['form_key'] = $form['form_key'] . '_copy_' . time();
        $form['is_default'] = 0;
        $form['is_system'] = 0;
        $form['is_builtin'] = 0;
        $form['views_count'] = 0;
        $form['submissions_count'] = 0;
        
        $this->wpdb->insert($this->forms_table, $form);
        return $this->wpdb->insert_id;
    }

    // ========== GETTER METHODS ==========

    /**
     * Get social providers
     */
    public function get_social_providers() {
        return $this->social_providers;
    }

    /**
     * Get layout options
     */
    public function get_layout_options() {
        return $this->layout_options;
    }

    /**
     * Add social provider dynamically
     */
    public function add_social_provider($provider) {
        if (!in_array($provider, $this->social_providers)) {
            $this->social_providers[] = $provider;
        }
        return $this;
    }

    /**
     * Remove social provider
     */
    public function remove_social_provider($provider) {
        $key = array_search($provider, $this->social_providers);
        if ($key !== false) {
            unset($this->social_providers[$key]);
            $this->social_providers = array_values($this->social_providers);
        }
        return $this;
    }

    /**
     * Check if form type is valid
     */
    public function is_valid_form_type($type) {
        return in_array($type, self::FORM_TYPES);
    }

    /**
     * Check if status is valid
     */
    public function is_valid_status($status) {
        return in_array($status, self::FORM_STATUS);
    }

    /**
     * Check if redirect option is valid
     */
    public function is_valid_redirect($redirect) {
        return in_array($redirect, self::REDIRECT_OPTIONS);
    }
}