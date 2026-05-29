<?php
/**
 * Form Designer Template - With Nested Sub Divs Support & Selective Customization
 * 
 * @package MyLoginForm
 */

// Prevent direct access
defined('ABSPATH') || exit;

global $wpdb;
$forms_table = $wpdb->prefix . 'my_login_forms';

// ============================================
// 1. GET ALL FORMS
// ============================================
$all_forms = $wpdb->get_results("SELECT * FROM $forms_table ORDER BY sort_order ASC, id DESC");
$forms = $all_forms;
$total_forms = is_array($forms) ? count($forms) : 0;

// ============================================
// 2. DEFINE FORM CONTAINERS
// ============================================
$form_container = [
    'main_container' => [
        'label' => __('Main Container', 'my-login-form'),
        'icon' => '📦',
        'type' => 'main_container'
    ],
    'sub_container' => [
        'label' => __('Sub Div', 'my-login-form'),
        'icon' => '🗂️',
        'type' => 'sub_div'
    ],
    'social_container' => [
        'label' => __('Social Container', 'my-login-form'),
        'icon' => '🔗',
        'type' => 'social_container'
    ],
];

// ============================================
// 3. DEFINE FORM FIELDS
// ============================================
$all_fields = [
    'text' => [
        'label' => __('Text Field', 'my-login-form'),
        'icon' => '📝',
        'type' => 'text',
        'html_type' => 'text'
    ],
    'first_name' => [
        'label' => __('First Name', 'my-login-form'),
        'icon' => '👤',
        'type' => 'first_name',
        'html_type' => 'text'
    ],
    'last_name' => [
        'label' => __('Last Name', 'my-login-form'),
        'icon' => '👥',
        'type' => 'last_name',
        'html_type' => 'text'
    ],
    'email' => [
        'label' => __('Email Field', 'my-login-form'),
        'icon' => '📧',
        'type' => 'email',
        'html_type' => 'email'
    ],
    'password' => [
        'label' => __('Password Field', 'my-login-form'),
        'icon' => '🔒',
        'type' => 'password',
        'html_type' => 'password'
    ],
    'confirm_password' => [
        'label' => __('Confirm Password Field', 'my-login-form'),
        'icon' => '🔒',
        'type' => 'password',
        'html_type' => 'password'
    ],
    'username' => [
        'label' => __('Username', 'my-login-form'),
        'icon' => '👤',
        'type' => 'username',
        'html_type' => 'text'
    ],
    'number' => [
        'label' => __('Number Field', 'my-login-form'),
        'icon' => '🔢',
        'type' => 'number',
        'html_type' => 'number'
    ],
    'date' => [
        'label' => __('Date Field', 'my-login-form'),
        'icon' => '📅',
        'type' => 'date',
        'html_type' => 'date'
    ],
    'textarea' => [
        'label' => __('Textarea', 'my-login-form'),
        'icon' => '📄',
        'type' => 'textarea',
        'html_type' => 'textarea'
    ],
    'select' => [
        'label' => __('Dropdown', 'my-login-form'),
        'icon' => '📋',
        'type' => 'select',
        'html_type' => 'select'
    ],
    'checkbox' => [
        'label' => __('Checkbox', 'my-login-form'),
        'icon' => '✅',
        'type' => 'checkbox',
        'html_type' => 'checkbox'
    ],
    'radio' => [
        'label' => __('Radio Button', 'my-login-form'),
        'icon' => '🔘',
        'type' => 'radio',
        'html_type' => 'radio'
    ],
    'phone' => [
        'label' => __('Phone Field', 'my-login-form'),
        'icon' => '📱',
        'type' => 'phone',
        'html_type' => 'tel'
    ],
];

// ============================================
// 4. WOOCOMMERCE FIELDS (if active)
// ============================================
if (class_exists('WooCommerce')) {
    $all_fields['billing_first_name'] = ['label' => __('Billing First Name', 'my-login-form'), 'icon' => '👤', 'type' => 'billing_first_name', 'html_type' => 'text'];
    $all_fields['billing_last_name'] = ['label' => __('Billing Last Name', 'my-login-form'), 'icon' => '👥', 'type' => 'billing_last_name', 'html_type' => 'text'];
    $all_fields['billing_company'] = ['label' => __('Billing Company', 'my-login-form'), 'icon' => '🏢', 'type' => 'billing_company', 'html_type' => 'text'];
    $all_fields['billing_address'] = ['label' => __('Billing Address', 'my-login-form'), 'icon' => '🏠', 'type' => 'billing_address', 'html_type' => 'text'];
    $all_fields['billing_city'] = ['label' => __('Billing City', 'my-login-form'), 'icon' => '🌆', 'type' => 'billing_city', 'html_type' => 'text'];
    $all_fields['billing_postcode'] = ['label' => __('Billing Postcode', 'my-login-form'), 'icon' => '📮', 'type' => 'billing_postcode', 'html_type' => 'text'];
    $all_fields['billing_email'] = ['label' => __('Billing Email', 'my-login-form'), 'icon' => '📧', 'type' => 'billing_email', 'html_type' => 'email'];
    $all_fields['billing_phone'] = ['label' => __('Billing Phone', 'my-login-form'), 'icon' => '📱', 'type' => 'billing_phone', 'html_type' => 'tel'];
}

// ============================================
// 5. SOCIAL PROVIDERS
// ============================================
$social_providers = [
    'google' => ['label' => 'Google', 'color' => '#DB4437'],
    'facebook' => ['label' => 'Facebook', 'color' => '#4267B2'],
    'twitter' => ['label' => 'Twitter', 'color' => '#1DA1F2'],
    'github' => ['label' => 'GitHub', 'color' => '#333333'],
    'linkedin' => ['label' => 'LinkedIn', 'color' => '#0077B5'],
];

// ============================================
// 6. CURRENT FORM ID & DATA
// ============================================
$current_form_id = isset($_GET['form_id']) ? intval($_GET['form_id']) : 0;
$selected_social = [];
$html_content = "";
$css_content = '';
$js_content = '';

// ============================================
// 7. FORM SETTINGS DEFAULTS
// ============================================
$form_settings = [
    'global' => [
        'btn_color' => ['type' => 'color', 'label' => 'Button Color', 'default' => '#0073aa'],
        'button_text' => ['type' => 'text', 'label' => 'Button Text', 'default' => 'Submit'],
        'show_labels' => ['type' => 'checkbox', 'label' => 'Show Field Labels', 'default' => false]
    ],
    'main_container' => [
        'layout' => [
            'display' => ['type' => 'select', 'label' => 'Display', 'options' => ['block', 'grid', 'flex'], 'default' => 'block', 'unit' => null],
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px', 'vw']],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 20, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'margin_bottom' => ['type' => 'slider', 'label' => 'Margin Bottom', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 20, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 16, 'unit' => 'px', 'units' => ['px', '%']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#667eea'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 10, 'step' => 1, 'default' => 2, 'unit' => 'px'],
            'box_shadow' => ['type' => 'select', 'label' => 'Box Shadow', 'options' => ['none', 'light', 'medium', 'strong'], 'default' => 'none']
        ]
    ],
    'sub_container' => [
        'layout' => [
            'display' => ['type' => 'select', 'label' => 'Display', 'options' => ['block', 'grid', 'flex'], 'default' => 'block', 'unit' => null],
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px', 'vw']],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 15, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'margin' => ['type' => 'slider', 'label' => 'Margin', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 10, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', '%']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#f8f9fa'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#f5576c'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 10, 'step' => 1, 'default' => 2, 'unit' => 'px'],
            'box_shadow' => ['type' => 'select', 'label' => 'Box Shadow', 'options' => ['none', 'light', 'medium', 'strong'], 'default' => 'none']
        ]
    ],
    'social_container' => [
        'layout' => [
            'display' => ['type' => 'select', 'label' => 'Display', 'options' => ['block', 'grid', 'flex'], 'default' => 'block', 'unit' => null],
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px', 'vw']],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 15, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'margin' => ['type' => 'slider', 'label' => 'Margin', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 10, 'unit' => 'px', 'units' => ['px', 'rem', 'em']],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', '%']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#667eea'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 10, 'step' => 1, 'default' => 2, 'unit' => 'px'],
            'box_shadow' => ['type' => 'select', 'label' => 'Box Shadow', 'options' => ['none', 'light', 'medium', 'strong'], 'default' => 'none']
        ]
    ],
    'field' => [
        'layout' => [
            'width' => ['type' => 'slider', 'label' => 'Width', 'min' => 0, 'max' => 100, 'step' => 1, 'default' => 100, 'unit' => '%', 'units' => ['%', 'px']],
            'margin_bottom' => ['type' => 'slider', 'label' => 'Margin Bottom', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', 'rem']],
            'label_font_size' => ['type' => 'slider', 'label' => 'Label Font Size', 'min' => 10, 'max' => 24, 'step' => 1, 'default' => 14, 'unit' => 'px', 'units' => ['px', 'rem']],
            'input_padding' => ['type' => 'slider', 'label' => 'Input Padding', 'min' => 5, 'max' => 30, 'step' => 1, 'default' => 10, 'unit' => 'px', 'units' => ['px', 'rem']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#ffffff'],
            'border_color' => ['type' => 'color', 'label' => 'Border Color', 'default' => '#e0e0e0'],
            'border_width' => ['type' => 'slider', 'label' => 'Border Width', 'min' => 0, 'max' => 5, 'step' => 1, 'default' => 1, 'unit' => 'px'],
            'text_color' => ['type' => 'color', 'label' => 'Text Color', 'default' => '#333333'],
            'label_color' => ['type' => 'color', 'label' => 'Label Color', 'default' => '#333333']
        ]
    ],
    'button' => [
        'layout' => [
            'width' => ['type' => 'select', 'label' => 'Width', 'options' => ['auto', 'full'], 'default' => 'auto', 'unit' => null],
            'padding' => ['type' => 'slider', 'label' => 'Padding', 'min' => 5, 'max' => 40, 'step' => 1, 'default' => 12, 'unit' => 'px', 'units' => ['px', 'rem']],
            'margin_top' => ['type' => 'slider', 'label' => 'Margin Top', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 15, 'unit' => 'px', 'units' => ['px', 'rem']]
        ],
        'customization' => [
            'background_color' => ['type' => 'color', 'label' => 'Background Color', 'default' => '#0073aa'],
            'text_color' => ['type' => 'color', 'label' => 'Text Color', 'default' => '#ffffff'],
            'hover_background_color' => ['type' => 'color', 'label' => 'Hover Background Color', 'default' => '#005a87'],
            'border_radius' => ['type' => 'slider', 'label' => 'Border Radius', 'min' => 0, 'max' => 50, 'step' => 1, 'default' => 8, 'unit' => 'px', 'units' => ['px', '%']]
        ]
    ]
];

// ============================================
// 8. LOAD EXISTING FORM DATA (if editing)
// ============================================
if ($current_form_id) {
    $current_form = $wpdb->get_row($wpdb->prepare("SELECT * FROM $forms_table WHERE id = %d", $current_form_id));
    if ($current_form) {
        $form_settings_data = json_decode($current_form->settings, true);
        if (is_array($form_settings_data)) {
            $form_settings = array_merge_recursive($form_settings, $form_settings_data);
            $selected_social = isset($form_settings_data['social_providers']) ? $form_settings_data['social_providers'] : [];
        }
        
        // Load file contents
        $css_content = $current_form->css_file ? @file_get_contents(MY_LOGIN_FORM_DIR . 'Public/Forms/css/' . $current_form->css_file) : '';
        $js_content = $current_form->js_file ? @file_get_contents(MY_LOGIN_FORM_DIR . 'Public/Forms/js/' . $current_form->js_file) : '';
        $html_content = $current_form->html_file ? @file_get_contents(MY_LOGIN_FORM_DIR . 'Public/Forms/html/' . $current_form->html_file) : '';
    }
}

// ============================================
// 9. EXTRACT VALUES FOR CLEAN HTML
// ============================================

// Global settings
$global_settings = $form_settings['global'];
$btn_color = $global_settings['btn_color']['default'];
$button_text = $global_settings['button_text']['default'];
$show_labels = $global_settings['show_labels']['default'];

// File contents
$css_file_content = $css_content;
$js_file_content = $js_content;
$html_file_content = $html_content;

// Get current form key if a form is selected
$current_form_key = '';
if ($current_form_id && isset($current_form)) {
    $current_form_key = $current_form->form_key;
}

// ============================================
// 10. CREATE NONCES & URLS (DEFINE FIRST)
// ============================================
$create_nonce = wp_create_nonce('my_login_create_form');
$delete_nonce = wp_create_nonce('my_login_delete_form');
$duplicate_nonce = wp_create_nonce('my_login_duplicate_form');
$get_nonce = wp_create_nonce('my_login_get_form');
$save_nonce = wp_create_nonce('my_login_save_form_settings');
$save_css_nonce = wp_create_nonce('my_login_save_form_css');
$save_js_nonce = wp_create_nonce('my_login_save_form_js');
$save_html_nonce = wp_create_nonce('my_login_save_form_html');

// URLs
$admin_ajax_url = admin_url('admin-ajax.php');
$designer_js_url = MY_LOGIN_FORM_URL . 'Admin/Pages/js/designer.js';
$designer_css_url = MY_LOGIN_FORM_URL . 'Admin/Pages/css/designer.css';

// ============================================
// 11. PREPARE DATA FOR JAVASCRIPT
// ============================================
$designer_data = [
    'ajax_url' => $admin_ajax_url,
    'nonces' => [
        'create_form' => $create_nonce,
        'delete_form' => $delete_nonce,
        'duplicate_form' => $duplicate_nonce,
        'get_form' => $get_nonce,
        'save_form' => $save_nonce,
        'save_css' => $save_css_nonce,
        'save_js' => $save_js_nonce,
        'save_html' => $save_html_nonce,
    ],
    'current_form_id' => $current_form_id,
    'current_form_key' => $current_form_key,
    'form_settings' => $form_settings,
    'social_providers' => $social_providers,
    'all_fields' => $all_fields,
    'form_container' => $form_container,
    'total_forms' => $total_forms,
    'forms' => $forms,
    'strings' => [
        'select_form' => __('Select a form first', 'my-login-form'),
        'form_saved' => __('Form saved!', 'my-login-form'),
        'css_saved' => __('CSS saved!', 'my-login-form'),
        'js_saved' => __('JavaScript saved!', 'my-login-form'),
        'duplicate_confirm' => __('Duplicate this form?', 'my-login-form'),
        'delete_confirm' => __('Delete this form?', 'my-login-form'),
        'clear_confirm' => __('Clear all fields?', 'my-login-form'),
    ]
];
?>

<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?php _e('Form Designer', 'my-login-form'); ?></title>
    <?php wp_head(); ?>
    <link rel="stylesheet" href="https://code.jquery.com/ui/1.12.1/themes/base/jquery-ui.css">
    <link rel="stylesheet" href="<?php echo esc_url($designer_css_url); ?>">
</head>
<body>
<div class="wrap">
    <!-- HEADER -->
    <div class="designer-header">
        <h1>🎨 <?php _e('Form Designer', 'my-login-form'); ?></h1>
        <div class="header-actions">
            <button id="createNewFormBtn" class="button-primary">+ <?php _e('Create New Form', 'my-login-form'); ?></button>
            <button id="saveFormBtn" class="button-primary">💾 <?php _e('Save Form', 'my-login-form'); ?></button>
            <span>📋 <?php echo (int) $total_forms; ?> <?php _e('Forms', 'my-login-form'); ?></span>
        </div>
    </div>
    


    <div class="designer-columns">
        
        <!-- LEFT COLUMN -->
        <div class="left-column">
            
            <!-- FORM LIST -->
            <div class="form-list-section">
                <h3>📋 <?php _e('Form List', 'my-login-form'); ?></h3>
                <div id="formList">
                    <?php if (empty($forms)): ?>
                        <div class="empty-state"><?php _e('No forms created yet', 'my-login-form'); ?></div>
                    <?php else: ?>
                        <?php foreach ($forms as $form): ?>
                            <div class="form-item <?php echo ($current_form_id == $form->id) ? 'active' : ''; ?>" data-form-id="<?php echo (int) $form->id; ?>">
                                <span class="form-name">📝 <?php echo esc_html($form->name); ?></span>
                                <div class="form-actions">
                                    <button class="copy-shortcode" data-form-id="<?php echo (int) $form->id; ?>">📋</button>
                                    <?php if ($form->is_system != 1): ?>
                                        <button class="duplicate-form" data-form-id="<?php echo (int) $form->id; ?>">📝</button>
                                        <button class="delete-form" data-form-id="<?php echo (int) $form->id; ?>">🗑️</button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- FIELDS PALETTE -->
            <div class="fields-palette">
                <div class="palette-category">
                    <h4>📦 <?php _e('Containers', 'my-login-form'); ?></h4>
                    <div class="draggable-field main-draggable" data-drag-type="main_container">
                        <span>📦</span> <span><?php _e('Main Container', 'my-login-form'); ?></span>
                    </div>
                    <div class="draggable-field subdiv-draggable" data-drag-type="sub_div">
                        <span>🗂️</span> <span><?php _e('Sub Div', 'my-login-form'); ?></span>
                    </div>
                </div>
                
                <div class="palette-category">
                    <h4>📝 <?php _e('Form Fields', 'my-login-form'); ?></h4>
                    <?php foreach ($all_fields as $key => $field): ?>
                        <div class="draggable-field" data-drag-type="field" data-field-type="<?php echo esc_attr($key); ?>" data-field-label="<?php echo esc_attr($field['label']); ?>" data-field-html-type="<?php echo esc_attr($field['html_type']); ?>">
                            <span><?php echo esc_html($field['icon']); ?></span> <span><?php echo esc_html($field['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
                
                <div class="palette-category">
                    <h4>🔗 <?php _e('Social Login Providers', 'my-login-form'); ?></h4>
                    <?php foreach ($social_providers as $key => $provider): ?>
                        <div class="draggable-field" data-drag-type="social" data-social-provider="<?php echo esc_attr($key); ?>">
                            <span>🔗</span> <span><?php echo esc_html($provider['label']); ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        
        <!-- RIGHT COLUMN -->
        <div class="right-column">
            
            <!-- FORM BUILDER AREA -->
            <div class="form-builder-area">
                <div class="builder-header">
                    <h2>✏️ <?php _e('Form Builder', 'my-login-form'); ?></h2>
                    <button id="clearAllBtn" class="button-primary"><?php _e('Clear All', 'my-login-form'); ?></button>
                </div>
                <div id="formBuilder">
                    <div class="builder-placeholder">📦 <?php _e('Drag a Main Container here to start', 'my-login-form'); ?></div>
                </div>
            </div>
            
            <!-- TABS CONTAINER -->
            <div class="tabs-container">
                <div id="selectionInfo" class="selection-info" style="display: none;">
                    <span>📌 <?php _e('Selected:', 'my-login-form'); ?> <strong id="selectedItemName"><?php _e('Nothing', 'my-login-form'); ?></strong></span>
                    <button id="clearSelectionBtn" class="clear-selection"><?php _e('Clear Selection', 'my-login-form'); ?></button>
                </div>
                
                <div class="tabs-header">
                    <button class="tab-btn" data-tab="layout">📐 <?php _e('Layout', 'my-login-form'); ?></button>
                    <button class="tab-btn" data-tab="customization">🎨 <?php _e('Customization', 'my-login-form'); ?></button>
                    <button class="tab-btn active" data-tab="css">🎨 <?php _e('CSS', 'my-login-form'); ?></button>
                    <button class="tab-btn" data-tab="js">⚡ <?php _e('JavaScript', 'my-login-form'); ?></button>
                    <button class="tab-btn" data-tab="settings">⚙️ <?php _e('Settings', 'my-login-form'); ?></button>
                </div>
                
                <!-- LAYOUT TAB -->
                <div class="tab-content" id="tab-layout">
                    <div id="layoutContent">
                        <div class="settings-group">
                            <p class="placeholder-message">📌 <?php _e('Select a field, sub-div, or main container to enable layout customization', 'my-login-form'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- CUSTOMIZATION TAB -->
                <div class="tab-content" id="tab-customization">
                    <div id="customizationContent">
                        <div class="settings-group">
                            <p class="placeholder-message">🎨 <?php _e('Select a field, sub-div, or main container to enable style customization', 'my-login-form'); ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- CSS TAB -->
                <div class="tab-content active" id="tab-css">
                    <div class="settings-group">
                        <h4>🎨 <?php _e('Global CSS for Entire Form', 'my-login-form'); ?></h4>
                        <textarea id="customCSS" class="code-editor" placeholder="/* <?php _e('Your custom CSS for the entire form', 'my-login-form'); ?> */"><?php echo esc_textarea($css_file_content); ?></textarea>
                        <button id="saveCSSBtn" class="button-primary">💾 <?php _e('Save CSS', 'my-login-form'); ?></button>
                    </div>
                </div>
                
                <!-- JAVASCRIPT TAB -->
                <div class="tab-content" id="tab-js">
                    <div class="settings-group">
                        <h4>⚡ <?php _e('Global JavaScript for Entire Form', 'my-login-form'); ?></h4>
                        <textarea id="customJS" class="code-editor" placeholder="// <?php _e('Your custom JavaScript for the entire form', 'my-login-form'); ?>"><?php echo esc_textarea($js_file_content); ?></textarea>
                        <button id="saveJSBtn" class="button-primary">💾 <?php _e('Save JavaScript', 'my-login-form'); ?></button>
                    </div>
                </div>
                
                <!-- SETTINGS TAB -->
                <div class="tab-content" id="tab-settings">
                    <div class="settings-group">
                        <h4>🎨 <?php _e('Form Settings', 'my-login-form'); ?></h4>
                        <div class="setting-field">
                            <label><?php _e('Button Color', 'my-login-form'); ?></label>
                            <input type="color" id="btnColor" value="<?php echo esc_attr($btn_color); ?>">
                        </div>
                        <div class="setting-field">
                            <label><?php _e('Button Text', 'my-login-form'); ?></label>
                            <input type="text" id="buttonText" value="<?php echo esc_attr($button_text); ?>">
                        </div>
                        <div class="setting-field">
                            <label>
                                <input type="checkbox" id="showLabels" <?php echo $show_labels ? 'checked' : ''; ?>>
                                <?php _e('Show field labels', 'my-login-form'); ?>
                            </label>
                        </div>
                    </div>
                    <div class="settings-group">
                        <h4>🎨 <?php _e('Presets', 'my-login-form'); ?></h4>
                        <div class="preset-buttons">
                            <button class="preset-btn" data-preset="modern"><?php _e('Modern', 'my-login-form'); ?></button>
                            <button class="preset-btn" data-preset="minimal"><?php _e('Minimal', 'my-login-form'); ?></button>
                            <button class="preset-btn" data-preset="dark"><?php _e('Dark', 'my-login-form'); ?></button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALS -->
<div id="fieldSettingsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>⚙️ <?php _e('Field Settings', 'my-login-form'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editingFieldId">
            <div class="form-group">
                <label><?php _e('Placeholder', 'my-login-form'); ?></label>
                <input type="text" id="fieldPlaceholder">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" id="fieldRequired">
                    <?php _e('Required field', 'my-login-form'); ?>
                </label>
            </div>
        </div>
        <div class="modal-footer">
            <button class="cancel-modal"><?php _e('Cancel', 'my-login-form'); ?></button>
            <button id="saveFieldSettingsBtn" class="button-primary"><?php _e('Save', 'my-login-form'); ?></button>
        </div>
    </div>
</div>

<div id="containerSettingsModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3>📦 <?php _e('Container Settings', 'my-login-form'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <input type="hidden" id="editingContainerId">
            <div class="form-group">
                <label><?php _e('Background Color', 'my-login-form'); ?></label>
                <input type="color" id="containerBgColor" value="#ffffff">
            </div>
        </div>
        <div class="modal-footer">
            <button class="cancel-modal"><?php _e('Cancel', 'my-login-form'); ?></button>
            <button id="saveContainerSettingsBtn" class="button-primary"><?php _e('Save', 'my-login-form'); ?></button>
        </div>
    </div>
</div>

<div id="createFormModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h3><?php _e('Create New Form', 'my-login-form'); ?></h3>
            <button class="close-modal">&times;</button>
        </div>
        <div class="modal-body">
            <div class="form-group">
                <label><?php _e('Form Name', 'my-login-form'); ?></label>
                <input type="text" id="newFormName">
            </div>
            <div class="form-group">
                <label><?php _e('Form Type', 'my-login-form'); ?></label>
                <select id="newFormType">
                    <option value="custom"><?php _e('Custom', 'my-login-form'); ?></option>
                    <option value="login"><?php _e('Login', 'my-login-form'); ?></option>
                    <option value="register"><?php _e('Register', 'my-login-form'); ?></option>
                </select>
            </div>
        </div>
        <div class="modal-footer">
            <button class="cancel-modal"><?php _e('Cancel', 'my-login-form'); ?></button>
            <button id="createFormSubmitBtn" class="button-primary"><?php _e('Create', 'my-login-form'); ?></button>
        </div>
    </div>
</div>
<!-- At the bottom of the body, before </body> -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.12.1/jquery-ui.min.js"></script>

<script>
var MyLoginDesigner = <?php echo json_encode($designer_data); ?>;
</script>

<script src="<?php echo esc_url($designer_js_url); ?>"></script>
</body>
</html>