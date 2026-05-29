<?php

namespace MyLoginForm\Ajax\Nonce;

defined('ABSPATH') || exit;

class AdminNonce {

    /**
     * Singleton instance
     *
     * @var AdminNonce|null
     */
    private static $instance = null;

    /**
     * Nonce action list
     *
     * @var array
     */
    private $admin_nonce = [];

    /**
     * Get instance
     *
     * @return AdminNonce
     */
    public static function get_instance(): self {

        if (null === self::$instance) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    /**
     * Constructor
     */
    private function __construct() {

        $this->initialize_nonces();
    }

    /**
     * Initialize nonce actions
     */
    private function initialize_nonces() {

        $this->admin_nonce = [

            // Form management
            'create_form'    => 'my_login_create_form',
            'delete_form'    => 'my_login_delete_form',
            'duplicate_form' => 'my_login_duplicate_form',
            'get_form'       => 'my_login_get_form',
            'save_form'      => 'my_login_save_form',
            'save_settings'  => 'my_login_save_form_settings',

            // Files
            'delete_css'     => 'my_login_delete_form_css',
            'delete_js'      => 'my_login_delete_form_js',
            'save_css'       => 'my_login_save_form_css',
            'save_js'        => 'my_login_save_form_js',

            // Preview
            'preview_form'   => 'my_login_preview_form',

            // Form submit
            'form_submit'    => 'my_login_form_submit',

            // Cache
            'clear_cache'    => 'my_login_clear_cache',
        ];
    }

    /**
     * Get all JS nonces
     *
     * @return array
     */
    public function get_js_nonces() {

        $nonces = [];

        foreach ($this->admin_nonce as $key => $action) {

            $nonces[$key] = wp_create_nonce($action);
        }

        return $nonces;
    }

    /**
     * Get single nonce
     *
     * @param string $key
     * @return string|null
     */
    public function get_nonce($key) {

        if (!isset($this->admin_nonce[$key])) {
            return null;
        }

        return wp_create_nonce(
            $this->admin_nonce[$key]
        );
    }

    /**
     * Verify nonce
     *
     * @param string $key
     * @param string $nonce
     * @return bool
     */
    public function verify_nonce($key, $nonce) {

        if (!isset($this->admin_nonce[$key])) {
            return false;
        }

        return wp_verify_nonce(
            $nonce,
            $this->admin_nonce[$key]
        );
    }
}