<?php
/**
 * My Login Form - Core Class
 *
 * @package MyLoginForm\Core
 */

namespace MyLoginForm\Core;

// Prevent Direct Access
defined('ABSPATH') || exit;

/**
 * Main plugin core class
 * 
 * This is the main entry point for the plugin following the singleton pattern.
 * It handles initialization, requirements checking, and provides access to modules.
 */
class Core {
    
    /**
     * Instance of this class
     *
     * @var Core|null
     */
    private static $instance = null;
    
    /**
     * Plugin loader instance
     *
     * @var Loader|null
     */
    private $loader = null;
    
    /**
     * Flag to prevent double initialization
     *
     * @var bool
     */
    private $initialized = false;
    
    /**
     * Constructor (private for singleton)
     */
    private function __construct() {

        // Check requirements before anything else
        if (!$this->check_requirements()) {
            return;
        }
        
        // Register activation/deactivation hooks
        $this->register_plugin_hooks();
    }
    
    /**
     * Prevent cloning
     */
    private function __clone() {}
    
    /**
     * Prevent unserializing
     */
    public function __wakeup() {}
    
    /**
     * Get singleton instance
     *
     * @return Core
     */
    public static function get_instance() {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Initialize the plugin
     *
     * @return void
     */
    public function init() {

        spl_autoload_register(function ($class) {

            $prefix = 'MyLoginForm\\';
            $base_dir = MY_LOGIN_FORM_DIR;

            // Only load our namespace
            $len = strlen($prefix);
            if (strncmp($prefix, $class, $len) !== 0) {
                return;
            }

            // Get relative class
            $relative_class = substr($class, $len);

            // Namespace mappings based on your actual structure
            $namespace_dirs = [
                'Admin\\'        => 'Admin/',
                'Ajax\\'         => 'Includes/Ajax/',
                'Core\\'         => 'Includes/Core/',
                'Database\\'     => 'Includes/Database/',
                'Emails\\'       => 'Includes/Emails/',
                'Integrations\\' => 'Includes/Integrations/',
                'Lifecycle\\'    => 'Includes/Lifecycle/',
                'Security\\'     => 'Includes/Security/',
                'Shortcodes\\'   => 'Includes/Shortcodes/',
                'Users\\'        => 'Includes/Users/',
                'Public\\'       => 'Public/',
            ];

            // Try mapped namespaces
            foreach ($namespace_dirs as $namespace => $dir) {
                if (strpos($relative_class, $namespace) === 0) {
                    $filename = $base_dir . $dir . str_replace('\\', '/', substr($relative_class, strlen($namespace))) . '.php';
                    if (file_exists($filename)) {
                        require_once $filename;
                        return true;
                    }
                }
            }

            // Handle nested namespace structures
            $subdir_patterns = [
                'Security\\Nonce\\' => 'Includes/Security/Nonce/',
                'Security\\Permissions\\' => 'Includes/Security/Permissions/',
                'Security\\Sanitizer\\' => 'Includes/Security/Sanitizer/',
            ];

            foreach ($subdir_patterns as $namespace => $dir) {
                if (strpos($relative_class, $namespace) === 0) {
                    $filename = $base_dir . $dir . str_replace('\\', '/', substr($relative_class, strlen($namespace))) . '.php';
                    if (file_exists($filename)) {
                        require_once $filename;
                        return true;
                    }
                }
            }

            // Handle direct Includes files
            $direct_includes = ['Constants', 'Functions', 'Helpers'];
            foreach ($direct_includes as $file) {
                if ($relative_class === $file) {
                    $filename = $base_dir . 'Includes/' . $file . '.php';
                    if (file_exists($filename)) {
                        require_once $filename;
                        return true;
                    }
                }
            }

            // Default fallback
            $file_path = str_replace('\\', '/', $relative_class);

            // Try Includes/ first
            $default_file = $base_dir . 'Includes/' . $file_path . '.php';
            if (file_exists($default_file)) {
                require_once $default_file;
                return true;
            }

            // Try root directory
            $root_file = $base_dir . $file_path . '.php';
            if (file_exists($root_file)) {
                require_once $root_file;
                return true;
            }

            // Debug
            if (defined('WP_DEBUG') && WP_DEBUG) {
                error_log("MyLoginForm Autoload FAILED: {$class}");
            }
    
         return false;
        });
    
        // Prevent double initialization
        if ($this->initialized) {
            return;
        }
        
        // Check if requirements failed in constructor
        if (!$this->check_requirements()) {
            return;
        }
        
        // Initialize loader
        $this->loader = Loader::get_instance();
        $this->loader->init();
        
        $this->initialized = true;
        
        // Fire initialization complete action
        do_action('my_login_form_initialized', $this);
    }
    
    /**
     * Check system requirements
     *
     * @return bool
     */
    private function check_requirements() {

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            add_action('admin_notices', [$this, 'php_version_notice']);
            return false;
        }
        
        // Check WordPress version
        global $wp_version;
        if (version_compare($wp_version, '5.6', '<')) {
            add_action('admin_notices', [$this, 'wp_version_notice']);
            return false;
        }
        
        return true;
    }
    
    /**
     * Register plugin activation/deactivation hooks
     */
    
    private function register_plugin_hooks() {
        // Only register if plugin file constant is defined
        if (!defined('MY_LOGIN_FORM_FILE')) {
            // Try to determine the plugin file
            $plugin_file = MY_LOGIN_FORM_DIR . 'my-login-form.php';
        } else {
            $plugin_file = MY_LOGIN_FORM_FILE;
        }
        
        register_activation_hook($plugin_file, [Loader::class, 'activate']);
        register_deactivation_hook($plugin_file, [Loader::class, 'deactivate']);
        register_uninstall_hook($plugin_file, [Loader::class, 'uninstall']);
    }
    
    /**
     * PHP version notice
     */
    public function php_version_notice() {
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                printf(
                    esc_html__('My Login Form requires PHP version 7.4 or higher. Your current version is %s. Please upgrade.', 'my-login-form'),
                    esc_html(PHP_VERSION)
                ); 
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * WordPress version notice
     */
    public function wp_version_notice() {
        global $wp_version;
        ?>
        <div class="notice notice-error">
            <p>
                <?php 
                printf(
                    esc_html__('My Login Form requires WordPress version 5.6 or higher. Your current version is %s. Please upgrade.', 'my-login-form'),
                    esc_html($wp_version)
                ); 
                ?>
            </p>
        </div>
        <?php
    }
    
    /**
     * Get a module instance
     *
     * @param string $module Module name
     * @return mixed|null
     */
    public function get_module($module) {
        if (!$this->loader) {
            return null;
        }
        return $this->loader->get_module($module);
    }
    
    /**
     * Get all modules
     *
     * @return array
     */
    public function get_modules() {
        if (!$this->loader) {
            return [];
        }
        return $this->loader->get_loaded_modules();
    }
    
    /**
     * Get loader instance
     *
     * @return Loader|null
     */
    public function get_loader() {
        return $this->loader;
    }
    
    /**
     * Check if a module is loaded
     *
     * @param string $module
     * @return bool
     */
    public function is_module_loaded($module) {
        if (!$this->loader) {
            return false;
        }
        return $this->loader->is_loaded($module);
    }
    
    /**
     * Get service instance
     *
     * @param string $service
     * @return mixed|null
     */
    public function get_service($service) {
        if (!$this->loader) {
            return null;
        }
        return $this->loader->get_service($service);
    }
    
    /**
     * Get template path
     *
     * @param string $template Template name
     * @param string $type Template type (forms, pages, partials, fields)
     * @return string
     */
    public static function get_template($template, $type = 'forms') {
        $template_path = MY_LOGIN_FORM_DIR . 'templates/' . $type . '/' . $template;
        
        if (!file_exists($template_path)) {
            // Try default form template
            if ($type === 'forms') {
                $template_path = MY_LOGIN_FORM_DIR . 'templates/forms/default.php';
            } else {
                // Check if template exists in theme
                $theme_template = get_stylesheet_directory() . '/my-login-form/' . $type . '/' . $template;
                if (file_exists($theme_template)) {
                    return $theme_template;
                }
            }
            
            if (!file_exists($template_path)) {
                return '';
            }
        }
        
        return $template_path;
    }
    
    /**
     * Render template
     *
     * @param string $template Template name
     * @param array $args Template arguments
     * @param string $type Template type
     * @return void
     */
    public static function render_template($template, $args = array(), $type = 'forms') {
        $template_path = self::get_template($template, $type);
        
        if (!empty($template_path) && file_exists($template_path)) {
            if (!empty($args) && is_array($args)) {
                extract($args); // phpcs:ignore WordPress.PHP.DontExtract.extract_extract
            }
            
            include $template_path;
        }
    }
    
    /**
     * Get asset URL
     *
     * @param string $asset Asset path
     * @return string
     */
    public static function get_asset_url($asset) {
        return MY_LOGIN_FORM_URL . 'assets/' . ltrim($asset, '/');
    }
    
    /**
     * Get plugin version
     *
     * @return string
     */
    public static function get_version() {
        if (defined('MY_LOGIN_FORM_VERSION')) {
            return MY_LOGIN_FORM_VERSION;
        }
        return '1.0.0';
    }
    
    /**
     * Log debug message
     *
     * @param string $message
     * @param string $level (debug, error, info)
     */
    public static function log($message, $level = 'debug') {
        if (defined('MY_LOGIN_FORM_DEBUG') && MY_LOGIN_FORM_DEBUG) {
            $prefix = '[My Login Form ' . strtoupper($level) . '] ';
            error_log($prefix . $message);
        }
    }

    /**
     * Add action links on plugins page
     */
    public function add_action_links($links) {
        $plugin_links = [
            '<a href="' . admin_url('admin.php?page=my-login-form-designer') . '">' . __('Form Designer', 'my-login-form') . '</a>',
            '<a href="' . admin_url('admin.php?page=my-login-form-users-data') . '">' . __('Users', 'my-login-form') . '</a>',
            '<a href="' . admin_url('admin.php?page=my-login-form-settings') . '">' . __('Settings', 'my-login-form') . '</a>',
        ];
        
        return array_merge($plugin_links, $links);
    }
}
    
    add_action('plugins_loaded', function () {
    
    \MyLoginForm\Core\Core::get_instance()->init();
    
    });