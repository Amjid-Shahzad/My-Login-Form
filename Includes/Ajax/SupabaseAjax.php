<?php
/**
 * My Login Form - AJAX Handler
 *
 * @package MyLoginForm
 */

namespace MyLoginForm\Ajax;

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * AJAX handler class
 */
class SupabaseAjax {

    private static $instance = null;

    /**
     * Constructor
     */
    public function __construct() {
        
        // // Firebase AJAX
        // add_action('wp_ajax_my_login_form_firebase_auth', array($this, 'firebase_auth'));
        // add_action('wp_ajax_nopriv_my_login_form_firebase_auth', array($this, 'firebase_auth'));
        
        // // Social Login AJAX
        // add_action('wp_ajax_my_login_form_social_auth', array($this, 'social_auth'));
        // add_action('wp_ajax_nopriv_my_login_form_social_auth', array($this, 'social_auth'));
        // add_action('wp_ajax_mlf_debug_check', array($this, 'debug_check'));
    }

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}