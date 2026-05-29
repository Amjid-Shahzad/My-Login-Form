<?php
/**
 * Main AJAX Loader
 *
 * @package MyLoginForm\Ajax
 */

namespace MyLoginForm\Ajax;

defined('ABSPATH') || exit;

class AuthAjax {

     /**
     * Instance of this class
     *
     * @var AuthAjax
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return AuthAjax
     */
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}