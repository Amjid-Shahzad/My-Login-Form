<?php
/**
 * User Data AJAX Handler
 *
 * @package MyLoginForm\Ajax
 */
namespace MyLoginForm\Ajax;

defined('ABSPATH') || exit;

class UserdataAjax {

    private static $instance = null;

    private function __construct() {
        add_action('wp_ajax_my_login_form_get_users_data', [$this, 'get_users_data']);
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    
    
    // Your methods here
}