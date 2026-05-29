<?php
namespace MyLoginForm\Admin;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Menus {
    
    private static $instance = null;

    private function __construct() {}
    
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function init() {
        // Register admin menus
        add_action('admin_menu', [$this, 'register_menus']);
    
    }
    
    public function register_menus() {
        // Main menu page
        add_menu_page(
            __('My Login Form', 'my-login-form'),
            __('My Login Form', 'my-login-form'),
            'manage_options',
            'my-login-form-dashboard',
            [$this, 'render_dashboard'],
            'dashicons-lock',
            30
        );
        
        // Dashboard submenu
        add_submenu_page(
            'my-login-form-dashboard',
            __('Dashboard', 'my-login-form'),
            __('Dashboard', 'my-login-form'),
            'manage_options',
            'my-login-form-dashboard',
            [$this, 'render_dashboard']
        );
        
        // Form Designer
        add_submenu_page(
            'my-login-form-dashboard',
            __('Form Designer', 'my-login-form'),
            __('Form Designer', 'my-login-form'),
            'manage_options',
            'my-login-form-designer',
            [$this, 'render_designer']
        );
        
        // User Data
        add_submenu_page(
            'my-login-form-dashboard',
            __('User Data', 'my-login-form'),
            __('Users Data', 'my-login-form'),
            'manage_options',
            'my-login-form-users-data',
            [$this, 'render_users_data']
        );
        
        // Supabase & Social
        add_submenu_page(
            'my-login-form-dashboard',
            __('Social Login Setup', 'my-login-form'),
            __('Social Login Setup', 'my-login-form'),
            'manage_options',
            'my-login-form-social-supabase',
            [$this, 'render_supabase']
        );
        
        // Settings
        add_submenu_page(
            'my-login-form-dashboard',
            __('Settings', 'my-login-form'),
            __('Settings', 'my-login-form'),
            'manage_options',
            'my-login-form-settings',
            [$this, 'render_settings']
        );
    }
    
    /**
     * Render Dashboard Page
     */
    public function render_dashboard() {
        $dashboard_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/dashboard.php';
        
        if (file_exists($dashboard_file)) {
            // Initialize database globals if needed
            global $my_login_form_forms_db, $my_login_form_users_db;
            
            // Get stats for dashboard
            $form_stats = [];
            $user_stats = [];
            $recent_forms = [];
            $recent_users = [];
            $chart_data = ['labels' => [], 'data' => []];
            $default_forms_status = [];
            
            if (isset($my_login_form_forms_db) && $my_login_form_forms_db) {
                $form_stats = $my_login_form_forms_db->get_form_stats();
                $recent_forms = $my_login_form_forms_db->get_recent_forms_for_dashboard(5);
                $default_forms_status = $my_login_form_forms_db->get_default_forms_status();
            }
            
            if (isset($my_login_form_users_db) && $my_login_form_users_db) {
                $user_stats = $my_login_form_users_db->get_user_stats();
                $recent_users = $my_login_form_users_db->get_recent_users_for_dashboard(5);
                $chart_data = $my_login_form_users_db->get_registration_chart_data(7);
            }
            
            $stats = array_merge($form_stats, $user_stats);
            
            // Include the dashboard template
            include $dashboard_file;
        } else {
            $this->show_error('Dashboard template not found', $dashboard_file);
        }
    }
    
    /**
     * Render Designer Page
     */
    public function render_designer() {
        $designer_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/designer.php';
        
        if (file_exists($designer_file)) {
            include $designer_file;
        } else {
            $this->show_error('Designer template not found', $designer_file);
        }
    }
    
    /**
     * Render Users Data Page
     */
    public function render_users_data() {
        $users_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/users-data.php';
        
        if (file_exists($users_file)) {
            include $users_file;
        } else {
            $this->show_error('Users template not found', $users_file);
        }
    }
    
    /**
     * Render Firebase Page
     */
    public function render_supabase() {
        $firebase_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/supabase.php';
        
        if (file_exists($firebase_file)) {
            include $firebase_file;
        } else {
            echo '<div class="wrap"><div class="notice notice-info"><p>' . __('Supabase integration coming soon!', 'my-login-form') . '</p></div></div>';
        }
    }
    
    /**
     * Render Settings Page
     */
    public function render_settings() {
        $settings_file = MY_LOGIN_FORM_DIR . 'Admin/Pages/settings.php';
        
        if (file_exists($settings_file)) {
            include $settings_file;
        } else {
            $this->show_error('Settings template not found', $settings_file);
        }
    }
    
    
    
    /**
     * Show error message
     */
    private function show_error($message, $file_path = '') {
        echo '<div class="wrap">';
        echo '<div class="notice notice-error">';
        echo '<p><strong>' . __('My Login Form Error:', 'my-login-form') . '</strong> ' . esc_html($message) . '</p>';
        if ($file_path && defined('WP_DEBUG') && WP_DEBUG) {
            echo '<p><code>' . esc_html($file_path) . '</code></p>';
        }
        echo '</div>';
        echo '</div>';
    }

    // In Menus class
    public function add_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=my-login-form-designer') . '">' . __('Form Designer', 'my-login-form') . '</a>',
            '<a href="' . admin_url('admin.php?page=my-login-form-users-data') . '">' . __('Users', 'my-login-form') . '</a>',
            '<a href="' . admin_url('admin.php?page=my-login-form-settings') . '">' . __('Settings', 'my-login-form') . '</a>',
        ];

        return array_merge($plugin_links, $links);
    }
}