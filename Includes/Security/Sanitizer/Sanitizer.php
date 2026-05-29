<?php
/**
 * Main Sanitizer Loader
 *
 * @package MyLoginForm\Security\Sanitizer
 */

namespace MyLoginForm\Security\Sanitizer;

defined('ABSPATH') || exit;

class Sanitizer {

     /**
     * Instance of this class
     *
     * @var Sanitizer
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
     * Load all Sanitizer handlers
     * @return void
     */

    private function load_sanitizer(): void {

        // Only load classes that actually exist
        if (class_exists('MyLoginForm\Security\Sanitizer\AdminSanitizer')) {
            AdminSanitizer::get_instance();
        }

        // Only load classes that actually exist
        if (class_exists('MyLoginForm\Security\Sanitizer\UserSanitizer')) {
            UserSanitizer::get_instance();
        }



    }


}