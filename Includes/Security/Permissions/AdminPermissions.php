<?php
/**
 * Admin Permissions Loader
 *
 * @package MyLoginForm\Ajax\Permissions
 */

namespace MyLoginForm\Ajax\Permissions;

defined('ABSPATH') || exit;

class AdminPermissions {

     /**
     * Instance of this class
     *
     * @var AdminPermissions
     */

    private static $instance = null;

    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
}