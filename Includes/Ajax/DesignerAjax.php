<?php
/**
 * Dashboard AJAX Handler
 *
 * @package MyLoginForm\Ajax
 */
namespace MyLoginForm\Ajax;

// Prevent direct access
defined('ABSPATH') || exit;

class DesignerAjax {


    /**
     * Test AJAX handler - Add this to your DesignerAjax class
     */
public function test_ajax() {
    error_log('=== TEST AJAX HANDLER CALLED ===');
    wp_send_json_success(array(
        'message' => 'AJAX is working correctly!',
        'timestamp' => current_time('mysql'),
        'received_data' => $_POST
    ));
}

    /**
     * Instance of this class
     *
     * @var DesignerAjax
     */
    private static $instance = null;

    /**
     * Get singleton instance
     *
     * @return DesignerAjax
     */

    private function __construct() {
        add_action('wp_ajax_my_login_test_ajax', [$this, 'test_ajax']);
        
        // Nonce verificatoin
        add_action('wp_ajax_my_login_get_nonce', [$this, 'get_nonce']);

        // Form Designer AJAX
        add_action('wp_ajax_my_login_create_form', [$this, 'create_form']);
        add_action('wp_ajax_my_login_delete_form', [$this, 'delete_form']);
        add_action('wp_ajax_my_login_duplicate_form', [$this, 'duplicate_form']);
        add_action('wp_ajax_my_login_get_form', [$this, 'get_form']);
        add_action('wp_ajax_my_login_save_form', [$this, 'save_form']);
        add_action('wp_ajax_my_login_save_form_settings', [$this, 'save_form_settings']);

        // // HTML file actions
        // add_action('wp_ajax_my_login_create_form_html', array($this, 'create_form_html'));
        add_action('wp_ajax_my_login_save_form_html', [$this, 'save_html']);
        // add_action('wp_ajax_my_login_get_form_html', array($this, 'get_form_html'));
        // add_action('wp_ajax_my_login_delete_form_html', array($this, 'delete_form_html'));

        // // CSS file actions
        // add_action('wp_ajax_my_login_create_form_css', array($this, 'create_form_css'));
        add_action('wp_ajax_my_login_save_form_css', array($this, 'save_css'));
        // add_action('wp_ajax_my_login_get_form_css', array($this, 'get_form_css'));
        // add_action('wp_ajax_my_login_delete_form_css', array($this, 'delete_form_css'));

        // // JS file actions
        // add_action('wp_ajax_my_login_create_form_js', array($this, 'create_form_js'));
        add_action('wp_ajax_my_login_save_form_js', array($this, 'save_js'));
        // add_action('wp_ajax_my_login_get_form_js', array($this, 'get_form_js'));
        // add_action('wp_ajax_my_login_delete_form_js', array($this, 'delete_form_js'));

        // Preview AJAX
        add_action('wp_ajax_my_login_preview_form', [$this, 'preview_form']);
        add_action('wp_ajax_my_login_preview_from_local', [$this, 'preview_from_local']);
        
        // Form Submission AJAX
        add_action('wp_ajax_my_login_form_submit', [$this, 'submit_form']);
        

    }

    
    public static function get_instance(): self {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get nonce dynamically via AJAX - Only for authenticated users
     */
    public function get_nonce() {

        // Verify user is logged in and has proper capabilities
        if (!is_user_logged_in()) {
            wp_send_json_error('User not logged in', 401);
            return;
        }
    
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized', 403);
            return;
        }
    
        // Get the requested nonce type
        $nonce_type = isset($_POST['nonce_type']) ? sanitize_text_field($_POST['nonce_type']) : '';
    
        if (empty($nonce_type)) {
            wp_send_json_error('Nonce type required');
            return;
        }
    
        $nonce_actions = [
            // Existing actions
            'create_form' => 'my_login_create_form',
            'delete_form' => 'my_login_delete_form',
            'duplicate_form' => 'my_login_duplicate_form',
            'get_form' => 'my_login_get_form',
            'preview_form' => 'my_login_preview_form',
            'save_form' => 'my_login_save_form',
            'submit_form' => 'my_login_submit_form',
            'save_settings' => 'my_login_save_form_settings',
            

            // HTML file actions
            'create_html' => 'my_login_create_form_html',
            'delete_html' => 'my_login_delete_form_html',
            'get_html' => 'my_login_get_form_html',
            'save_html' => 'my_login_save_form_html',

            // CSS file actions
            'create_css' => 'my_login_create_form_css',
            'delete_css' => 'my_login_delete_form_css',
            'get_css' => 'my_login_get_form_css',
            'save_css' => 'my_login_save_form_css',


            // JS file actions
            'create_js' => 'my_login_create_form_js',
            'delete_js' => 'my_login_delete_form_js',
            'get_js' => 'my_login_get_form_js',
            'save_js' => 'my_login_save_form_js',
        ];
    
        if (!isset($nonce_actions[$nonce_type])) {
            wp_send_json_error('Invalid nonce type');
            return;
        }
    
        // Create and return the nonce
        $nonce = wp_create_nonce($nonce_actions[$nonce_type]);
    
        wp_send_json_success([
            'nonce' => $nonce,
            'action' => $nonce_actions[$nonce_type]
        ]);
    }

    /**
     * Create form - Updated with proper file creation using form_key
     * 
     * @return void
     */
    public function create_form() {
        check_ajax_referer('my_login_create_form', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        $form_name = sanitize_text_field($_POST['form_name']);
        $form_type = sanitize_text_field($_POST['form_type']);

        if (empty($form_name)) {
            wp_send_json_error(__('Form name is required', 'my-login-form'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';

        // Define directories
        $html_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';
        $css_dir  = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
        $js_dir   = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';

        // Create directories if they don't exist
        if (!file_exists($html_dir)) wp_mkdir_p($html_dir);
        if (!file_exists($css_dir))  wp_mkdir_p($css_dir);
        if (!file_exists($js_dir))   wp_mkdir_p($js_dir);

        // Debug: Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table'");
        if (!$table_exists) {
            wp_send_json_error('Database table does not exist: ' . $table . '. Please deactivate and reactivate the plugin.');
        }

        // Debug: Check if form_key column exists
        $column_exists = $wpdb->get_results("SHOW COLUMNS FROM $table LIKE 'form_key'");
        if (empty($column_exists)) {
            wp_send_json_error('Column "form_key" does not exist in table. Please run database update.');
        }

        // Check if form exists
        $exists = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM $table WHERE name = %s",
            $form_name
        ));

        if ($exists) {
            wp_send_json_error(__('Form name already exists', 'my-login-form'));
        }

        // Prepare default fields based on type
        $default_fields = [];
        $default_settings = [];

        switch ($form_type) {
            case 'login':
                $default_fields = [
                    'username' => [
                        'type' => 'text',
                        'label' => __('Username or Email', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your username or email', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => __('Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your password', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'remember_me' => [
                        'type' => 'checkbox',
                        'label' => __('Remember Me', 'my-login-form'),
                        'required' => false,
                        'class' => 'form-check-input'
                    ]
                ];
                $default_settings = [
                    'redirect_after_login' => 'dashboard',
                    'show_remember_me' => true,
                    'show_lost_password' => true,
                    'show_register_link' => true,
                    'button_text' => __('Log In', 'my-login-form'),
                    'button_class' => 'btn btn-primary',
                ];
                break;
            case 'register':
                $default_fields = [
                    'username' => [
                        'type' => 'text',
                        'label' => __('Username', 'my-login-form'),
                        'required' => false,
                        'placeholder' => __('Choose a username', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email Address', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Enter your email', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'first_name' => [
                        'type' => 'text',
                        'label' => __('First Name', 'my-login-form'),
                        'required' => false,
                        'placeholder' => __('Your first name', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'last_name' => [
                        'type' => 'text',
                        'label' => __('Last Name', 'my-login-form'),
                        'required' => false,
                        'placeholder' => __('Your last name', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'password' => [
                        'type' => 'password',
                        'label' => __('Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Choose a password', 'my-login-form'),
                        'class' => 'form-control'
                    ],
                    'confirm_password' => [
                        'type' => 'password',
                        'label' => __('Confirm Password', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('Confirm your password', 'my-login-form'),
                        'class' => 'form-control'
                    ]
                ];
                $default_settings = [
                    'redirect_after_registration' => 'dashboard',
                    'auto_login' => true,
                    'email_verification' => false,
                    'enable_password_strength' => true,
                    'button_text' => __('Register', 'my-login-form'),
                    'button_class' => 'btn btn-success',
                ];
                break;
            default:
                $default_fields = [
                    'email' => [
                        'type' => 'email',
                        'label' => __('Email Address', 'my-login-form'),
                        'required' => true,
                        'placeholder' => __('your@email.com', 'my-login-form'),
                        'class' => 'form-control'
                    ]
                ];
                $default_settings = [
                    'button_text' => __('Submit', 'my-login-form'),
                    'button_class' => 'btn btn-primary',
                ];
        }

        // Generate form key
        $form_key = $this->generate_form_key($form_name);

        $data = array(
            'form_key' => $form_key,
            'form_type' => $form_type,
            'name' => $form_name,
            'fields' => json_encode($default_fields),
            'settings' => json_encode($default_settings),
            'css_file' => $form_key . '.css',
            'js_file' => $form_key . '.js',
            'html_file' => $form_key . '.html',
            'redirect_after_login' => 'my-profile',
            'redirect_after_logout' => 'home',
            'social_login' => in_array($form_type, ['login', 'register']) ? 1 : 0,
            'status' => 'active',
            'is_system' => 0,
            'is_builtin' => 0,
            'is_default' => 0,
            'sort_order' => 6,
            'created_by' => get_current_user_id(),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql'),
        );

        // Debug: Log the data being inserted
        error_log('Inserting form data: ' . print_r($data, true));

        $result = $wpdb->insert($table, $data);

        if ($result) {
            $new_id = $wpdb->insert_id;

            // Create blank files using form_key
            $html_file = $html_dir . $form_key . '.html';
            $css_file  = $css_dir  . $form_key . '.css';
            $js_file   = $js_dir   . $form_key . '.js';

            file_put_contents($html_file, '');
            file_put_contents($css_file, '');
            file_put_contents($js_file, '');

            // Add security files to directories
            $this->create_security_files($html_dir);
            $this->create_security_files($css_dir);
            $this->create_security_files($js_dir);

            wp_send_json_success(array(
                'id'       => $new_id,
                'form_key' => $form_key,
                'name'     => $form_name,
                'message'  => __('Form created successfully!', 'my-login-form')
            ));
        } else {
            $error = $wpdb->last_error;
            error_log('Database error: ' . $error);
            wp_send_json_error('Database error: ' . $error);
        }
    }

    /**
     * Delete form - Also deletes associated HTML, CSS, JS files
     */
    public function delete_form() {
        check_ajax_referer('my_login_delete_form', 'nonce');
                
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }
                
        $form_id = intval($_POST['form_id']);
        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'my-login-form'));
        }
                
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
                
        // Get form data first to retrieve form_key and file names
        $form = $wpdb->get_row($wpdb->prepare(
            "SELECT form_key, html_file, css_file, js_file, is_system, is_builtin FROM $table WHERE id = %d", 
            $form_id
        ));
                
        if (!$form) {
            wp_send_json_error(__('Form not found', 'my-login-form'));
        }
                
        // Check if it's a system form
        if ($form->is_system == 1 || $form->is_builtin == 1) {
            wp_send_json_error(__('System forms cannot be deleted', 'my-login-form'));
        }
                
        $form_key = $form->form_key;
                
        // Define file paths
        $html_file = MY_LOGIN_FORM_DIR . 'Public/Forms/html/' . $form_key . '.html';
        $css_file  = MY_LOGIN_FORM_DIR . 'Public/Forms/css/'  . $form_key . '.css';
        $js_file   = MY_LOGIN_FORM_DIR . 'Public/Forms/js/'   . $form_key . '.js';
                
        // Delete files if they exist
        if (file_exists($html_file)) unlink($html_file);
        if (file_exists($css_file))  unlink($css_file);
        if (file_exists($js_file))   unlink($js_file);
                
        // Delete form from database
        $result = $wpdb->delete($table, array('id' => $form_id));
                
        if ($result) {
            wp_send_json_success(__('Form deleted successfully', 'my-login-form'));
        } else {
            wp_send_json_error(__('Delete failed', 'my-login-form'));
        }
    }

    /**
     * Duplicate form - Also duplicates HTML, CSS, JS files
     */
    public function duplicate_form() {
        check_ajax_referer('my_login_duplicate_form', 'nonce');
                
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }
                
        $form_id = intval($_POST['form_id']);
        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'my-login-form'));
        }
                
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
                
        // Get original form
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id), ARRAY_A);
                
        if (!$form) {
            wp_send_json_error(__('Form not found', 'my-login-form'));
        }
                
        // Get old form_key for file operations
        $old_form_key = $form['form_key'];
                
        // Remove ID and timestamps
        unset($form['id']);
        unset($form['created_at']);
        unset($form['updated_at']);
                
        // Modify name and generate new key
        $new_name = sprintf(__('%s (Copy)', 'my-login-form'), $form['name']);
        $new_form_key = $this->generate_form_key($new_name);
                
        // Update form data
        $form['name'] = $new_name;
        $form['form_key'] = $new_form_key;
        $form['html_file'] = $new_form_key . '.html';
        $form['css_file'] = $new_form_key . '.css';
        $form['js_file'] = $new_form_key . '.js';
        $form['is_default'] = 0;
        $form['is_system'] = 0;
        $form['is_builtin'] = 0;
        $form['views_count'] = 0;
        $form['submissions_count'] = 0;
        $form['created_by'] = get_current_user_id();
        $form['created_at'] = current_time('mysql');
        $form['updated_at'] = current_time('mysql');
                
        // Insert duplicate
        $result = $wpdb->insert($table, $form);
                
        if ($result) {
            $new_id = $wpdb->insert_id;
                
            // Define directories
            $html_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';
            $css_dir  = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
            $js_dir   = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
                
            // Ensure directories exist
            if (!file_exists($html_dir)) wp_mkdir_p($html_dir);
            if (!file_exists($css_dir))  wp_mkdir_p($css_dir);
            if (!file_exists($js_dir))   wp_mkdir_p($js_dir);
                
            // Define old file paths (using old form_key)
            $old_html = $html_dir . $old_form_key . '.html';
            $old_css  = $css_dir  . $old_form_key . '.css';
            $old_js   = $js_dir   . $old_form_key . '.js';
                
            // Define new file paths (using new form_key)
            $new_html = $html_dir . $new_form_key . '.html';
            $new_css  = $css_dir  . $new_form_key . '.css';
            $new_js   = $js_dir   . $new_form_key . '.js';
                
            // Duplicate files if they exist
            if (file_exists($old_html)) copy($old_html, $new_html);
            if (file_exists($old_css))  copy($old_css, $new_css);
            if (file_exists($old_js))   copy($old_js, $new_js);
                
            // If original HTML doesn't exist, create blank one
            if (!file_exists($old_html)) {
                file_put_contents($new_html, '');
            }
                
            // Add security files to directories
            $this->create_security_files($html_dir);
            $this->create_security_files($css_dir);
            $this->create_security_files($js_dir);
                
            wp_send_json_success(array(
                'id'       => $new_id,
                'form_key' => $new_form_key,
                'name'     => $new_name,
                'message'  => __('Form duplicated successfully', 'my-login-form')
            ));
        } else {
            wp_send_json_error(__('Failed to duplicate form', 'my-login-form'));
        }
    }

    /**
     * Get form data - Returns both file names and file contents
     */
    public function get_form() {
        check_ajax_referer('my_login_get_form', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }

        $form_id = intval($_POST['form_id']);
        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'my-login-form'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id));

        if (!$form) {
            wp_send_json_error(__('Form not found', 'my-login-form'));
        }

        // Parse JSON data
        $fields = json_decode($form->fields, true) ?: array();
        $settings = json_decode($form->settings, true) ?: array();

        // Get selected fields (keys from fields array)
        $selected_fields = array_keys($fields);

        // Get social providers from settings or default empty array
        $social_providers = isset($settings['social_providers']) ? $settings['social_providers'] : array();

        // Load HTML content from file if exists
        $html_content = '';
        $html_file_path = MY_LOGIN_FORM_DIR . 'Public/Forms/html/' . $form->html_file;
        if (!empty($form->html_file) && file_exists($html_file_path)) {
            $html_content = file_get_contents($html_file_path);
        }

        // Load CSS content from file if exists
        $css_content = '';
        $css_file_path = MY_LOGIN_FORM_DIR . 'Public/Forms/css/' . $form->css_file;
        if (!empty($form->css_file) && file_exists($css_file_path)) {
            $css_content = file_get_contents($css_file_path);
        }

        // Load JS content from file if exists
        $js_content = '';
        $js_file_path = MY_LOGIN_FORM_DIR . 'Public/Forms/js/' . $form->js_file;
        if (!empty($form->js_file) && file_exists($js_file_path)) {
            $js_content = file_get_contents($js_file_path);
        }

        $data = array(
            'id' => $form->id,
            'form_id' => $form->id,
            'form_key' => $form->form_key,
            'name' => $form->name,
            'form_type' => $form->form_type,
            'description' => $form->description,
            'fields' => $fields,
            'selected_fields' => $selected_fields,
            'social_providers' => $social_providers,
            'settings' => $settings,
            'containers' => !empty($form->form_containers) ? json_decode($form->form_containers, true) : array(),
            // File names (from database)
            'html_filename' => $form->html_file,
            'css_filename'  => $form->css_file,
            'js_filename'   => $form->js_file,
            // File contents (from actual files)
            'html_content' => $html_content,
            'css_content'  => $css_content,
            'js_content'   => $js_content,
            // Other form data
            'social_login' => (bool)$form->social_login,
            'redirect_after_login' => $form->redirect_after_login,
            'redirect_after_logout' => $form->redirect_after_logout,
            'is_system' => (bool)$form->is_system,
            'is_builtin' => (bool)$form->is_builtin,
            'status' => $form->status,
            'created_at' => $form->created_at,
            'updated_at' => $form->updated_at,
        );

        wp_send_json_success($data);
    }

    /**
     * Generate unique form key
     */
    private function generate_form_key($name) {
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        
        $base = sanitize_title($name);
        $key = $base;
        $counter = 1;
        
        while ($wpdb->get_var($wpdb->prepare("SELECT id FROM $table WHERE form_key = %s", $key))) {
            $key = $base . '-' . $counter;
            $counter++;
        }
        
        return $key;
    }

    /**
     * Preview form - Shows form from database
     */
    public function preview_form() {
        $form_id = intval($_GET['form_id']);
        $nonce = sanitize_text_field($_GET['_wpnonce'] ?? '');

        if (!wp_verify_nonce($nonce, 'my_login_preview_form')) {
            wp_die(__('Invalid nonce', 'my-login-form'));
        }

        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id));

        if (!$form) {
            wp_die(__('Form not found', 'my-login-form'));
        }

        $fields = json_decode($form->fields, true) ?: array();
        $settings = json_decode($form->settings, true) ?: array();
        $social_providers = isset($settings['social_providers']) ? $settings['social_providers'] : array();
                
        // Load file contents
        $html_content = '';
        $html_file_path = MY_LOGIN_FORM_DIR . 'Public/Forms/html/' . $form->html_file;
        if (!empty($form->html_file) && file_exists($html_file_path)) {
            $html_content = file_get_contents($html_file_path);
        }
                
        $css_content = '';
        $css_file_path = MY_LOGIN_FORM_DIR . 'Public/Forms/css/' . $form->css_file;
        if (!empty($form->css_file) && file_exists($css_file_path)) {
            $css_content = file_get_contents($css_file_path);
        }
                
        $js_content = '';
        $js_file_path = MY_LOGIN_FORM_DIR . 'Public/Forms/js/' . $form->js_file;
        if (!empty($form->js_file) && file_exists($js_file_path)) {
            $js_content = file_get_contents($js_file_path);
        }

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($form->name); ?> - <?php _e('Preview', 'my-login-form'); ?></title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    background: #f1f1f1;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }
                .preview-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    padding: 30px;
                }
                .preview-header {
                    margin-bottom: 20px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #f0f0f0;
                }
                .preview-header h2 {
                    margin: 0;
                    font-size: 24px;
                }
                .preview-header p {
                    margin: 5px 0 0;
                    color: #666;
                    font-size: 12px;
                }
                <?php echo $css_content; ?>
            </style>
        </head>
        <body>
            <div class="preview-container">
                <div class="preview-header">
                    <h2><?php echo esc_html($form->name); ?></h2>
                    <p><?php _e('Preview Mode - Form is not actually submitted', 'my-login-form'); ?></p>
                </div>

                <?php echo $html_content; ?>

                <div class="my-login-form my-login-form-<?php echo esc_attr($form->form_key); ?>">
                    <form method="post" class="my-login-form-form" onsubmit="alert('<?php _e('This is a preview. The form is not actually submitted.', 'my-login-form'); ?>'); return false;">
                        <?php wp_nonce_field('my_login_form_nonce', 'my_login_form_nonce'); ?>
                        <input type="hidden" name="form_id" value="<?php echo esc_attr($form->id); ?>">
                        <input type="hidden" name="form_key" value="<?php echo esc_attr($form->form_key); ?>">
                        <input type="hidden" name="action" value="my_login_form_submit">

                        <?php 
                        $show_labels = isset($settings['show_labels']) ? $settings['show_labels'] : true;

                        foreach ($fields as $field_name => $field): 
                            $required = isset($field['required']) && $field['required'] ? 'required' : '';
                            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
                            $class = isset($field['class']) ? $field['class'] : 'form-control';
                            $type = isset($field['type']) ? $field['type'] : 'text';
                            $label = isset($field['label']) ? $field['label'] : ucfirst(str_replace('_', ' ', $field_name));
                        ?>
                            <div class="form-group">
                                <?php if ($show_labels && $type !== 'checkbox'): ?>
                                    <label for="field_<?php echo esc_attr($field_name); ?>">
                                        <?php echo esc_html($label); ?>
                                        <?php if ($required): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endif; ?>
                                        
                                <?php if ($type === 'textarea'): ?>
                                    <textarea id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                        rows="4"
                                        <?php echo $required; ?>></textarea>
                                <?php elseif ($type === 'checkbox'): ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox"
                                            name="<?php echo esc_attr($field_name); ?>"
                                            class="<?php echo esc_attr($class); ?>"
                                            value="1"
                                            <?php echo $required; ?>>
                                        <?php echo esc_html($label); ?>
                                        <?php if ($required): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                <?php elseif ($type === 'select'): ?>
                                    <select id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        <?php echo $required; ?>>
                                        <option value=""><?php _e('Select', 'my-login-form'); ?></option>
                                        <option value="male"><?php _e('Male', 'my-login-form'); ?></option>
                                        <option value="female"><?php _e('Female', 'my-login-form'); ?></option>
                                        <option value="other"><?php _e('Other', 'my-login-form'); ?></option>
                                    </select>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($type); ?>"
                                        id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                        <?php echo $required; ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                                
                        <?php if (!empty($social_providers)): ?>
                            <div class="social-login-section">
                                <div class="social-login-title"><?php _e('Or login with', 'my-login-form'); ?></div>
                                <div class="social-buttons">
                                    <?php foreach ($social_providers as $provider): ?>
                                        <button type="button" class="social-button" style="background: <?php echo esc_attr($this->get_provider_color($provider)); ?>">
                                            <?php echo ucfirst($provider); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                                    
                        <div class="form-submit" style="margin-top: 30px;">
                            <button type="submit" class="btn btn-primary">
                                <?php echo esc_html($settings['button_text'] ?? __('Submit', 'my-login-form')); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
                                    
            <?php wp_footer(); ?>
            <script>
                <?php echo $js_content; ?>
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Preview live form from local data (without saving) - For live preview in designer
     */
    public function preview_from_local() {
        // Check nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'my_login_preview_form')) {
            wp_die('Invalid nonce');
        }
                                        
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }
                                        
        $form_data = json_decode(stripslashes($_POST['form_data']), true);
        $form_id = intval($_POST['form_id']);
        $form_key = isset($_POST['form_key']) ? sanitize_key($_POST['form_key']) : '';
        $form_name = sanitize_text_field($_POST['form_name']);
                                        
        if (!$form_data) {
            wp_die('Invalid form data');
        }
                                        
        // Get data with correct keys (match get_form() structure)
        $fields = isset($form_data['fields']) ? $form_data['fields'] : array();
        $settings = isset($form_data['settings']) ? $form_data['settings'] : array();
        $html_content = isset($form_data['html_content']) ? $form_data['html_content'] : '';
        $css_content  = isset($form_data['css_content'])  ? $form_data['css_content']  : '';
        $js_content   = isset($form_data['js_content'])   ? $form_data['js_content']   : '';
        $social_providers = isset($form_data['social_providers']) ? $form_data['social_providers'] : array();
                                        
        // Fallback for old data structure
        if (empty($html_content) && isset($form_data['html_file'])) {
            $html_content = $form_data['html_file'];
        }
        if (empty($css_content) && isset($form_data['css_file'])) {
            $css_content = $form_data['css_file'];
        }
        if (empty($js_content) && isset($form_data['js_file'])) {
            $js_content = $form_data['js_file'];
        }
                                        
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo('charset'); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title><?php echo esc_html($form_name); ?> - Live Preview</title>
            <?php wp_head(); ?>
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    background: #f1f1f1;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                }
                .preview-container {
                    max-width: 600px;
                    margin: 0 auto;
                    background: #ffffff;
                    border-radius: 8px;
                    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
                    padding: 30px;
                }
                .preview-header {
                    margin-bottom: 20px;
                    padding-bottom: 20px;
                    border-bottom: 2px solid #f0f0f0;
                }
                .preview-header h2 {
                    margin: 0;
                    font-size: 24px;
                }
                .preview-header p {
                    margin: 5px 0 0;
                    color: #666;
                    font-size: 12px;
                }
                .form-group {
                    margin-bottom: 20px;
                }
                .form-group label {
                    display: block;
                    margin-bottom: 5px;
                    font-weight: 500;
                }
                .form-group label .required {
                    color: #dc3545;
                    margin-left: 4px;
                }
                .form-control {
                    width: 100%;
                    padding: 10px;
                    border: 1px solid #ddd;
                    border-radius: 4px;
                    font-size: 14px;
                    box-sizing: border-box;
                }
                .form-control:focus {
                    outline: none;
                    border-color: <?php echo esc_attr($settings['btn_color'] ?? '#0073aa'); ?>;
                }
                .checkbox-label {
                    display: flex;
                    align-items: center;
                    gap: 8px;
                    cursor: pointer;
                }
                .checkbox-label input {
                    width: auto;
                }
                .btn {
                    display: inline-block;
                    padding: 12px 24px;
                    border: none;
                    border-radius: 4px;
                    cursor: pointer;
                    font-size: 16px;
                }
                .btn-primary {
                    background-color: <?php echo esc_attr($settings['btn_color'] ?? '#0073aa'); ?>;
                    color: #ffffff;
                }
                .btn-primary:hover {
                    opacity: 0.9;
                }
                .form-submit {
                    margin-top: 30px;
                }
                .social-login-section {
                    margin-top: 20px;
                    padding-top: 20px;
                    border-top: 1px solid #eee;
                }
                .social-login-title {
                    text-align: center;
                    margin-bottom: 15px;
                    color: #666;
                }
                .social-buttons {
                    display: flex;
                    gap: 10px;
                    justify-content: center;
                }
                .social-button {
                    padding: 10px 20px;
                    border: none;
                    border-radius: 4px;
                    color: white;
                    cursor: pointer;
                }
                <?php echo $css_content; ?>
            </style>
        </head>
        <body>
            <div class="preview-container">
                <div class="preview-header">
                    <h2><?php echo esc_html($form_name); ?></h2>
                    <p>Live Preview - Changes appear instantly</p>
                </div>
                                        
                <?php echo $html_content; ?>
                                        
                <div class="my-login-form my-login-form-<?php echo esc_attr($form_key ?: $form_id); ?>">
                    <form method="post" class="my-login-form-form" onsubmit="alert('This is a preview. The form is not actually submitted.'); return false;">
                        <?php wp_nonce_field('my_login_form_nonce', 'my_login_form_nonce'); ?>
                        <input type="hidden" name="form_id" value="<?php echo esc_attr($form_id); ?>">
                        <input type="hidden" name="form_key" value="<?php echo esc_attr($form_key); ?>">
                        <input type="hidden" name="action" value="my_login_form_submit">
                                        
                        <?php 
                        $show_labels = isset($settings['show_labels']) ? $settings['show_labels'] : true;
                                        
                        foreach ($fields as $field_name => $field):
                            $required = isset($field['required']) && $field['required'] ? 'required' : '';
                            $placeholder = isset($field['placeholder']) ? $field['placeholder'] : '';
                            $class = isset($field['class']) ? $field['class'] : 'form-control';
                            $type = isset($field['type']) ? $field['type'] : 'text';
                            $label = isset($field['label']) ? $field['label'] : ucfirst(str_replace('_', ' ', $field_name));
                        ?>
                            <div class="form-group">
                                <?php if ($show_labels && $type !== 'checkbox'): ?>
                                    <label for="field_<?php echo esc_attr($field_name); ?>">
                                        <?php echo esc_html($label); ?>
                                        <?php if ($required): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                <?php endif; ?>
                                        
                                <?php if ($type === 'textarea'): ?>
                                    <textarea id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                        rows="4"
                                        <?php echo $required; ?>></textarea>
                                <?php elseif ($type === 'checkbox'): ?>
                                    <label class="checkbox-label">
                                        <input type="checkbox"
                                            name="<?php echo esc_attr($field_name); ?>"
                                            class="<?php echo esc_attr($class); ?>"
                                            value="1"
                                            <?php echo $required; ?>>
                                        <?php echo esc_html($label); ?>
                                        <?php if ($required): ?>
                                            <span class="required">*</span>
                                        <?php endif; ?>
                                    </label>
                                <?php elseif ($type === 'select'): ?>
                                    <select id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        <?php echo $required; ?>>
                                        <option value=""><?php _e('Select', 'my-login-form'); ?></option>
                                        <option value="male"><?php _e('Male', 'my-login-form'); ?></option>
                                        <option value="female"><?php _e('Female', 'my-login-form'); ?></option>
                                        <option value="other"><?php _e('Other', 'my-login-form'); ?></option>
                                    </select>
                                <?php else: ?>
                                    <input type="<?php echo esc_attr($type); ?>"
                                        id="field_<?php echo esc_attr($field_name); ?>"
                                        name="<?php echo esc_attr($field_name); ?>"
                                        class="<?php echo esc_attr($class); ?>"
                                        placeholder="<?php echo esc_attr($placeholder); ?>"
                                        <?php echo $required; ?>>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; ?>
                                
                        <?php if (!empty($social_providers)): ?>
                            <div class="social-login-section">
                                <div class="social-login-title"><?php _e('Or login with', 'my-login-form'); ?></div>
                                <div class="social-buttons">
                                    <?php foreach ($social_providers as $provider): ?>
                                        <button type="button" class="social-button" style="background: <?php echo esc_attr($this->get_provider_color($provider)); ?>">
                                            <?php echo ucfirst($provider); ?>
                                        </button>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endif; ?>
                                    
                        <div class="form-submit">
                            <button type="submit" class="btn btn-primary">
                                <?php echo esc_html($settings['button_text'] ?? __('Submit', 'my-login-form')); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
                                    
            <?php wp_footer(); ?>
            <script>
                <?php echo $js_content; ?>
            </script>
        </body>
        </html>
        <?php
        exit;
    }

    /**
     * Save form - Main save function (saves both database AND files)
     */
    public function save_form() {
        check_ajax_referer('my_login_save_form', 'nonce');
                                        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }
                                        
        $form_id = intval($_POST['form_id']);
        $form_data = json_decode(stripslashes($_POST['form_data']), true);
                                        
        if (!$form_id || !$form_data) {
            wp_send_json_error(__('Invalid form data', 'my-login-form'));
        }
                                        
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
                                        
        // Get form_key for file operations
        $form = $wpdb->get_row($wpdb->prepare("SELECT form_key FROM $table WHERE id = %d", $form_id));
        if (!$form) {
            wp_send_json_error(__('Form not found', 'my-login-form'));
        }
                                        
        $form_key = $form->form_key;
                                        
        // Define directories
        $html_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';
        $css_dir  = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
        $js_dir   = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
                                        
        // Ensure directories exist
        if (!file_exists($html_dir)) wp_mkdir_p($html_dir);
        if (!file_exists($css_dir))  wp_mkdir_p($css_dir);
        if (!file_exists($js_dir))   wp_mkdir_p($js_dir);
                                        
        // Get file contents from form_data (using content keys, not filename keys)
        $html_content = isset($form_data['html_content']) ? wp_unslash($form_data['html_content']) : '';
        $css_content  = isset($form_data['css_content'])  ? wp_unslash($form_data['css_content'])  : '';
        $js_content   = isset($form_data['js_content'])   ? wp_unslash($form_data['js_content'])   : '';
                                        
        // Fallback for old data structure
        if (empty($html_content) && isset($form_data['html_file'])) {
            $html_content = wp_unslash($form_data['html_file']);
        }
        if (empty($css_content) && isset($form_data['css_file'])) {
            $css_content = wp_unslash($form_data['css_file']);
        }
        if (empty($js_content) && isset($form_data['js_file'])) {
            $js_content = wp_unslash($form_data['js_file']);
        }
                                        
        // Define file paths
        $html_file_path = $html_dir . $form_key . '.html';
        $css_file_path  = $css_dir  . $form_key . '.css';
        $js_file_path   = $js_dir   . $form_key . '.js';
                                        
        // Write content to files (empty content = delete file)
        if (empty(trim($html_content))) {
            if (file_exists($html_file_path)) unlink($html_file_path);
        } else {
            file_put_contents($html_file_path, $html_content);
        }
                                        
        if (empty(trim($css_content))) {
            if (file_exists($css_file_path)) unlink($css_file_path);
        } else {
            file_put_contents($css_file_path, $css_content);
        }
                                        
        if (empty(trim($js_content))) {
            if (file_exists($js_file_path)) unlink($js_file_path);
        } else {
            file_put_contents($js_file_path, $js_content);
        }
                                        
        // Add security files to directories
        $this->create_security_files($html_dir);
        $this->create_security_files($css_dir);
        $this->create_security_files($js_dir);
                                        
        // Prepare database update
        $update_data = array(
            'name'       => sanitize_text_field($form_data['name']),
            'form_type'  => sanitize_text_field($form_data['form_type']),
            'status'     => sanitize_text_field($form_data['status']),
            'fields'     => is_array($form_data['fields']) ? json_encode($form_data['fields']) : $form_data['fields'],
            'settings'   => is_array($form_data['settings']) ? json_encode($form_data['settings']) : $form_data['settings'],
            'html_file'  => $form_key . '.html',
            'css_file'   => $form_key . '.css',
            'js_file'    => $form_key . '.js',
            'updated_at' => current_time('mysql'),
            'updated_by' => get_current_user_id(),
        );
                                        
        $result = $wpdb->update($table, $update_data, array('id' => $form_id));
                                        
        if ($result !== false) {
            wp_send_json_success(array(
                'message'    => __('Form saved successfully', 'my-login-form'),
                'form_id'    => $form_id,
                'form_key'   => $form_key,
                'files'      => array(
                    'html' => !empty($html_content) ? 'saved' : 'deleted',
                    'css'  => !empty($css_content) ? 'saved' : 'deleted',
                    'js'   => !empty($js_content) ? 'saved' : 'deleted',
                )
            ));
        } else {
            wp_send_json_error(__('Failed to save form', 'my-login-form'));
        }
    }

    

    public function submit_form() {
        // Verify nonce
        if (!isset($_POST['my_login_form_nonce']) || 
            !wp_verify_nonce($_POST['my_login_form_nonce'], 'my_login_form_nonce')) {
            wp_send_json_error(__('Security check failed', 'my-login-form'));
        }
    
        $form_id = intval($_POST['form_id']);
    
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
        $form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $form_id));
    
        if (!$form) {
            wp_send_json_error(__('Form not found', 'my-login-form'));
        }
    
        $fields = json_decode($form->fields, true) ?: array();
    
        // Validate required fields
        $errors = array();
        foreach ($fields as $field_name => $field) {
            if (isset($field['required']) && $field['required']) {
                if (empty($_POST[$field_name])) {
                    $errors[] = sprintf(__('%s is required', 'my-login-form'), $field['label']);
                }
            }
        }
    
        if (!empty($errors)) {
            wp_send_json_error(implode('<br>', $errors));
        }
    
        // Process the form data here
        // For example, create user, send email, etc.
    
        // For now, just log the submission
        error_log('Form submission - Form ID: ' . $form_id . ' - Data: ' . print_r($_POST, true));
    
        wp_send_json_success(array(
            'message' => __('Form submitted successfully!', 'my-login-form'),
            'clear_form' => true,
            'redirect' => home_url()
        ));
    }
    
    public function save_form_settings() {
    error_log('=== save_form_settings started ===');
    
    // Check if nonce exists
    if (!isset($_POST['nonce'])) {
        error_log('ERROR: Nonce not provided');
        wp_send_json_error('Security check failed: Nonce not provided');
        return;
    }
    
    // Verify nonce
    if (!wp_verify_nonce($_POST['nonce'], 'my_login_save_form_settings')) {
        error_log('ERROR: Nonce verification failed');
        wp_send_json_error('Security check failed: Invalid nonce');
        return;
    }
    
    // Check permissions
    if (!current_user_can('manage_options')) {
        error_log('ERROR: User unauthorized');
        wp_send_json_error('Unauthorized');
        return;
    }
    
    // Check if form_data exists
    if (!isset($_POST['form_data'])) {
        error_log('ERROR: form_data not provided');
        wp_send_json_error('Form data not provided');
        return;
    }
    
    // Decode form data
    $form_data = json_decode(stripslashes($_POST['form_data']), true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log('ERROR: JSON decode error: ' . json_last_error_msg());
        wp_send_json_error('Invalid JSON: ' . json_last_error_msg());
        return;
    }
    
    if (!$form_data || !isset($form_data['form_id'])) {
        error_log('ERROR: Invalid form data structure');
        wp_send_json_error('Invalid form data structure');
        return;
    }
    
    $form_id = intval($form_data['form_id']);
    error_log('Form ID: ' . $form_id);
    
    global $wpdb;
    $table = $wpdb->prefix . 'my_login_forms';
    
    // Get existing form data to retrieve form_key
    $existing_form = $wpdb->get_row($wpdb->prepare("SELECT form_key FROM $table WHERE id = %d", $form_id));
    if (!$existing_form) {
        error_log('ERROR: Form not found');
        wp_send_json_error('Form not found');
        return;
    }
    
    $form_key = $existing_form->form_key;
    error_log('Form Key: ' . $form_key);
    
    // ============================================
    // DEBUG: Log received data
    // ============================================
    error_log('Received form_data keys: ' . print_r(array_keys($form_data), true));
    
    // ============================================
    // 1. GET CONTAINERS FROM FORM_DATA
    // ============================================
    $containers = isset($form_data['containers']) ? $form_data['containers'] : array();
    error_log('Containers count: ' . count($containers));
    error_log('Containers data: ' . print_r($containers, true));
    
    // ============================================
    // 2. BUILD FIELDS ARRAY FROM CONTAINERS
    // ============================================
    $fields_to_save = array();
    $this->extractFieldsFromContainers($containers, $fields_to_save);
    error_log('Fields to save: ' . print_r(array_keys($fields_to_save), true));
    
    // ============================================
    // 3. BUILD SETTINGS ARRAY
    // ============================================
    $settings = isset($form_data['settings']) ? $form_data['settings'] : array();
    
    $default_settings = [
        'bg_color' => '#ffffff',
        'text_color' => '#333333',
        'btn_color' => '#0073aa',
        'border_color' => '#dddddd',
        'show_labels' => true,
        'show_remember_me' => false,
        'enable_validation' => true,
        'enable_password_strength' => false,
        'redirect_after_login' => 'dashboard',
        'redirect_after_logout' => 'home',
        'custom_redirect_url' => '',
        'button_text' => 'Submit',
        'button_class' => 'btn btn-primary'
    ];
    
    $settings = array_merge($default_settings, $settings);
    
    // Add social providers to settings
    $social_providers = isset($form_data['social_providers']) ? $form_data['social_providers'] : array();
    $settings['social_providers'] = $social_providers;
    error_log('Social providers to save: ' . print_r($social_providers, true));
    
    // ============================================
    // 4. GET FILE CONTENTS
    // ============================================
    $html_content = isset($form_data['html_content']) ? $form_data['html_content'] : '';
    $css_content = isset($form_data['css_content']) ? $form_data['css_content'] : '';
    $js_content = isset($form_data['js_content']) ? $form_data['js_content'] : '';
    
    error_log('HTML content length: ' . strlen($html_content));
    error_log('CSS content length: ' . strlen($css_content));
    error_log('JS content length: ' . strlen($js_content));
    
    // ============================================
    // 5. PREPARE UPDATE DATA
    // ============================================
    $update_data = [
        'fields' => json_encode($fields_to_save),
        'settings' => json_encode($settings),
        'form_containers' => json_encode($containers),
        'updated_at' => current_time('mysql'),
        'updated_by' => get_current_user_id(),
    ];
    
    // ============================================
    // 6. HANDLE HTML FILE
    // ============================================
    $html_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';
    $html_file_path = $html_dir . $form_key . '.html';
    
    if (!file_exists($html_dir)) {
        wp_mkdir_p($html_dir);
    }
    
    if (!empty($html_content)) {
        $html_result = file_put_contents($html_file_path, $html_content);
        if ($html_result !== false) {
            $update_data['html_file'] = $form_key . '.html';
            error_log('HTML file saved/updated: ' . $html_file_path . ' (' . $html_result . ' bytes)');
        } else {
            error_log('ERROR: Failed to save HTML file: ' . $html_file_path);
        }
    } else {
        if (file_exists($html_file_path)) {
            unlink($html_file_path);
            error_log('HTML file deleted: ' . $html_file_path);
        }
        $update_data['html_file'] = '';
    }
    
    // ============================================
    // 7. HANDLE CSS FILE
    // ============================================
    $css_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
    $css_file_path = $css_dir . $form_key . '.css';
    
    if (!file_exists($css_dir)) {
        wp_mkdir_p($css_dir);
    }
    
    if (!empty($css_content)) {
        $css_result = file_put_contents($css_file_path, $css_content);
        if ($css_result !== false) {
            $update_data['css_file'] = $form_key . '.css';
            error_log('CSS file saved/updated: ' . $css_file_path . ' (' . $css_result . ' bytes)');
        } else {
            error_log('ERROR: Failed to save CSS file: ' . $css_file_path);
        }
    } else {
        if (file_exists($css_file_path)) {
            unlink($css_file_path);
            error_log('CSS file deleted: ' . $css_file_path);
        }
        $update_data['css_file'] = '';
    }
    
    // ============================================
    // 8. HANDLE JS FILE
    // ============================================
    $js_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
    $js_file_path = $js_dir . $form_key . '.js';
    
    if (!file_exists($js_dir)) {
        wp_mkdir_p($js_dir);
    }
    
    if (!empty($js_content)) {
        $js_result = file_put_contents($js_file_path, $js_content);
        if ($js_result !== false) {
            $update_data['js_file'] = $form_key . '.js';
            error_log('JS file saved/updated: ' . $js_file_path . ' (' . $js_result . ' bytes)');
        } else {
            error_log('ERROR: Failed to save JS file: ' . $js_file_path);
        }
    } else {
        if (file_exists($js_file_path)) {
            unlink($js_file_path);
            error_log('JS file deleted: ' . $js_file_path);
        }
        $update_data['js_file'] = '';
    }
    
    // ============================================
    // 9. ADD SECURITY FILES TO DIRECTORIES
    // ============================================
    $this->create_security_files($html_dir);
    $this->create_security_files($css_dir);
    $this->create_security_files($js_dir);
    
    error_log('Update data prepared: ' . print_r(array_keys($update_data), true));
    
    // ============================================
    // 10. PERFORM THE UPDATE
    // ============================================
    $result = $wpdb->update($table, $update_data, array('id' => $form_id));
    
    if ($result !== false) {
        error_log('Update successful');
        
        wp_send_json_success([
            'message' => 'Form saved successfully',
            'form_id' => $form_id,
            'form_key' => $form_key,
            'fields_count' => count($fields_to_save),
            'social_count' => count($social_providers),
            'files' => [
                'html' => !empty($html_content) ? 'saved' : 'deleted',
                'css' => !empty($css_content) ? 'saved' : 'deleted',
                'js' => !empty($js_content) ? 'saved' : 'deleted',
            ],
            'saved_data' => [
                'fields' => array_keys($fields_to_save),
                'social_providers' => $social_providers,
            ]
        ]);
    } else {
        $error = $wpdb->last_error;
        error_log('ERROR: Database update failed: ' . $error);
        wp_send_json_error('Database error: ' . $error);
    }
}

/**
 * Extract fields from containers recursively
 */
private function extractFieldsFromContainers($containers, &$fields) {
    if (!is_array($containers)) return;
    
    foreach ($containers as $container) {
        if (isset($container['items']) && is_array($container['items'])) {
            $this->extractFieldsFromItems($container['items'], $fields);
        }
    }
}

/**
 * Extract fields from items recursively
 */
private function extractFieldsFromItems($items, &$fields) {
    if (!is_array($items)) return;
    
    foreach ($items as $item) {
        if ($item['type'] === 'field') {
            $field_name = isset($item['fieldType']) ? $item['fieldType'] : 'field_' . uniqid();
            $fields[$field_name] = array(
                'type' => isset($item['htmlType']) ? $item['htmlType'] : 'text',
                'label' => isset($item['label']) ? $item['label'] : 'Field',
                'required' => isset($item['required']) ? $item['required'] : false,
                'placeholder' => isset($item['placeholder']) ? $item['placeholder'] : '',
                'class' => 'form-control'
            );
        } elseif (isset($item['items']) && is_array($item['items'])) {
            $this->extractFieldsFromItems($item['items'], $fields);
        }
    }
}


    // ================ HTML file functions ================

    // /**
    //  * Create html file
    //  */
    // public function create_html() {
    //     check_ajax_referer('my_login_create_form_html', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_id = intval($_POST['form_id']);
    //     $form_key = sanitize_key($_POST['form_key']);
    //     $content = wp_unslash($_POST['content']);
                                        
    //     if (!$form_id || !$form_key) {
    //         wp_send_json_error('Form ID and form key required');
    //     }
                                        
    //     $dir = $this->ensure_html_directory();
    //     $filename = $form_key . '.html';
    //     $filepath = $dir . $filename;
                                        
    //     $result = file_put_contents($filepath, $content);
                                        
    //     if ($result !== false) {
    //         global $wpdb;
    //         $table = $wpdb->prefix . 'my_login_forms';
    //         $wpdb->update($table, array('html_file' => $filename), array('id' => $form_id));
                                        
    //         wp_send_json_success(array(
    //             'message' => 'HTML file created successfully',
    //             'filename' => $filename,
    //             'size' => $result
    //         ));
    //     } else {
    //         wp_send_json_error('Failed to create HTML file');
    //     }
    // }

    // /**
    //  * Delete HTML file
    //  */
    // public function delete_html() {
    //     check_ajax_referer('my_login_delete_form_html', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_key = sanitize_key($_POST['form_key']);
                                        
    //     if (!$form_key) {
    //         wp_send_json_error('Form key required');
    //     }
                                        
    //     $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';
    //     $filepath = $dir . $form_key . '.html';
                                        
    //     if (file_exists($filepath)) {
    //         unlink($filepath);
    //     }
                                        
    //     wp_send_json_success('HTML file deleted');
    // }

    // /**
    //  * Get html file content
    //  */
    // public function get_html() {

    //     check_ajax_referer('my_login_get_form_html', 'nonce');

    //     if (!current_user_can('manage_options')) {

    //         wp_send_json_error('Unauthorized');

    //     }

    //     $form_key = sanitize_key($_POST['form_key']);

    //     if (!$form_key) {

    //         wp_send_json_error('Form key required');

    //     }

    //     $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';

    //     $filepath = $dir . $form_key . '.html';

    //     if (file_exists($filepath)) {

    //         $content = file_get_contents($filepath);

    //         wp_send_json_success([

    //             'content' => $content,
    //             'size' => filesize($filepath),
    //             'modified' => date('Y-m-d H:i:s', filemtime($filepath))

    //         ]);

    //     } else {

    //         wp_send_json_success(array('content' => ''));

    //     }

    // }

    /**
     * Save html file
     */
    public function save_html() {

     error_log('=== save_html called ===');
    error_log('POST form_key: ' . ($_POST['form_key'] ?? 'NOT SET'));
    error_log('POST content length: ' . strlen($_POST['content'] ?? ''));
        check_ajax_referer('my_login_save_form_html', 'nonce');
                                        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Unauthorized');
        }
                                        
        $form_key = sanitize_key($_POST['form_key']);
        $content = wp_unslash($_POST['content']);
                                        
        if (!$form_key) {
            wp_send_json_error('Form key required');
        }
                                        
        $dir = $this->ensure_html_directory();
        $filename = $form_key . '.html';
        $filepath = $dir . $filename;
                                        
        if (empty(trim($content))) {
            // Delete if content is empty
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            wp_send_json_success('HTML file deleted (empty content)');
        } else {
            $result = file_put_contents($filepath, $content);
            if ($result !== false) {
                wp_send_json_success(array(
                    'message' => 'HTML saved successfully',
                    'size' => $result
                ));
            } else {
                wp_send_json_error('Failed to save HTML file');
            }
        }
    }

    // // ================ CSS file functions ================

    // /**
    //  * Create css file
    //  */
    // public function create_css() {
    //     check_ajax_referer('my_login_create_form_css', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_id = intval($_POST['form_id']);
    //     $form_key = sanitize_key($_POST['form_key']);
    //     $content = wp_unslash($_POST['content']);
                                        
    //     if (!$form_id || !$form_key) {
    //         wp_send_json_error('Form ID and form key required');
    //     }
                                        
    //     $dir = $this->ensure_css_directory();
    //     $filename = $form_key . '.css';
    //     $filepath = $dir . $filename;
                                        
    //     $result = file_put_contents($filepath, $content);
                                        
    //     if ($result !== false) {
    //         global $wpdb;
    //         $table = $wpdb->prefix . 'my_login_forms';
    //         $wpdb->update($table, array('css_file' => $filename), array('id' => $form_id));
                                        
    //         wp_send_json_success(array(
    //             'message' => 'CSS file created successfully',
    //             'filename' => $filename
    //         ));
    //     } else {
    //         wp_send_json_error('Failed to create CSS file');
    //     }
    // }

    // /**
    //  * Delete css file
    //  */
    // public function delete_form_css() {
    //     check_ajax_referer('my_login_delete_form_css', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_key = sanitize_key($_POST['form_key']);
                                        
    //     if (!$form_key) {
    //         wp_send_json_error('Form key required');
    //     }
                                        
    //     $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
    //     $filepath = $dir . $form_key . '.css';
                                        
    //     if (file_exists($filepath)) {
    //         unlink($filepath);
    //     }
                                        
    //     wp_send_json_success('CSS file deleted');
    // }

    // /**
    //  * Get css file content
    //  */
    // public function get_css() {
    //     check_ajax_referer('my_login_get_form_css', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_key = sanitize_key($_POST['form_key']);
                                        
    //     if (!$form_key) {
    //         wp_send_json_error('Form key required');
    //     }
                                        
    //     $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
    //     $filepath = $dir . $form_key . '.css';
                                        
    //     if (file_exists($filepath)) {
    //         wp_send_json_success(array('content' => file_get_contents($filepath)));
    //     } else {
    //         wp_send_json_success(array('content' => ''));
    //     }
    // }

    /**
     * Save css file
     */
    public function save_css() {
        check_ajax_referer('my_login_save_form_css', 'nonce');
                                        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }
                                        
        $form_id = intval($_POST['form_id']);
        $css_content = wp_unslash($_POST['css_content']);
        $css_content = trim($css_content);
                                        
        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'my-login-form'));
        }
                                        
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
                                        
        $css_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
        $css_file_path = $css_dir . $form_id . '.css';
                                        
        if (!empty($css_content)) {
            // Save to file
            $this->ensure_css_directory();
            $result = file_put_contents($css_file_path, $css_content);
                                        
            if ($result !== false) {
                // Update database
                $wpdb->update($table, array('css_file' => $form_id . '.css'), array('id' => $form_id));
                wp_send_json_success(array(
                    'message' => 'CSS saved successfully',
                    'file_size' => $result,
                    'action' => 'saved'
                ));
            } else {
                wp_send_json_error(__('Failed to save CSS file', 'my-login-form'));
            }
        } else {
            // Delete file if empty
            if (file_exists($css_file_path)) {
                unlink($css_file_path);
            }
            // Update database to remove reference
            $wpdb->update($table, array('css_file' => ''), array('id' => $form_id));
            wp_send_json_success(array(
                'message' => 'CSS cleared and file deleted',
                'action' => 'deleted'
            ));
        }
    }

    // // ================ JS file functions ================

    // /**
    //  * Create js file
    //  */
    // public function create_js() {
    //     check_ajax_referer('my_login_create_form_js', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_id = intval($_POST['form_id']);
    //     $form_key = sanitize_key($_POST['form_key']);
    //     $content = wp_unslash($_POST['content']);
                                        
    //     if (!$form_id || !$form_key) {
    //         wp_send_json_error('Form ID and form key required');
    //     }
                                        
    //     $dir = $this->ensure_js_directory();
    //     $filename = $form_key . '.js';
    //     $filepath = $dir . $filename;
                                        
    //     $result = file_put_contents($filepath, $content);
                                        
    //     if ($result !== false) {
    //         global $wpdb;
    //         $table = $wpdb->prefix . 'my_login_forms';
    //         $wpdb->update($table, array('js_file' => $filename), array('id' => $form_id));
                                        
    //         wp_send_json_success(array(
    //             'message' => 'JS file created successfully',
    //             'filename' => $filename
    //         ));
    //     } else {
    //         wp_send_json_error('Failed to create JS file');
    //     }
    // }

    // /**
    //  * Delete js file
    //  */
    // public function delete_js() {
    //     check_ajax_referer('my_login_delete_form_js', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_key = sanitize_key($_POST['form_key']);
                                        
    //     if (!$form_key) {
    //         wp_send_json_error('Form key required');
    //     }
                                        
    //     $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
    //     $filepath = $dir . $form_key . '.js';
                                        
    //     if (file_exists($filepath)) {
    //         unlink($filepath);
    //     }
                                        
    //     wp_send_json_success('JS file deleted');
    // }

    // /**
    //  * Get js file
    //  */
    // public function get_js() {
    //     check_ajax_referer('my_login_get_form_js', 'nonce');
                                        
    //     if (!current_user_can('manage_options')) {
    //         wp_send_json_error('Unauthorized');
    //     }
                                        
    //     $form_key = sanitize_key($_POST['form_key']);
                                        
    //     if (!$form_key) {
    //         wp_send_json_error('Form key required');
    //     }
                                        
    //     $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
    //     $filepath = $dir . $form_key . '.js';
                                        
    //     if (file_exists($filepath)) {
    //         wp_send_json_success(array('content' => file_get_contents($filepath)));
    //     } else {
    //         wp_send_json_success(array('content' => ''));
    //     }
    // }

    /**
     * Save js file
     */
    public function save_js() {
        check_ajax_referer('my_login_save_form_js', 'nonce');
                                        
        if (!current_user_can('manage_options')) {
            wp_send_json_error(__('Unauthorized', 'my-login-form'));
        }
                                        
        $form_id = intval($_POST['form_id']);
        $js_content = wp_unslash($_POST['js_content']);
        $js_content = trim($js_content);
                                        
        if (!$form_id) {
            wp_send_json_error(__('Invalid form ID', 'my-login-form'));
        }
                                        
        global $wpdb;
        $table = $wpdb->prefix . 'my_login_forms';
                                        
        $js_dir = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
        $js_file_path = $js_dir . $form_id . '.js';
                                        
        if (!empty($js_content)) {
            // Save to file
            $this->ensure_js_directory();
            $result = file_put_contents($js_file_path, $js_content);
                                        
            if ($result !== false) {
                // Update database
                $wpdb->update($table, array('js_file' => $form_id . '.js'), array('id' => $form_id));
                wp_send_json_success(array(
                    'message' => 'JavaScript saved successfully',
                    'file_size' => $result,
                    'action' => 'saved'
                ));
            } else {
                wp_send_json_error(__('Failed to save JavaScript file', 'my-login-form'));
            }
        } else {
            // Delete file if empty
            if (file_exists($js_file_path)) {
                unlink($js_file_path);
            }
            // Update database to remove reference
            $wpdb->update($table, array('js_file' => ''), array('id' => $form_id));
            wp_send_json_success(array(
                'message' => 'JavaScript cleared and file deleted',
                'action' => 'deleted'
            ));
        }
    }


    /**
     * Create security files (.htaccess and index.php) in directory
     */
    private function create_security_files($dir) {
        // Create .htaccess
        $htaccess_file = $dir . '.htaccess';
        if (!file_exists($htaccess_file)) {
            $htaccess_content = "# Apache 2.4\n";
            $htaccess_content .= "<IfModule mod_authz_core.c>\n";
            $htaccess_content .= "    Require all denied\n";
            $htaccess_content .= "</IfModule>\n\n";
            $htaccess_content .= "# Apache 2.2\n";
            $htaccess_content .= "<IfModule !mod_authz_core.c>\n";
            $htaccess_content .= "    Order Deny,Allow\n";
            $htaccess_content .= "    Deny from all\n";
            $htaccess_content .= "</IfModule>\n\n";
            $htaccess_content .= "# Allow specific file types\n";
            $htaccess_content .= "<FilesMatch \"\.(html|css|js)$\">\n";
            $htaccess_content .= "    Require all granted\n";
            $htaccess_content .= "</FilesMatch>\n";
            file_put_contents($htaccess_file, $htaccess_content);
        }
                                        
        // Create index.php
        $index_file = $dir . 'index.php';
        if (!file_exists($index_file)) {
            file_put_contents($index_file, '<?php // Silence is golden');
        }
    }


    private function ensure_html_directory() {
    $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/html/';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
        $this->create_security_files($dir);
    }
    return $dir;
}
private function ensure_css_directory() {
    $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/css/';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
        $this->create_security_files($dir);
    }
    return $dir;
}

private function ensure_js_directory() {
    $dir = MY_LOGIN_FORM_DIR . 'Public/Forms/js/';
    if (!file_exists($dir)) {
        wp_mkdir_p($dir);
        $this->create_security_files($dir);
    }
    return $dir;
}



    private function get_field_type($field_name) {
        $types = [
            'first_name' => 'text',
            'last_name' => 'text', 
            'email' => 'email',
            'password' => 'password',
            'confirm_password' => 'password',
            'terms' => 'checkbox',
            'username' => 'text',
            'phone' => 'tel',
            'gender' => 'select',
            'dob' => 'date',
            'address' => 'textarea',
            'city' => 'text',
            'country' => 'text'
        ];
        return isset($types[$field_name]) ? $types[$field_name] : 'text';
    }

    private function get_field_label($field_name) {
        $labels = [
            'first_name' => 'First Name',
            'last_name' => 'Last Name',
            'email' => 'Email Address',
            'password' => 'Password',
            'confirm_password' => 'Confirm Password',
            'terms' => 'Terms & Conditions',
            'username' => 'Username',
            'phone' => 'Phone Number',
            'gender' => 'Gender',
            'dob' => 'Date of Birth',
            'address' => 'Address',
            'city' => 'City',
            'country' => 'Country'
        ];
        return isset($labels[$field_name]) ? $labels[$field_name] : ucfirst(str_replace('_', ' ', $field_name));
    }

}