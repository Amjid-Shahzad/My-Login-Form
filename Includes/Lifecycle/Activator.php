<?php
namespace MyLoginForm\Lifecycle;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Activator {


    private static $instance = null;

    private function __construct() {}
    
    /**
     * Activate the plugin
     */
    public static function activate() {
        // Set default options
        self::set_default_options();
        
        // Schedule cron events
        self::schedule_events();
        
        // Set activation timestamp
        update_option('my_login_form_activated', current_time('mysql'));
        
        // Clear any cached data
        flush_rewrite_rules();
    }
    
    /**
     * Set default options
     */
    private static function set_default_options() {
        $default_options = array(
            'my_login_form_db_version' => '1.0.0',
            'my_login_form_version' => MY_LOGIN_FORM_VERSION,
            'my_login_form_installed' => current_time('mysql'),
            'my_login_form_default_form_style' => 'default',
            'my_login_form_enable_custom_styles' => 1,
            'my_login_form_allow_registration' => get_option('users_can_register', 0),
            'my_login_form_require_email_confirmation' => 0,
            'my_login_form_enable_google_login' => 0,
            'my_login_form_enable_facebook_login' => 0,
            'my_login_form_recaptcha_enabled' => 0,
            'my_login_form_recaptcha_site_key' => '',
            'my_login_form_recaptcha_secret_key' => '',
            'my_login_form_firebase_api_key' => '',
            'my_login_form_firebase_auth_domain' => '',
            'my_login_form_firebase_project_id' => '',
            'my_login_form_firebase_storage_bucket' => '',
            'my_login_form_firebase_messaging_sender_id' => '',
            'my_login_form_firebase_app_id' => '',
        );
        
        foreach ($default_options as $option => $value) {
            if (get_option($option) === false) {
                add_option($option, $value);
            }
        }
    }
    
    /**
     * Schedule cron events
     */
    private static function schedule_events() {
        // Schedule daily maintenance
        if (!wp_next_scheduled('my_login_form_daily_maintenance')) {
            wp_schedule_event(time(), 'daily', 'my_login_form_daily_maintenance');
        }
        
        // Schedule cleanup for old login attempts (weekly)
        if (!wp_next_scheduled('my_login_form_cleanup')) {
            wp_schedule_event(time(), 'weekly', 'my_login_form_cleanup');
        }
    }
    
    /**
     * Add custom cron schedule intervals
     */
    public static function add_cron_schedules($schedules) {
        $schedules['weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Once Weekly', 'my-login-form')
        );
        
        $schedules['monthly'] = array(
            'interval' => MONTH_IN_SECONDS,
            'display' => __('Once Monthly', 'my-login-form')
        );
        
        return $schedules;
    }
}

// Add custom cron schedules
add_filter('cron_schedules', array('MyLoginForm\\Lifecycle\\Activator', 'add_cron_schedules'));