<?php
/**
 * Main Nonce Loader
 *
 * @package MyLoginForm\Ajax\Nonce
 */

namespace MyLoginForm\Ajax\Permissions;

defined('ABSPATH') || exit;

class Permissions {

     /**
     * Instance of this class
     *
     * @var Permissions
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
     * Load all Permissions handlers
     * @return void
     */

    private function load_permissions(): void {

        // Only load classes that actually exist
        if (class_exists('MyLoginForm\Security\Permissions\AdminPermissions')) {
            AdminPermissions::get_instance();
        }

        // Only load classes that actually exist
        if (class_exists('MyLoginForm\Security\Permissions\UserPermissions')) {
            UserPermissions::get_instance();
        }



    }


}