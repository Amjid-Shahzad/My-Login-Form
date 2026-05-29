<?php
/**
 * Users Permissions Loader
 *
 * @package MyLoginForm\Ajax\Permissions
 */

namespace MyLoginForm\Ajax\Permissions;

defined('ABSPATH') || exit;

class UsersPermissions {

     /**
     * Instance of this class
     *
     * @var UsersPermissions
     */

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}