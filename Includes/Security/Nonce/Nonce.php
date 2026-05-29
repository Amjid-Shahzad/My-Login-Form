<?php
/**
 * Main Nonce Loader
 *
 * @package MyLoginForm\Ajax\Nonce
 */

namespace MyLoginForm\Ajax\Nonce;

defined('ABSPATH') || exit;

class Nonce {

     /**
     * Instance of this class
     *
     * @var Nonce
     */

    private static $instance = null;

    private function __construct() {

    }


    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Load all Nonce handlers
     * @return void
     */

    private function load_nonce(): void {

        // Only load classes that actually exist
        if (class_exists('MyLoginForm\Security\Nonce\AdminNonce')) {
            AdminNonce::get_instance();
        }

        // Only load classes that actually exist
        if (class_exists('MyLoginForm\Security\Nonce\UserNonce')) {
            UserNonce::get_instance();
        }



    }


}