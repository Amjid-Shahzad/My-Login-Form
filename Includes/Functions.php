<?php

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Get plugin option
 */
function my_login_form_get_option($key, $default = '') {
    $options = get_option(MY_LOGIN_FORM_OPTION_SETTINGS, []);
    return $options[$key] ?? $default;
}

/**
 * Update plugin option
 */
function my_login_form_update_option($key, $value) {
    $options = get_option(MY_LOGIN_FORM_OPTION_SETTINGS, []);
    $options[$key] = $value;
    return update_option(MY_LOGIN_FORM_OPTION_SETTINGS, $options);
}

/**
 * Check if feature is enabled
 */
function my_login_form_is_feature_enabled($feature) {
    $enabled_features = my_login_form_get_option('enabled_features', []);
    return in_array($feature, (array)$enabled_features);
}

/**
 * Get template file path
 */
function my_login_form_locate_template($template_name) {
    // Check theme directory first
    $template = locate_template(['my-login-form/' . $template_name . '.php']);
    
    // Fallback to plugin templates
    if (!$template) {
        $template = MY_LOGIN_FORM_TEMPLATES_DIR . $template_name . '.php';
    }
    
    return apply_filters('my_login_form_template_path', $template, $template_name);
}

/**
 * Load template
 */
function my_login_form_get_template($template_name, $args = []) {
    if (!empty($args) && is_array($args)) {
        extract($args);
    }
    
    $template_file = my_login_form_locate_template($template_name);
    
    if (file_exists($template_file)) {
        include $template_file;
    }
}

/**
 * Log debug message
 */
function my_login_form_log($message, $type = 'debug') {
    if (!MY_LOGIN_FORM_DEBUG) {
        return;
    }
    
    if (is_array($message) || is_object($message)) {
        $message = print_r($message, true);
    }
    
    $log_file = MY_LOGIN_FORM_PLUGIN_DIR . 'debug.log';
    $message = '[' . current_time('mysql') . '] [' . strtoupper($type) . '] ' . $message . PHP_EOL;
    
    @file_put_contents($log_file, $message, FILE_APPEND);
}

/**
 * Check if WooCommerce is active
 */
function my_login_form_is_woocommerce_active() {
    return class_exists('WooCommerce');
}