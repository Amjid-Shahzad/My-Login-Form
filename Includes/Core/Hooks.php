<?php
namespace MyLoginForm\Core;

// Prevent Direct Access
defined('ABSPATH') || exit;

class Hooks {

    /**
     * Instance of this class
     *
     * @var Hooks|null
     */
    private static ?self $instance = null;

    private array $actions = [];
    private array $filters = [];
    private array $shortcodes = [];
    private array $ajax_handlers = [];
    private array $rest_routes = [];

    /**
     * @var bool
     */
    private bool $registered = false;

    private function __construct() {
        // Initialize hooks in constructor
        $this->init_asset_hooks();
    }

    /**
     * Initialize asset hooks separately
     */
    private function init_asset_hooks(): void {
        // Admin assets - must be added directly, not through the hook system
        add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets'], 10, 1);
        add_action('wp_enqueue_scripts', [$this, 'my_login_form_enqueue_assets'], 10);
        add_action('wp_ajax_my_login_form_clear_cache', [$this, 'mlf_clear_cache']);
        add_action('wp_ajax_nopriv_my_login_form_clear_cache', [$this, 'mlf_clear_cache']);
    }

    /**
     * Get singleton instance
     *
     * @return Hooks
     */
    public static function get_instance(): self {
        if (!self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    public function init(): void {
        // Register hooks (this will register actions/filters added via add_action/add_filter)
        $this->register_hooks();
        
        // Register REST routes
        add_action('rest_api_init', [$this, 'register_rest_routes']);
    }

    private function register_hooks(): void {
        if ($this->registered) {
            return;
        }

        $this->register_actions();
        $this->register_filters();
        $this->register_shortcodes();
        $this->register_ajax_handlers();

        $this->registered = true;
    }

    /* ------------------------------------------------------------------
     * Add Methods
     * ------------------------------------------------------------------ */

    public function add_action(string $hook, $callback, int $priority = 10, int $accepted_args = 1): self {
        $this->actions[] = compact('hook','callback','priority','accepted_args');
        return $this;
    }

    public function add_filter(string $hook, $callback, int $priority = 10, int $accepted_args = 1): self {
        $this->filters[] = compact('hook','callback','priority','accepted_args');
        return $this;
    }

    public function add_shortcode(string $tag, $callback): self {
        $this->shortcodes[$tag] = $callback;
        return $this;
    }

    public function add_ajax_handler(string $action, $callback, bool $nopriv = false): self {
        $this->ajax_handlers[] = compact('action','callback','nopriv');
        return $this;
    }

    public function add_rest_route(string $namespace, string $route, array $args): self {
        $this->rest_routes[] = compact('namespace','route','args');
        return $this;
    }

    /**
     * Register Internals
     */
    private function register_actions(): void {
        foreach ($this->actions as $action) {
            if ($cb = $this->resolve_callback($action['callback'])) {
                add_action($action['hook'], $cb, $action['priority'], $action['accepted_args']);
            }
        }
    }

    private function register_filters(): void {
        foreach ($this->filters as $filter) {
            if ($cb = $this->resolve_callback($filter['callback'])) {
                add_filter($filter['hook'], $cb, $filter['priority'], $filter['accepted_args']);
            }
        }
    }

    private function register_shortcodes(): void {
        foreach ($this->shortcodes as $tag => $callback) {
            if ($cb = $this->resolve_callback($callback)) {
                add_shortcode($tag, $cb);
            }
        }
    }

    private function register_ajax_handlers(): void {
        foreach ($this->ajax_handlers as $handler) {
            if (!$cb = $this->resolve_callback($handler['callback'])) {
                continue;
            }

            add_action('wp_ajax_' . $handler['action'], $cb);

            if ($handler['nopriv']) {
                add_action('wp_ajax_nopriv_' . $handler['action'], $cb);
            }
        }
    }

    public function register_rest_routes(): void {
        foreach ($this->rest_routes as $route) {
            if (empty($route['namespace']) || empty($route['route']) || empty($route['args']['callback'])) {
                continue;
            }

            register_rest_route(
                $route['namespace'],
                $route['route'],
                $route['args']
            );
        }
    }

    /* ------------------------------------------------------------------
     * Callback Resolver (Singleton Safe)
     * ------------------------------------------------------------------ */

    private function resolve_callback($callback): ?callable {
        if (is_callable($callback)) {
            return $callback;
        }

        if (is_array($callback) && count($callback) === 2) {
            [$class, $method] = $callback;

            if (is_object($class) && method_exists($class, $method)) {
                return [$class, $method];
            }

            if (is_string($class) && class_exists($class)) {
                // Try singleton first
                if (method_exists($class, 'get_instance')) {
                    $instance = $class::get_instance();
                    return method_exists($instance, $method) ? [$instance, $method] : null;
                }
                
                // Try static method
                if (method_exists($class, $method)) {
                    return [$class, $method];
                }
            }
        }

        $this->log_error('Invalid callback: ' . print_r($callback, true));
        return null;
    }

    /* ------------------------------------------------------------------
     * Utility Methods
     * ------------------------------------------------------------------ */

    public function do_action(string $hook, ...$args): void {
        do_action('my_login_form_' . $hook, ...$args);
    }

    public function apply_filters(string $hook, $value, ...$args) {
        return apply_filters('my_login_form_' . $hook, $value, ...$args);
    }

    public function get_registered_hooks(): array {
        return [
            'actions' => $this->actions,
            'filters' => $this->filters,
            'shortcodes' => array_keys($this->shortcodes),
            'ajax' => array_column($this->ajax_handlers, 'action'),
            'rest_routes' => $this->rest_routes
        ];
    }

    private function log_error(string $message): void {
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('[My Login Form Hooks] ' . $message);
        }
    }

    // ------------------------------------------------------------------
    // Clear cache AJAX handler
    // ------------------------------------------------------------------ 
    public function my_login_clear_cache(): void {
        // Verify nonce
        if (!check_ajax_referer('my_login_clear_cache', 'nonce', false)) {
            wp_send_json_error(['message' => 'Invalid nonce']);
            return;
        }

        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(['message' => 'Insufficient permissions']);
            return;
        }

        // Clear transients
        global $wpdb;
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_my_login_form_%'");
        $wpdb->query("DELETE FROM {$wpdb->options} WHERE option_name LIKE '_transient_timeout_my_login_form_%'");

        // Clear cache directory if exists
        $cache_dir = WP_CONTENT_DIR . '/cache/my-login-form/';
        if (file_exists($cache_dir)) {
            array_map('unlink', glob($cache_dir . '*'));
            rmdir($cache_dir);
        }

        wp_send_json_success(['message' => 'Cache cleared successfully']);
    }

    // ------------------------------------------------------------------
    // Enqueue admin assets and localize scripts
    // ------------------------------------------------------------------ 

public function enqueue_admin_assets($hook): void {
    // Debug: Log the current hook
    if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
        error_log('Current admin hook: ' . $hook);
    }
    
    // Get current page from query string
    $current_page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    
    // Check if this is one of our pages
    if (empty($current_page) || strpos($current_page, 'my-login-form') === false) {
        return;
    }
    
    // Map page slugs to identifiers
    $page_map = [
        'my-login-form-dashboard'   => 'dashboard',
        'my-login-form-designer'    => 'designer',
        'my-login-form-users-data'  => 'users', 
        'my-login-form-supabase'    => 'social',
        'my-login-form-settings'    => 'settings',
    ];
    
    // Get page identifier (default to dashboard)
    $page_id = isset($page_map[$current_page]) ? $page_map[$current_page] : 'dashboard';
    
    // Define paths
    $css_dir = MY_LOGIN_FORM_DIR . 'Admin/Pages/css/';
    $js_dir = MY_LOGIN_FORM_DIR . 'Admin/Pages/js/';
    
    // Try page-specific files first
    $css_file = $css_dir . $page_id . '.css';
    $js_file = $js_dir . $page_id . '.js';
    
    // For users page, also try 'users-data.css' if 'users.css' doesn't exist
    if ($page_id === 'users' && !file_exists($css_file)) {
        $alt_css = $css_dir . 'users-data.css';
        if (file_exists($alt_css)) {
            $css_file = $alt_css;
        }
    }
    
    if ($page_id === 'users' && !file_exists($js_file)) {
        $alt_js = $js_dir . 'users-data.js';
        if (file_exists($alt_js)) {
            $js_file = $alt_js;
        }
    }
    
    // Enqueue CSS if file exists
    if (file_exists($css_file)) {
        wp_enqueue_style(
            'my-login-form-' . $page_id,
            MY_LOGIN_FORM_URL . 'Admin/Pages/css/' . basename($css_file),
            [],
            filemtime($css_file)
        );
        
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('CSS enqueued for page: ' . $page_id . ' from: ' . basename($css_file));
        }
    } else if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
        error_log('CSS file not found for page ' . $page_id . ': ' . $css_file);
    }
    
    // Enqueue JS if file exists
    if (file_exists($js_file)) {
        wp_enqueue_script(
            'my-login-form-' . $page_id,
            MY_LOGIN_FORM_URL . 'Admin/Pages/js/' . basename($js_file),
            ['jquery'],
            filemtime($js_file),
            true
        );
        
        
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            error_log('JS enqueued for page: ' . $page_id . ' from: ' . basename($js_file));
        }
    } else if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
        error_log('JS file not found for page ' . $page_id . ': ' . $js_file);
    }
}

    // ------------------------------------------------------------------
    // Enqueue form assets on frontend from public/Forms folder
    // ------------------------------------------------------------------ 
    public function my_login_form_enqueue_assets(): void {
        global $wpdb;
        
        $table = $wpdb->prefix . 'my_login_forms';
        
        // Check if table exists
        if ($wpdb->get_var("SHOW TABLES LIKE '$table'") != $table) {
            return;
        }
        
        // Get all active forms
        $forms = $wpdb->get_results("SELECT id FROM {$table} WHERE status = 'active'");
        
        if (!$forms) {
            return;
        }
        
        $plugin_url = MY_LOGIN_FORM_URL;
        $plugin_dir = MY_LOGIN_FORM_DIR;
        
        foreach ($forms as $form) {
            // CSS file path
            $css_file = $plugin_dir . 'Public/Forms/css/' . $form->id . '.css';
            $css_url = $plugin_url . 'Public/Forms/css/' . $form->id . '.css';
            
            // Also check without subdirectory
            if (!file_exists($css_file)) {
                $css_file = $plugin_dir . 'Public/Forms/' . $form->id . '.css';
                $css_url = $plugin_url . 'Public/Forms/' . $form->id . '.css';
            }
            
            if (file_exists($css_file)) {
                wp_enqueue_style(
                    'my-login-form-css-' . $form->id,
                    $css_url,
                    [],
                    filemtime($css_file)
                );
            }
            
            // JS file path
            $js_file = $plugin_dir . 'Public/Forms/js/' . $form->id . '.js';
            $js_url = $plugin_url . 'Public/Forms/js/' . $form->id . '.js';
            
            // Also check without subdirectory
            if (!file_exists($js_file)) {
                $js_file = $plugin_dir . 'Public/Forms/' . $form->id . '.js';
                $js_url = $plugin_url . 'Public/Forms/' . $form->id . '.js';
            }
            
            if (file_exists($js_file)) {
                wp_enqueue_script(
                    'my-login-form-js-' . $form->id,
                    $js_url,
                    ['jquery'],
                    filemtime($js_file),
                    true
                );
            }
        }
        
        // Localize script for AJAX on frontend
        wp_localize_script('jquery', 'my_login_form_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('my_login_form_nonce')
        ]);
    }
}