<?php

/**
  * Plugin Name: My Login Form
  * Plugin URI: https://example.com/my-login-form
  * Description: Advanced login/registration with Firebase, social login, WooCommerce integration, and form builder.
  * Version: 1.0.0
  * Requires at least: 5.6
  * Requires PHP: 7.4
  * Author: Amjad Shahzad
  * License: GPL v2 or later
  * Text Domain: my-login-form
  * Domain Path: /languages
  * WC requires at least: 5.0
  * WC tested up to: 8.0
  */

 // Prevent Direct Access
 defined('ABSPATH') || exit;

 // Define Only Essential Constants
 define('MY_LOGIN_FORM_VERSION', '1.0.0');
 define('MY_LOGIN_FORM_FILE', __FILE__);
 define('MY_LOGIN_FORM_DIR', plugin_dir_path(__FILE__));
 define('MY_LOGIN_FORM_URL', plugin_dir_url(__FILE__));
 define('MY_LOGIN_FORM_BASENAME', plugin_basename(__FILE__));
 define('MY_LOGIN_FORM_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

 // Define Minimum Requirements
 define('MY_LOGIN_FORM_MIN_PHP', '7.4');
 define('MY_LOGIN_FORM_MIN_WP', '5.6');

 include MY_LOGIN_FORM_DIR . 'Includes/Core/Core.php';
 
 // Include the public forms renderer
 require_once MY_LOGIN_FORM_DIR . 'Public/Forms/index.php';
 
 // In my-login-form.php, make sure you have:
 require_once MY_LOGIN_FORM_DIR . 'Includes/Shortcodes/FormsShortcodes.php';

