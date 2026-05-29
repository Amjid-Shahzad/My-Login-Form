<?php
/**
 * Users Nonce Loader
 *
 * @package MyLoginForm\Ajax\Nonce
 */

namespace MyLoginForm\Ajax\Nonce;

defined('ABSPATH') || exit;

class UsersNonce {

     /**
     * Instance of this class
     *
     * @var UsersNonce
     */

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}