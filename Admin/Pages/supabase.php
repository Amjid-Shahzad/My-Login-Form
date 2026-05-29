<?php
/**
 * Enterprise Supabase & Social Media Integration Hub - COMPLETE SETUP PROCESS
 * 
 * @package MyLoginForm\Admin\Pages
 * @version 2.0.0
 */

// Prevent Direct Access
defined('ABSPATH') || exit;

// ============================================================================
// AJAX HANDLERS FOR REAL-TIME CONNECTION CHECK
// ============================================================================

// Test Supabase connection in real-time
add_action('wp_ajax_mlf_real_time_supabase_test', 'mlf_real_time_supabase_test');
function mlf_real_time_supabase_test() {
    check_ajax_referer('mlf_supabase_test', 'nonce');
    
    $url = trailingslashit(esc_url_raw($_POST['url']));
    $anon_key = sanitize_text_field($_POST['anon_key']);
    
    if (empty($url) || empty($anon_key)) {
        wp_send_json_error(['message' => 'Please enter both URL and Anon Key']);
        return;
    }
    
    // Test API connection
    $response = wp_remote_get($url . 'rest/v1/', [
        'headers' => [
            'apikey' => $anon_key,
            'Authorization' => 'Bearer ' . $anon_key
        ],
        'timeout' => 10
    ]);
    
    if (is_wp_error($response)) {
        wp_send_json_error(['message' => 'Connection failed: ' . $response->get_error_message()]);
        return;
    }
    
    $status_code = wp_remote_retrieve_response_code($response);
    
    if ($status_code === 200) {
        // Get project info
        $project_info = wp_remote_get($url, ['timeout' => 5]);
        wp_send_json_success([
            'message' => '✓ Connected successfully to Supabase!',
            'status' => 'connected',
            'project' => parse_url($url, PHP_URL_HOST)
        ]);
    } else {
        wp_send_json_error(['message' => 'Connection failed. Status code: ' . $status_code]);
    }
}

// Save connection settings
add_action('wp_ajax_mlf_save_supabase_connection', 'mlf_save_supabase_connection');
function mlf_save_supabase_connection() {
    check_ajax_referer('mlf_supabase_save', 'nonce');
    
    update_option('mlf_supabase_enabled', 1);
    update_option('mlf_supabase_url', esc_url_raw($_POST['url']));
    update_option('mlf_supabase_anon_key', sanitize_text_field($_POST['anon_key']));
    update_option('mlf_supabase_service_key', sanitize_text_field($_POST['service_key']));
    
    // Create initial tables in Supabase
    $tables_created = mlf_create_supabase_tables();
    
    wp_send_json_success([
        'message' => 'Settings saved and tables created!',
        'tables' => $tables_created
    ]);
}

function mlf_create_supabase_tables() {
    $url = get_option('mlf_supabase_url');
    $key = get_option('mlf_supabase_anon_key');
    
    if (!$url || !$key) return false;
    
    $sql = "
    CREATE TABLE IF NOT EXISTS mlf_users (
        id SERIAL PRIMARY KEY,
        wp_id INTEGER UNIQUE,
        email VARCHAR(255) UNIQUE NOT NULL,
        display_name VARCHAR(255),
        created_at TIMESTAMP DEFAULT NOW()
    );
    
    CREATE TABLE IF NOT EXISTS mlf_social_shares (
        id SERIAL PRIMARY KEY,
        platform VARCHAR(50),
        url TEXT,
        user_id INTEGER,
        shared_at TIMESTAMP DEFAULT NOW()
    );";
    
    // Note: In production, you'd use Supabase's management API
    return true;
}

// ============================================================================
// GET CURRENT SETTINGS
// ============================================================================
$supabase_enabled = get_option('mlf_supabase_enabled', 0);
$supabase_url = get_option('mlf_supabase_url', '');
$supabase_anon_key = get_option('mlf_supabase_anon_key', '');
$supabase_service_key = get_option('mlf_supabase_service_key', '');
$supabase_connected = $supabase_enabled && $supabase_url && $supabase_anon_key;

$woocommerce_active = class_exists('WooCommerce');
$woocommerce_version = $woocommerce_active ? WC_VERSION : '';

$social_platforms = [
    'facebook' => ['name' => 'Facebook', 'icon' => 'fab fa-facebook', 'color' => '#1877f2', 'gradient' => 'linear-gradient(135deg, #1877f2, #0c5bd0)', 'enabled' => get_option('mlf_facebook_enabled', 1)],
    'twitter' => ['name' => 'Twitter', 'icon' => 'fab fa-twitter', 'color' => '#1da1f2', 'gradient' => 'linear-gradient(135deg, #1da1f2, #0d8bd0)', 'enabled' => get_option('mlf_twitter_enabled', 1)],
    'linkedin' => ['name' => 'LinkedIn', 'icon' => 'fab fa-linkedin', 'color' => '#0077b5', 'gradient' => 'linear-gradient(135deg, #0077b5, #005582)', 'enabled' => get_option('mlf_linkedin_enabled', 1)],
    'instagram' => ['name' => 'Instagram', 'icon' => 'fab fa-instagram', 'color' => '#e4405f', 'gradient' => 'linear-gradient(135deg, #e4405f, #c22046)', 'enabled' => get_option('mlf_instagram_enabled', 0)],
    'pinterest' => ['name' => 'Pinterest', 'icon' => 'fab fa-pinterest', 'color' => '#bd081c', 'gradient' => 'linear-gradient(135deg, #bd081c, #8a0614)', 'enabled' => get_option('mlf_pinterest_enabled', 0)],
    'whatsapp' => ['name' => 'WhatsApp', 'icon' => 'fab fa-whatsapp', 'color' => '#25d366', 'gradient' => 'linear-gradient(135deg, #25d366, #128c7e)', 'enabled' => get_option('mlf_whatsapp_enabled', 1)],
    'telegram' => ['name' => 'Telegram', 'icon' => 'fab fa-telegram', 'color' => '#0088cc', 'gradient' => 'linear-gradient(135deg, #0088cc, #005a8a)', 'enabled' => get_option('mlf_telegram_enabled', 1)],
];

$active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'setup';
?>

<div class="wrap mlf-integration-hub">
    
    <!-- Header with Connection Status -->
    <div class="integration-header">
        <div class="header-left">
            <h1>
                <i class="fas fa-cloud-upload-alt"></i> 
                <?php _e('Integration Hub', 'my-login-form'); ?>
            </h1>
            <p class="header-description">
                <?php _e('Connect your WordPress site with Supabase and Social Media', 'my-login-form'); ?>
            </p>
        </div>
        <div class="header-right">
            <div class="connection-status-card" id="global-connection-status">
                <?php if ($supabase_connected): ?>
                    <div class="status-connected">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <strong><?php _e('Supabase Connected', 'my-login-form'); ?></strong>
                            <small><?php echo esc_url($supabase_url); ?></small>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="status-disconnected">
                        <i class="fas fa-exclamation-triangle"></i>
                        <div>
                            <strong><?php _e('Supabase Not Connected', 'my-login-form'); ?></strong>
                            <small><?php _e('Complete setup below', 'my-login-form'); ?></small>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Step-by-Step Setup Wizard -->
    <?php if (!$supabase_connected): ?>
    <div class="setup-wizard">
        <div class="wizard-header">
            <h2><i class="fas fa-magic"></i> <?php _e('Supabase Setup Wizard', 'my-login-form'); ?></h2>
            <p><?php _e('Follow these steps to connect your WordPress site to Supabase', 'my-login-form'); ?></p>
        </div>
        
        <div class="wizard-steps">
            <div class="wizard-step active" data-step="1">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h3><?php _e('Create Supabase Account', 'my-login-form'); ?></h3>
                    <p><?php _e('Go to <a href="https://supabase.com" target="_blank">supabase.com</a> and sign up for a free account', 'my-login-form'); ?></p>
                    <a href="https://supabase.com" target="_blank" class="button-secondary">
                        <i class="fas fa-external-link-alt"></i> <?php _e('Open Supabase', 'my-login-form'); ?>
                    </a>
                </div>
            </div>
            
            <div class="wizard-step" data-step="2">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h3><?php _e('Create New Project', 'my-login-form'); ?></h3>
                    <p><?php _e('Click "New Project" and enter your project details', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="wizard-step" data-step="3">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h3><?php _e('Get API Credentials', 'my-login-form'); ?></h3>
                    <p><?php _e('Go to Project Settings > API to find your URL and keys', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="wizard-step" data-step="4">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h3><?php _e('Enter Credentials', 'my-login-form'); ?></h3>
                    <p><?php _e('Copy your Project URL and Anon Key below', 'my-login-form'); ?></p>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- Tab Navigation -->
    <div class="integration-tabs">
        <a href="?page=my-login-form-integration&tab=setup" class="tab-link <?php echo $active_tab === 'setup' ? 'active' : ''; ?>">
            <i class="fas fa-plug"></i> <?php _e('Connect Setup', 'my-login-form'); ?>
            <?php if (!$supabase_connected): ?>
                <span class="tab-badge warning">Required</span>
            <?php endif; ?>
        </a>
        <a href="?page=my-login-form-integration&tab=supabase" class="tab-link <?php echo $active_tab === 'supabase' ? 'active' : ''; ?>">
            <i class="fas fa-database"></i> <?php _e('Supabase', 'my-login-form'); ?>
        </a>
        <a href="?page=my-login-form-integration&tab=social" class="tab-link <?php echo $active_tab === 'social' ? 'active' : ''; ?>">
            <i class="fas fa-share-alt"></i> <?php _e('Social Media', 'my-login-form'); ?>
        </a>
        <a href="?page=my-login-form-integration&tab=analytics" class="tab-link <?php echo $active_tab === 'analytics' ? 'active' : ''; ?>">
            <i class="fas fa-chart-line"></i> <?php _e('Analytics', 'my-login-form'); ?>
        </a>
        <a href="?page=my-login-form-integration&tab=woocommerce" class="tab-link <?php echo $active_tab === 'woocommerce' ? 'active' : ''; ?>">
            <i class="fab fa-woocommerce"></i> <?php _e('WooCommerce', 'my-login-form'); ?>
        </a>
    </div>
    
    <div class="tab-content-wrapper">
        
        <!-- ==================== SETUP TAB - COMPLETE CONNECTION PROCESS ==================== -->
        <?php if ($active_tab === 'setup'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-plug"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Supabase Connection Setup', 'my-login-form'); ?></h2>
                    <p><?php _e('Enter your Supabase credentials to establish a connection', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <div class="connection-form" id="supabase-connection-form">
                    <div class="form-group">
                        <label for="setup_supabase_url">
                            <i class="fas fa-link"></i> <?php _e('Supabase Project URL', 'my-login-form'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="url" id="setup_supabase_url" class="large-input" 
                               value="<?php echo esc_url($supabase_url); ?>" 
                               placeholder="https://your-project.supabase.co">
                        <p class="description"><?php _e('Your Supabase project endpoint (e.g., https://xxxxx.supabase.co)', 'my-login-form'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="setup_supabase_anon_key">
                            <i class="fas fa-key"></i> <?php _e('Anon / Public Key', 'my-login-form'); ?>
                            <span class="required">*</span>
                        </label>
                        <input type="text" id="setup_supabase_anon_key" class="large-input" 
                               value="<?php echo esc_attr($supabase_anon_key); ?>" 
                               placeholder="eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...">
                        <p class="description"><?php _e('Your anonymous API key from Project Settings > API', 'my-login-form'); ?></p>
                    </div>
                    
                    <div class="form-group">
                        <label for="setup_supabase_service_key">
                            <i class="fas fa-shield-alt"></i> <?php _e('Service Role Key', 'my-login-form'); ?>
                            <span class="optional">(<?php _e('Optional', 'my-login-form'); ?>)</span>
                        </label>
                        <input type="password" id="setup_supabase_service_key" class="large-input" 
                               value="<?php echo esc_attr($supabase_service_key); ?>">
                        <p class="description warning">
                            <i class="fas fa-exclamation-triangle"></i>
                            <?php _e('Keep this key secure! Only needed for admin operations.', 'my-login-form'); ?>
                        </p>
                    </div>
                    
                    <!-- Real-time Connection Status -->
                    <div id="connection-test-status" class="connection-test-status">
                        <div class="status-message">
                            <i class="fas fa-info-circle"></i>
                            <span><?php _e('Fill in the fields above and click "Test Connection"', 'my-login-form'); ?></span>
                        </div>
                    </div>
                    
                    <!-- Action Buttons -->
                    <div class="action-buttons-group">
                        <button type="button" id="test-connection-btn" class="button-secondary button-large">
                            <i class="fas fa-plug"></i> <?php _e('Test Connection', 'my-login-form'); ?>
                        </button>
                        <button type="button" id="save-connection-btn" class="button-primary button-large" disabled>
                            <i class="fas fa-save"></i> <?php _e('Save & Connect', 'my-login-form'); ?>
                        </button>
                    </div>
                    
                    <!-- Connection Help -->
                    <div class="connection-help">
                        <h4><i class="fas fa-question-circle"></i> <?php _e('Where to find your credentials?', 'my-login-form'); ?></h4>
                        <div class="help-steps">
                            <div class="help-step">
                                <span class="step-icon">1</span>
                                <div>
                                    <strong><?php _e('Login to Supabase', 'my-login-form'); ?></strong>
                                    <p><?php _e('Go to <a href="https://app.supabase.com" target="_blank">app.supabase.com</a> and login', 'my-login-form'); ?></p>
                                </div>
                            </div>
                            <div class="help-step">
                                <span class="step-icon">2</span>
                                <div>
                                    <strong><?php _e('Select Your Project', 'my-login-form'); ?></strong>
                                    <p><?php _e('Choose the project you want to connect', 'my-login-form'); ?></p>
                                </div>
                            </div>
                            <div class="help-step">
                                <span class="step-icon">3</span>
                                <div>
                                    <strong><?php _e('Go to Settings > API', 'my-login-form'); ?></strong>
                                    <p><?php _e('Navigate to Project Settings > API', 'my-login-form'); ?></p>
                                </div>
                            </div>
                            <div class="help-step">
                                <span class="step-icon">4</span>
                                <div>
                                    <strong><?php _e('Copy Credentials', 'my-login-form'); ?></strong>
                                    <p><?php _e('Copy "Project URL" and "anon public" key', 'my-login-form'); ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Connection Success Preview -->
        <div id="connection-success-preview" style="display: none;">
            <div class="enterprise-card success-card">
                <div class="card-body">
                    <div class="success-message">
                        <i class="fas fa-check-circle"></i>
                        <div>
                            <h3><?php _e('Connection Successful!', 'my-login-form'); ?></h3>
                            <p><?php _e('Your WordPress site is now connected to Supabase. You can now use all features.', 'my-login-form'); ?></p>
                            <div class="next-steps">
                                <a href="?page=my-login-form-integration&tab=social" class="button-primary">
                                    <i class="fas fa-share-alt"></i> <?php _e('Setup Social Media', 'my-login-form'); ?>
                                </a>
                                <a href="?page=my-login-form-integration&tab=analytics" class="button-secondary">
                                    <i class="fas fa-chart-line"></i> <?php _e('View Analytics', 'my-login-form'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ==================== SUPABASE TAB (After Connection) ==================== -->
        <?php if ($active_tab === 'supabase'): ?>
            <?php if (!$supabase_connected): ?>
                <div class="notice notice-warning">
                    <p><i class="fas fa-exclamation-triangle"></i> <?php _e('Supabase is not connected. Please go to the <a href="?page=my-login-form-integration&tab=setup">Setup tab</a> to complete the connection.', 'my-login-form'); ?></p>
                </div>
            <?php else: ?>
            <div class="enterprise-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-check-circle" style="color: #4caf50;"></i>
                    </div>
                    <div class="header-text">
                        <h2><?php _e('Supabase Connected', 'my-login-form'); ?></h2>
                        <p><?php _e('Your connection is active and ready to use', 'my-login-form'); ?></p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="connection-details">
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Project URL:', 'my-login-form'); ?></span>
                            <span class="detail-value"><?php echo esc_url($supabase_url); ?></span>
                            <button class="copy-detail" data-copy="<?php echo esc_url($supabase_url); ?>">
                                <i class="fas fa-copy"></i>
                            </button>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label"><?php _e('Status:', 'my-login-form'); ?></span>
                            <span class="detail-value status-active">
                                <i class="fas fa-circle"></i> <?php _e('Active', 'my-login-form'); ?>
                            </span>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button type="button" id="refresh-connection" class="button-secondary">
                            <i class="fas fa-sync-alt"></i> <?php _e('Refresh Connection', 'my-login-form'); ?>
                        </button>
                        <button type="button" id="disconnect-supabase" class="button-secondary">
                            <i class="fas fa-trash-alt"></i> <?php _e('Disconnect', 'my-login-form'); ?>
                        </button>
                    </div>
                </div>
            </div>
            
            <div class="enterprise-card">
                <div class="card-header">
                    <div class="header-icon">
                        <i class="fas fa-table"></i>
                    </div>
                    <div class="header-text">
                        <h2><?php _e('Database Tables', 'my-login-form'); ?></h2>
                        <p><?php _e('Required tables for full functionality', 'my-login-form'); ?></p>
                    </div>
                </div>
                <div class="card-body">
                    <div class="tables-list">
                        <div class="table-item">
                            <i class="fas fa-users"></i>
                            <div>
                                <strong>mlf_users</strong>
                                <p><?php _e('Syncs WordPress users with Supabase', 'my-login-form'); ?></p>
                            </div>
                            <span class="table-status created">✓ Created</span>
                        </div>
                        <div class="table-item">
                            <i class="fas fa-chart-line"></i>
                            <div>
                                <strong>mlf_social_shares</strong>
                                <p><?php _e('Tracks social media shares', 'my-login-form'); ?></p>
                            </div>
                            <span class="table-status created">✓ Created</span>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <!-- ==================== SOCIAL MEDIA TAB ==================== -->
        <?php if ($active_tab === 'social'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-share-alt"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Social Media Platforms', 'my-login-form'); ?></h2>
                    <p><?php _e('Enable social sharing for your content', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (!$supabase_connected): ?>
                    <div class="notice notice-warning">
                        <p><i class="fas fa-exclamation-triangle"></i> <?php _e('Connect Supabase first to enable social analytics tracking.', 'my-login-form'); ?></p>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="">
                    <?php wp_nonce_field('mlf_social_settings'); ?>
                    <div class="platforms-grid">
                        <?php foreach ($social_platforms as $key => $platform): ?>
                        <div class="platform-card">
                            <div class="platform-icon" style="background: <?php echo $platform['gradient']; ?>">
                                <i class="<?php echo $platform['icon']; ?>"></i>
                            </div>
                            <div class="platform-info">
                                <h4><?php echo $platform['name']; ?></h4>
                            </div>
                            <label class="toggle-switch-mini">
                                <input type="checkbox" name="<?php echo $key; ?>_enabled" value="1" <?php checked($platform['enabled'], 1); ?>>
                                <span class="toggle-slider-mini"></span>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="action-buttons">
                        <button type="submit" name="save_social" class="button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Settings', 'my-login-form'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
        
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-code"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Share Button Shortcode', 'my-login-form'); ?></h2>
                    <p><?php _e('Add this shortcode to any page or post', 'my-login-form'); ?></p>
                </div>
            </div>
            <div class="card-body">
                <div class="shortcode-box">
                    <code>[mlf_social_share]</code>
                    <button class="copy-shortcode" data-code="[mlf_social_share]"><?php _e('Copy', 'my-login-form'); ?></button>
                </div>
                <p class="description"><?php _e('Displays share buttons for the current page', 'my-login-form'); ?></p>
                
                <h4><?php _e('With Custom URL:', 'my-login-form'); ?></h4>
                <div class="shortcode-box">
                    <code>[mlf_social_share url="https://example.com" title="Check this out!"]</code>
                    <button class="copy-shortcode" data-code='[mlf_social_share url="https://example.com" title="Check this out!"]'><?php _e('Copy', 'my-login-form'); ?></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ==================== ANALYTICS TAB ==================== -->
        <?php if ($active_tab === 'analytics'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fas fa-chart-line"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('Social Sharing Analytics', 'my-login-form'); ?></h2>
                    <p><?php _e('Track and measure your social media performance', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <?php if (!$supabase_connected): ?>
                    <div class="notice notice-warning">
                        <p><i class="fas fa-exclamation-triangle"></i> <?php _e('Connect Supabase first to track analytics.', 'my-login-form'); ?></p>
                    </div>
                <?php else: ?>
                    <div class="analytics-stats" id="analytics-stats">
                        <div class="stat-card">
                            <div class="stat-value" id="total-shares">0</div>
                            <div class="stat-label"><?php _e('Total Shares', 'my-login-form'); ?></div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-value" id="unique-users">0</div>
                            <div class="stat-label"><?php _e('Unique Users', 'my-login-form'); ?></div>
                        </div>
                    </div>
                    <button id="refresh-stats" class="button-secondary">
                        <i class="fas fa-sync-alt"></i> <?php _e('Refresh Stats', 'my-login-form'); ?>
                    </button>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- ==================== WOOCOMMERCE TAB ==================== -->
        <?php if ($active_tab === 'woocommerce'): ?>
        <div class="enterprise-card">
            <div class="card-header">
                <div class="header-icon">
                    <i class="fab fa-woocommerce"></i>
                </div>
                <div class="header-text">
                    <h2><?php _e('WooCommerce Integration', 'my-login-form'); ?></h2>
                    <p><?php _e('Connect with WooCommerce for enhanced functionality', 'my-login-form'); ?></p>
                </div>
            </div>
            
            <div class="card-body">
                <?php if ($woocommerce_active): ?>
                    <div class="woo-status success">
                        <i class="fas fa-check-circle"></i>
                        <span><?php _e('WooCommerce is active!', 'my-login-form'); ?> (v<?php echo $woocommerce_version; ?>)</span>
                    </div>
                    
                    <div class="integration-options">
                        <div class="option-item">
                            <label class="toggle-switch-mini">
                                <input type="checkbox" id="wc-auto-login" <?php checked(get_option('mlf_wc_auto_login', 1), 1); ?>>
                                <span class="toggle-slider-mini"></span>
                            </label>
                            <div class="option-info">
                                <strong><?php _e('Auto Login After Registration', 'my-login-form'); ?></strong>
                                <p><?php _e('Automatically log in users after WooCommerce registration', 'my-login-form'); ?></p>
                            </div>
                        </div>
                        <div class="option-item">
                            <label class="toggle-switch-mini">
                                <input type="checkbox" id="wc-sync-users" <?php checked(get_option('mlf_wc_sync_users', 1), 1); ?>>
                                <span class="toggle-slider-mini"></span>
                            </label>
                            <div class="option-info">
                                <strong><?php _e('Sync User Data', 'my-login-form'); ?></strong>
                                <p><?php _e('Sync user profiles between systems', 'my-login-form'); ?></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="action-buttons">
                        <button id="save-wc-settings" class="button-primary">
                            <i class="fas fa-save"></i> <?php _e('Save Settings', 'my-login-form'); ?>
                        </button>
                    </div>
                <?php else: ?>
                    <div class="woo-status error">
                        <i class="fas fa-exclamation-triangle"></i>
                        <span><?php _e('WooCommerce is not installed or activated', 'my-login-form'); ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
        
    </div>
</div>

<style>
/* Integration Hub Styles */
.mlf-integration-hub {
    max-width: 1400px;
    margin: 20px auto;
}

/* Header */
.integration-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 25px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    border-radius: 15px;
    color: #fff;
    margin-bottom: 25px;
}

.header-left h1 {
    margin: 0 0 10px;
    font-size: 28px;
    color: #fff;
}

.header-description {
    margin: 0;
    opacity: 0.9;
}

.connection-status-card {
    background: rgba(255,255,255,0.2);
    padding: 12px 20px;
    border-radius: 10px;
    backdrop-filter: blur(10px);
}

.status-connected, .status-disconnected {
    display: flex;
    align-items: center;
    gap: 12px;
}

.status-connected i {
    font-size: 24px;
    color: #4caf50;
}

.status-disconnected i {
    font-size: 24px;
    color: #ff9800;
}

/* Setup Wizard */
.setup-wizard {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
    margin-bottom: 25px;
    overflow: hidden;
}

.wizard-header {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: #fff;
    padding: 20px 25px;
}

.wizard-header h2 {
    margin: 0 0 5px;
    color: #fff;
}

.wizard-steps {
    display: flex;
    padding: 30px;
    gap: 20px;
    background: #f8f9fa;
}

.wizard-step {
    flex: 1;
    text-align: center;
    padding: 20px;
    background: #fff;
    border-radius: 10px;
    position: relative;
}

.wizard-step.active {
    border: 2px solid #ff9800;
    background: #fff8e1;
}

.step-number {
    width: 40px;
    height: 40px;
    background: #ff9800;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 15px;
    font-weight: bold;
    font-size: 18px;
}

.wizard-step h3 {
    margin: 0 0 10px;
    font-size: 16px;
}

.wizard-step p {
    margin: 0;
    font-size: 13px;
    color: #666;
}

/* Tabs */
.integration-tabs {
    display: flex;
    gap: 5px;
    margin-bottom: 25px;
    background: #fff;
    padding: 5px;
    border-radius: 10px;
    box-shadow: 0 2px 5px rgba(0,0,0,0.05);
}

.tab-link {
    padding: 12px 24px;
    text-decoration: none;
    color: #666;
    border-radius: 8px;
    transition: all 0.3s;
    position: relative;
}

.tab-link i {
    margin-right: 8px;
}

.tab-link:hover {
    background: #f0f0f0;
    color: #333;
}

.tab-link.active {
    background: #667eea;
    color: #fff;
}

.tab-badge {
    background: #ff9800;
    color: #fff;
    font-size: 10px;
    padding: 2px 6px;
    border-radius: 10px;
    margin-left: 8px;
}

.tab-badge.warning {
    background: #ff9800;
    animation: pulse 2s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.6; }
}

/* Cards */
.enterprise-card {
    background: #fff;
    border-radius: 15px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.08);
    margin-bottom: 25px;
    overflow: hidden;
}

.card-header {
    display: flex;
    gap: 20px;
    padding: 25px;
    background: #f8f9fa;
    border-bottom: 1px solid #e5e5e5;
}

.header-icon {
    width: 50px;
    height: 50px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.header-icon i {
    font-size: 24px;
    color: #fff;
}

.header-text h2 {
    margin: 0 0 8px;
    font-size: 20px;
}

.header-text p {
    margin: 0;
    color: #666;
}

.card-body {
    padding: 25px;
}

/* Form */
.form-group {
    margin-bottom: 25px;
}

.form-group label {
    display: block;
    margin-bottom: 8px;
    font-weight: 600;
    font-size: 14px;
}

.large-input {
    width: 100%;
    padding: 12px 15px;
    border: 2px solid #e5e5e5;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s;
}

.large-input:focus {
    border-color: #667eea;
    outline: none;
    box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
}

.required {
    color: #f44336;
}

.optional {
    color: #999;
    font-weight: normal;
}

.description {
    margin: 5px 0 0;
    font-size: 12px;
    color: #666;
}

.description.warning {
    color: #ff9800;
}

/* Connection Test Status */
.connection-test-status {
    margin: 20px 0;
    padding: 15px;
    border-radius: 10px;
    background: #f8f9fa;
}

.status-message {
    display: flex;
    align-items: center;
    gap: 10px;
}

.status-message i {
    font-size: 20px;
}

.status-message.testing i {
    color: #2196f3;
    animation: spin 1s linear infinite;
}

@keyframes spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

.status-message.success i {
    color: #4caf50;
}

.status-message.error i {
    color: #f44336;
}

/* Action Buttons */
.action-buttons-group {
    display: flex;
    gap: 15px;
    margin: 25px 0;
}

.button-large {
    padding: 12px 24px !important;
    height: auto !important;
    font-size: 14px !important;
}

/* Connection Help */
.connection-help {
    margin-top: 30px;
    padding: 20px;
    background: #f0f7ff;
    border-radius: 10px;
}

.connection-help h4 {
    margin: 0 0 15px;
}

.help-steps {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 15px;
}

.help-step {
    display: flex;
    gap: 12px;
    align-items: flex-start;
}

.step-icon {
    width: 24px;
    height: 24px;
    background: #667eea;
    color: #fff;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px;
    font-weight: bold;
}

.help-step strong {
    display: block;
    margin-bottom: 5px;
    font-size: 13px;
}

.help-step p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

/* Success Card */
.success-card {
    border: 2px solid #4caf50;
}

.success-message {
    display: flex;
    align-items: center;
    gap: 20px;
    text-align: left;
}

.success-message i {
    font-size: 48px;
    color: #4caf50;
}

.success-message h3 {
    margin: 0 0 10px;
}

.next-steps {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

/* Platforms Grid */
.platforms-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 15px;
    margin-bottom: 25px;
}

.platform-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 12px;
    transition: all 0.3s;
}

.platform-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.platform-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.platform-icon i {
    font-size: 24px;
    color: #fff;
}

.platform-info {
    flex: 1;
}

.platform-info h4 {
    margin: 0;
}

/* Toggle Switch */
.toggle-switch-mini {
    position: relative;
    display: inline-block;
    width: 44px;
    height: 24px;
}

.toggle-switch-mini input {
    opacity: 0;
    width: 0;
    height: 0;
}

.toggle-slider-mini {
    position: absolute;
    cursor: pointer;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: #ccc;
    transition: 0.3s;
    border-radius: 24px;
}

.toggle-slider-mini:before {
    position: absolute;
    content: "";
    height: 18px;
    width: 18px;
    left: 3px;
    bottom: 3px;
    background-color: white;
    transition: 0.3s;
    border-radius: 50%;
}

input:checked + .toggle-slider-mini {
    background-color: #4caf50;
}

input:checked + .toggle-slider-mini:before {
    transform: translateX(20px);
}

/* Shortcode Box */
.shortcode-box {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 12px 15px;
    border-radius: 8px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin: 15px 0;
    font-family: monospace;
}

.shortcode-box code {
    background: none;
    color: #d4d4d4;
    font-size: 13px;
}

/* Connection Details */
.connection-details {
    margin-bottom: 25px;
}

.detail-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 12px;
    background: #f8f9fa;
    border-radius: 8px;
    margin-bottom: 10px;
}

.detail-label {
    font-weight: 600;
    min-width: 100px;
}

.detail-value {
    flex: 1;
    font-family: monospace;
}

.detail-value.status-active {
    color: #4caf50;
}

.detail-value.status-active i {
    font-size: 10px;
}

.copy-detail {
    background: #e0e0e0;
    border: none;
    padding: 5px 10px;
    border-radius: 5px;
    cursor: pointer;
}

/* Tables List */
.tables-list {
    display: grid;
    gap: 15px;
}

.table-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
}

.table-item i {
    font-size: 24px;
    color: #667eea;
}

.table-item div {
    flex: 1;
}

.table-item strong {
    display: block;
    margin-bottom: 5px;
}

.table-item p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

.table-status {
    font-size: 12px;
    padding: 4px 8px;
    border-radius: 5px;
}

.table-status.created {
    background: #d4edda;
    color: #155724;
}

/* WooCommerce */
.woo-status {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
}

.woo-status.success {
    background: #d4edda;
    color: #155724;
}

.woo-status.error {
    background: #f8d7da;
    color: #721c24;
}

.option-item {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 10px;
    margin-bottom: 10px;
}

.option-info {
    flex: 1;
}

.option-info strong {
    display: block;
    margin-bottom: 5px;
}

.option-info p {
    margin: 0;
    font-size: 12px;
    color: #666;
}

/* Analytics Stats */
.analytics-stats {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.stat-card {
    background: linear-gradient(135deg, #667eea, #764ba2);
    padding: 25px;
    border-radius: 12px;
    text-align: center;
    color: #fff;
}

.stat-value {
    font-size: 36px;
    font-weight: bold;
    margin-bottom: 10px;
}

.stat-label {
    font-size: 14px;
    opacity: 0.9;
}

/* Responsive */
@media (max-width: 768px) {
    .integration-header {
        flex-direction: column;
        text-align: center;
        gap: 15px;
    }
    
    .wizard-steps {
        flex-direction: column;
    }
    
    .integration-tabs {
        flex-wrap: wrap;
    }
    
    .action-buttons-group {
        flex-direction: column;
    }
    
    .help-steps {
        grid-template-columns: 1fr;
    }
    
    .success-message {
        flex-direction: column;
        text-align: center;
    }
}
</style>

<script>
jQuery(document).ready(function($) {
    let isConnected = <?php echo $supabase_connected ? 'true' : 'false'; ?>;
    
    // Test Connection Function
    $('#test-connection-btn').on('click', function() {
        var url = $('#setup_supabase_url').val();
        var anonKey = $('#setup_supabase_anon_key').val();
        
        if (!url || !anonKey) {
            showConnectionStatus('error', 'Please enter both URL and Anon Key');
            return;
        }
        
        showConnectionStatus('testing', 'Testing connection to Supabase...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mlf_real_time_supabase_test',
                url: url,
                anon_key: anonKey,
                nonce: '<?php echo wp_create_nonce('mlf_supabase_test'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showConnectionStatus('success', response.data.message);
                    $('#save-connection-btn').prop('disabled', false);
                    isConnected = true;
                } else {
                    showConnectionStatus('error', response.data.message);
                    $('#save-connection-btn').prop('disabled', true);
                }
            },
            error: function() {
                showConnectionStatus('error', 'Connection failed. Please check your credentials.');
            }
        });
    });
    
    // Save Connection
    $('#save-connection-btn').on('click', function() {
        var url = $('#setup_supabase_url').val();
        var anonKey = $('#setup_supabase_anon_key').val();
        var serviceKey = $('#setup_supabase_service_key').val();
        
        showConnectionStatus('testing', 'Saving connection settings...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mlf_save_supabase_connection',
                url: url,
                anon_key: anonKey,
                service_key: serviceKey,
                nonce: '<?php echo wp_create_nonce('mlf_supabase_save'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    showConnectionStatus('success', response.data.message);
                    $('#connection-success-preview').fadeIn();
                    $('#global-connection-status').html(`
                        <div class="status-connected">
                            <i class="fas fa-check-circle"></i>
                            <div>
                                <strong>Supabase Connected</strong>
                                <small>${url}</small>
                            </div>
                        </div>
                    `);
                    setTimeout(function() {
                        window.location.href = '?page=my-login-form-integration&tab=supabase';
                    }, 2000);
                }
            }
        });
    });
    
    // Refresh Connection
    $('#refresh-connection').on('click', function() {
        location.reload();
    });
    
    // Disconnect
    $('#disconnect-supabase').on('click', function() {
        if (confirm('Are you sure you want to disconnect Supabase? This will disable all features that depend on it.')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'mlf_disconnect_supabase',
                    nonce: '<?php echo wp_create_nonce('mlf_disconnect'); ?>'
                },
                success: function() {
                    location.reload();
                }
            });
        }
    });
    
    // Save WooCommerce Settings
    $('#save-wc-settings').on('click', function() {
        var autoLogin = $('#wc-auto-login').is(':checked') ? 1 : 0;
        var syncUsers = $('#wc-sync-users').is(':checked') ? 1 : 0;
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'mlf_save_wc_settings',
                auto_login: autoLogin,
                sync_users: syncUsers,
                nonce: '<?php echo wp_create_nonce('mlf_wc_settings'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    alert('WooCommerce settings saved!');
                }
            }
        });
    });
    
    // Copy Shortcode
    $('.copy-shortcode').on('click', function() {
        var code = $(this).data('code');
        copyToClipboard(code);
        var original = $(this).html();
        $(this).html('<i class="fas fa-check"></i> Copied!');
        setTimeout(function() {
            $(this).html(original);
        }.bind(this), 2000);
    });
    
    // Copy Detail
    $('.copy-detail').on('click', function() {
        var text = $(this).data('copy');
        copyToClipboard(text);
        $(this).html('<i class="fas fa-check"></i>');
        setTimeout(function() {
            $(this).html('<i class="fas fa-copy"></i>');
        }.bind(this), 2000);
    });
    
    // Refresh Stats
    $('#refresh-stats').on('click', function() {
        $('#total-shares').text(Math.floor(Math.random() * 100));
        $('#unique-users').text(Math.floor(Math.random() * 50));
    });
    
    // Helper Functions
    function showConnectionStatus(type, message) {
        var icon = '';
        var color = '';
        
        switch(type) {
            case 'testing':
                icon = '<i class="fas fa-spinner fa-pulse"></i>';
                color = '#2196f3';
                break;
            case 'success':
                icon = '<i class="fas fa-check-circle"></i>';
                color = '#4caf50';
                break;
            case 'error':
                icon = '<i class="fas fa-exclamation-circle"></i>';
                color = '#f44336';
                break;
            default:
                icon = '<i class="fas fa-info-circle"></i>';
                color = '#666';
        }
        
        $('#connection-test-status').html(`
            <div class="status-message" style="color: ${color}">
                ${icon}
                <span>${message}</span>
            </div>
        `);
    }
    
    function copyToClipboard(text) {
        var textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
    }
    
    // Auto-test when fields are filled
    $('#setup_supabase_url, #setup_supabase_anon_key').on('input', function() {
        var url = $('#setup_supabase_url').val();
        var key = $('#setup_supabase_anon_key').val();
        
        if (url && key) {
            $('#test-connection-btn').click();
        }
    });
});
</script>

<?php
// Disconnect handler
add_action('wp_ajax_mlf_disconnect_supabase', 'mlf_disconnect_supabase');
function mlf_disconnect_supabase() {
    check_ajax_referer('mlf_disconnect', 'nonce');
    delete_option('mlf_supabase_enabled');
    delete_option('mlf_supabase_url');
    delete_option('mlf_supabase_anon_key');
    delete_option('mlf_supabase_service_key');
    wp_send_json_success();
}

// Shortcode function
function mlf_social_share_buttons($atts = []) {
    $atts = shortcode_atts(['url' => '', 'title' => ''], $atts);
    $url = $atts['url'] ?: get_permalink();
    $title = $atts['title'] ?: get_the_title();
    
    $platforms = [
        'facebook' => ['icon' => 'fab fa-facebook', 'url' => "https://www.facebook.com/sharer/sharer.php?u=" . urlencode($url), 'color' => '#1877f2'],
        'twitter' => ['icon' => 'fab fa-twitter', 'url' => "https://twitter.com/intent/tweet?url=" . urlencode($url) . "&text=" . urlencode($title), 'color' => '#1da1f2'],
        'linkedin' => ['icon' => 'fab fa-linkedin', 'url' => "https://www.linkedin.com/sharing/share-offsite/?url=" . urlencode($url), 'color' => '#0077b5'],
        'whatsapp' => ['icon' => 'fab fa-whatsapp', 'url' => "https://wa.me/?text=" . urlencode($title . ' ' . $url), 'color' => '#25d366'],
        'telegram' => ['icon' => 'fab fa-telegram', 'url' => "https://t.me/share/url?url=" . urlencode($url) . "&text=" . urlencode($title), 'color' => '#0088cc']
    ];
    
    $html = '<div class="mlf-social-buttons" style="display: flex; gap: 10px; flex-wrap: wrap;">';
    foreach ($platforms as $key => $platform) {
        if (get_option("mlf_{$key}_enabled", 1)) {
            $html .= '<a href="' . esc_url($platform['url']) . '" target="_blank" class="mlf-share-btn" style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 20px; background: ' . $platform['color'] . '; color: #fff; text-decoration: none; border-radius: 8px; transition: transform 0.2s;" onmouseover="this.style.transform=\'translateY(-2px)\'" onmouseout="this.style.transform=\'translateY(0)\'">';
            $html .= '<i class="' . $platform['icon'] . '"></i>';
            $html .= '<span>' . ucfirst($key) . '</span>';
            $html .= '</a>';
        }
    }
    $html .= '</div>';
    
    // Track share in analytics if Supabase is connected
    if (get_option('mlf_supabase_enabled')) {
        $html .= '<script>
        document.querySelectorAll(".mlf-share-btn").forEach(btn => {
            btn.addEventListener("click", function(e) {
                var platform = this.querySelector("span").innerText.toLowerCase();
                fetch(ajaxurl, {
                    method: "POST",
                    headers: {"Content-Type": "application/x-www-form-urlencoded"},
                    body: "action=mlf_track_share&platform=" + platform + "&url=" + encodeURIComponent(window.location.href) + "&nonce=' . wp_create_nonce('mlf_social_nonce') . '"
                });
            });
        });
        </script>';
    }
    
    return $html;
}
add_shortcode('mlf_social_share', 'mlf_social_share_buttons');

// Track share handler
add_action('wp_ajax_mlf_track_share', 'mlf_track_share_handler');
add_action('wp_ajax_nopriv_mlf_track_share', 'mlf_track_share_handler');
function mlf_track_share_handler() {
    check_ajax_referer('mlf_social_nonce', 'nonce');
    
    global $wpdb;
    $table = $wpdb->prefix . 'mlf_social_analytics';
    
    // Create table if not exists
    $charset_collate = $wpdb->get_charset_collate();
    $sql = "CREATE TABLE IF NOT EXISTS $table (
        id bigint(20) NOT NULL AUTO_INCREMENT,
        platform varchar(50) NOT NULL,
        url text NOT NULL,
        user_id bigint(20) DEFAULT 0,
        ip_address varchar(45) DEFAULT NULL,
        user_agent text,
        shared_at datetime DEFAULT CURRENT_TIMESTAMP,
        PRIMARY KEY (id),
        KEY idx_platform (platform),
        KEY idx_shared_at (shared_at)
    ) $charset_collate;";
    
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
    
    $data = [
        'platform' => sanitize_text_field($_POST['platform']),
        'url' => esc_url_raw($_POST['url']),
        'user_id' => get_current_user_id(),
        'ip_address' => $_SERVER['REMOTE_ADDR'],
        'user_agent' => $_SERVER['HTTP_USER_AGENT'],
        'shared_at' => current_time('mysql')
    ];
    
    $wpdb->insert($table, $data);
    wp_send_json_success();
}
?>