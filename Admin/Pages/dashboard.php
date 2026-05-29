<?php
/**
 * Dashboard Template
 *
 * @since 1.0.0
 * @package MyLoginForm\Admin
 */

    // Prevent Direct Access
    defined('ABSPATH') || exit;

    // Get database instances
    $forms_table = \MyLoginForm\Database\FormsDatabase::get_instance();
    $users_table = \MyLoginForm\Database\UsersDatabase::get_instance();
    $supabase_table = \MyLoginForm\Database\SupabaseDatabase::get_instance();

    // Dashboard Page Link
    $dashboard_page_link = admin_url('admin.php?page=my-login-form-dashboard');

    // Designer Page Link
    $designer_page_link = admin_url('admin.php?page=my-login-form-designer');

    // Settings Page Link
    $settings_page_link = admin_url('admin.php?page=my-login-form-settings');
    
    // Supabase Settings Page Link
    $supabase_settings_page_link = admin_url('admin.php?page=my-login-form-social-supabase');

    // User Data Page Link
    $user_data_page_link = admin_url('admin.php?page=my-login-form-user-data');

    // Instead of accessing private property directly, use the getter
    $recent_users = $users_table->get_users(array('limit' => 5, 'orderby' => 'created_at', 'order' => 'DESC'));

    // Get all forms
    $all_forms = $forms_table->get_all_forms();
    $recent_forms = array_slice($all_forms, 0, 5);

    // Get all users
    $all_users = $users_table->get_users(array('limit' => 100, 'orderby' => 'created_at', 'order' => 'DESC'));
    $recent_users = array_slice($all_users, 0, 5);

    // Calculate stats
    $total_forms = count($all_forms);
    $active_forms = count(array_filter($all_forms, function($form) { return $form->status === 'active'; }));

    $total_users = $users_table->get_users_count();
    $today_users = count($users_table->get_users(array('date_from' => date('Y-m-d 00:00:00'))));
    $active_users = count($users_table->get_users(array('last_login' => date('Y-m-d H:i:s', strtotime('-30 days')))));

    // Social login users
    $social_users = count($users_table->get_users(array('social_provider' => 'not_empty')));

    // Form types distribution
    $form_types = array();
    foreach ($all_forms as $form) {
        $type = $form->form_type;
        if (!isset($form_types[$type])) {
            $form_types[$type] = 0;
        }
        $form_types[$type]++;
    }

    // Get Supabase sync status
    $supabase_status = $supabase_table->get_sync_status();
    $synced_users = $supabase_status['supabase_linked_users'] ?? 0;
    $total_local_users = $supabase_status['total_local_users'] ?? $total_users;
    $sync_rate = $total_local_users > 0 ? round(($synced_users / $total_local_users) * 100) : 0;

// Chart data for last 7 days
$chart_labels = array();
$chart_data = array();
for ($i = 6; $i >= 0; $i--) {
    $date = date('Y-m-d', strtotime("-$i days"));
    $chart_labels[] = date('M j', strtotime($date));
    $count = count($users_table->get_users(array(
        'date_from' => $date . ' 00:00:00',
        'date_to' => $date . ' 23:59:59'
    )));
    $chart_data[] = $count;
}

// System status
$system_status = array(
    'wordpress' => array(
        'name' => __('WordPress', 'my-login-form'),
        'value' => get_bloginfo('version'),
        'status' => version_compare(get_bloginfo('version'), '5.0', '>=') ? 'good' : 'warning'
    ),
    'php' => array(
        'name' => __('PHP', 'my-login-form'),
        'value' => phpversion(),
        'status' => version_compare(phpversion(), '7.4', '>=') ? 'good' : 'warning'
    ),
    'mysql' => array(
        'name' => __('MySQL', 'my-login-form'),
        'value' => $GLOBALS['wpdb']->db_version(),
        'status' => 'good'
    ),
    'tables' => array(
        'name' => __('Database Tables', 'my-login-form'),
        'value' => $forms_table->table_exists() && $users_table->table_exists() ? __('All present', 'my-login-form') : __('Missing', 'my-login-form'),
        'status' => $forms_table->table_exists() && $users_table->table_exists() ? 'good' : 'error'
    ),
    'supabase' => array(
        'name' => __('Supabase', 'my-login-form'),
        'value' => $supabase_table->is_configured() ? __('Connected', 'my-login-form') : __('Not configured', 'my-login-form'),
        'status' => $supabase_table->is_configured() ? 'good' : 'warning'
    ),
    'social_login' => array(
        'name' => __('Social Login', 'my-login-form'),
        'value' => $social_users,
        'status' => $social_users > 0 ? 'good' : 'info'
    ),
    'profile_page' => array(
        'name' => __('Profile Page', 'my-login-form'),
        'value' => get_permalink(get_option('my_login_profile_page_id', 0)) ? __('Set', 'my-login-form') : __('Not set', 'my-login-form'),
        'status' => get_option('my_login_profile_page_id', 0) ? 'good' : 'warning'
    )
);

// Default forms status
$default_forms = array('login', 'register', 'forgot_password', 'welcome', 'popup');
$default_forms_status = array();
foreach ($default_forms as $form_type) {
    $form = $forms_table->get_default_form($form_type);
    $default_forms_status[] = array(
        'name' => ucfirst(str_replace('_', ' ', $form_type)) . ' Form',
        'exists' => !empty($form),
        'status' => !empty($form) ? 'good' : 'warning'
    );
}

// Greeting based on time
$hour = current_time('H');
if ($hour < 12) {
    $greeting = __('Good Morning', 'my-login-form');
} elseif ($hour < 17) {
    $greeting = __('Good Afternoon', 'my-login-form');
} else {
    $greeting = __('Good Evening', 'my-login-form');
}

// Stats array for the template
$stats = array(
    'total_forms' => $total_forms,
    'active_forms' => $active_forms,
    'total_users' => $total_users,
    'today_users' => $today_users,
    'active_users' => $active_users,
    'social_users' => $social_users,
    'synced_users' => $synced_users,
    'sync_rate' => $sync_rate,
    'form_types' => $form_types
);

$current_user = wp_get_current_user();
?>

<div class="wrap my-login-form-dashboard">
    <!-- Welcome Header -->
    <div class="dashboard-welcome">
        <div class="welcome-content">
            <h1><?php echo esc_html($greeting); ?>, <?php echo esc_html($current_user->display_name); ?> 👋</h1>
            <p class="welcome-subtitle"><?php _e('Welcome to My Login Form Dashboard. Manage your forms, users, and settings from here.', 'my-login-form'); ?></p>
        </div>
        <div class="welcome-actions">
            <a href="<?php echo $designer_page_link; ?>" class="button button-primary button-hero">
                <?php _e('Create New Form', 'my-login-form'); ?>
            </a>
        </div>
    </div>
    
    <!-- Stats Cards -->
    <div class="dashboard-stats">
        <!-- Total Forms -->
        <div class="stat-card stat-card-primary">
            <div class="stat-icon">📝</div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Total Forms', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['total_forms']); ?></div>
                <div class="stat-sub"><?php printf(__('%d Active', 'my-login-form'), $stats['active_forms']); ?></div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=my-login-form-designer'); ?>" class="stat-link">
                <?php _e('View All', 'my-login-form'); ?> →
            </a>
        </div>
        
        <!-- Total Users -->
        <div class="stat-card stat-card-success">
            <div class="stat-icon">👥</div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Total Users', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['total_users']); ?></div>
                <div class="stat-sub"><?php printf(__('+%d today', 'my-login-form'), $stats['today_users']); ?></div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=my-login-form-users-data'); ?>" class="stat-link">
                <?php _e('View All', 'my-login-form'); ?> →
            </a>
        </div>

        <!-- Active Users -->
        <div class="stat-card stat-card-success">
            <div class="stat-icon">⭐</div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Active Users', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['active_users']); ?></div>
                <div class="stat-sub"><?php _e('Last 30 days', 'my-login-form'); ?></div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=my-login-form-users-data'); ?>" class="stat-link">
                <?php _e('View All', 'my-login-form'); ?> →
            </a>
        </div>
        
        <!-- Social Users -->
        <div class="stat-card stat-card-info">
            <div class="stat-icon">🌐</div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Social Login Users', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['social_users']); ?></div>
                <div class="stat-sub"><?php _e('Connected via social media', 'my-login-form'); ?></div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=my-login-form-social-supabase'); ?>" class="stat-link">
                <?php _e('Configure', 'my-login-form'); ?> →
            </a>
        </div>
        
        <!-- Synced Users -->
        <div class="stat-card stat-card-warning">
            <div class="stat-icon">🔄</div>
            <div class="stat-info">
                <div class="stat-label"><?php _e('Supabase Sync', 'my-login-form'); ?></div>
                <div class="stat-value"><?php echo number_format_i18n($stats['synced_users']); ?></div>
                <div class="stat-sub"><?php printf(__('%d%% synced', 'my-login-form'), $stats['sync_rate']); ?></div>
            </div>
            <a href="<?php echo admin_url('admin.php?page=my-login-form-social-supabase'); ?>" class="stat-link">
                <?php _e('Configure', 'my-login-form'); ?> →
            </a>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="dashboard-row">
        <div class="dashboard-card chart-card">
            <div class="card-header">
                <h3><?php _e('Registration Trends', 'my-login-form'); ?></h3>
                <span class="card-subtitle"><?php _e('Last 7 days', 'my-login-form'); ?></span>
            </div>
            <div class="card-body">
                <canvas id="registrations-chart" 
                        data-labels='<?php echo json_encode($chart_labels); ?>' 
                        data-values='<?php echo json_encode($chart_data); ?>'
                        style="height: 300px; width: 100%;"></canvas>
            </div>
        </div>
        
        <div class="dashboard-card stats-card">
            <div class="card-header">
                <h3><?php _e('Form Types', 'my-login-form'); ?></h3>
                <span class="card-subtitle"><?php _e('Distribution', 'my-login-form'); ?></span>
            </div>
            <div class="card-body">
                <?php if (!empty($stats['form_types'])): ?>
                    <div class="form-types-list">
                        <?php foreach ($stats['form_types'] as $type => $count): ?>
                            <div class="form-type-item">
                                <div class="type-label">
                                    <span class="type-icon">
                                        <?php
                                        $icon = '📄';
                                        switch($type) {
                                            case 'login': $icon = '🔐'; break;
                                            case 'register': $icon = '📝'; break;
                                            case 'welcome': $icon = '👋'; break;
                                            case 'forgot_password': $icon = '🔑'; break;
                                            case 'popup': $icon = '💬'; break;
                                            default: $icon = '📄';
                                        }
                                        echo $icon;
                                        ?>
                                    </span>
                                    <span class="type-name"><?php echo ucfirst(str_replace('_', ' ', $type)); ?></span>
                                </div>
                                <div class="type-count"><?php echo $count; ?></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="no-data"><?php _e('No forms created yet.', 'my-login-form'); ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Recent Activity -->
    <div class="dashboard-row">
        <!-- Recent Users -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><?php _e('Recent Users', 'my-login-form'); ?></h3>
                <a href="<?php echo admin_url('admin.php?page=my-login-form-users-data'); ?>" class="card-link">
                    <?php _e('View All', 'my-login-form'); ?> →
                </a>
            </div>
            <div class="card-body no-padding">
                <?php if (empty($recent_users)): ?>
                    <div class="empty-state">
                        <p><?php _e('No users registered yet.', 'my-login-form'); ?></p>
                    </div>
                <?php else: ?>
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th><?php _e('User', 'my-login-form'); ?></th>
                                <th><?php _e('Email', 'my-login-form'); ?></th>
                                <th><?php _e('Date', 'my-login-form'); ?></th>
                                <th><?php _e('Status', 'my-login-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_users as $user): ?>
                                <tr>
                                    <td>
                                        <div class="user-info">
                                            <div class="user-avatar">
                                                <?php echo get_avatar($user->user_email, 32); ?>
                                            </div>
                                            <div>
                                                <div class="user-name">
                                                    <?php echo esc_html($user->user_first_name . ' ' . $user->user_last_name); ?>
                                                </div>
                                                <div class="user-id">ID: <?php echo $user->id; ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td><?php echo esc_html($user->user_email); ?></td>
                                    <td><?php echo date_i18n(get_option('date_format'), strtotime($user->created_at)); ?></td>
                                    <td>
                                        <?php if ($user->email_verified): ?>
                                            <span class="badge badge-success"><?php _e('Verified', 'my-login-form'); ?></span>
                                        <?php else: ?>
                                            <span class="badge badge-warning"><?php _e('Pending', 'my-login-form'); ?></span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- Recent Forms -->
        <div class="dashboard-card">
            <div class="card-header">
                <h3><?php _e('Recent Forms', 'my-login-form'); ?></h3>
                <a href="<?php echo admin_url('admin.php?page=my-login-form-designer'); ?>" class="card-link">
                    <?php _e('View All', 'my-login-form'); ?> →
                </a>
            </div>
            <div class="card-body no-padding">
                <?php if (empty($recent_forms)): ?>
                    <div class="empty-state">
                        <p><?php _e('No forms created yet.', 'my-login-form'); ?></p>
                        <a href="<?php echo admin_url('admin.php?page=my-login-form-designer'); ?>" class="button button-small">
                            <?php _e('Create Your First Form', 'my-login-form'); ?>
                        </a>
                    </div>
                <?php else: ?>
                    <table class="recent-table">
                        <thead>
                            <tr>
                                <th><?php _e('Form', 'my-login-form'); ?></th>
                                <th><?php _e('Type', 'my-login-form'); ?></th>
                                <th><?php _e('Shortcode', 'my-login-form'); ?></th>
                                <th><?php _e('Views', 'my-login-form'); ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recent_forms as $form): 
                                $icon = '📄';
                                switch($form->form_type) {
                                    case 'login': $icon = '🔐'; break;
                                    case 'register': $icon = '📝'; break;
                                    case 'welcome': $icon = '👋'; break;
                                    case 'forgot_password': $icon = '🔑'; break;
                                    case 'popup': $icon = '💬'; break;
                                    default: $icon = '📄';
                                }
                            ?>
                                <tr>
                                    <td>
                                        <div class="form-info">
                                            <span class="form-icon"><?php echo $icon; ?></span>
                                            <div>
                                                <div class="form-name"><?php echo esc_html($form->name); ?></div>
                                                <div class="form-key"><?php echo esc_html($form->form_key); ?></div>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge badge-info"><?php echo ucfirst(str_replace('_', ' ', $form->form_type)); ?></span>
                                    </td>
                                    <td>
                                        <code class="shortcode">[my_login_form id="<?php echo $form->id; ?>"]</code>
                                    </td>
                                    <td><?php echo number_format_i18n($form->views_count); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- System Status -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><?php _e('System Status', 'my-login-form'); ?></h3>
            <button class="button button-small" onclick="location.reload();">
                <?php _e('Refresh', 'my-login-form'); ?>
            </button>
        </div>
        <div class="card-body">
            <div class="status-grid">
                <div class="status-column">
                    <h4><?php _e('Environment', 'my-login-form'); ?></h4>
                    <table class="status-table">
                        <?php foreach ($system_status as $key => $status): ?>
                            <?php if (in_array($key, ['wordpress', 'php', 'mysql', 'tables'])): ?>
                            <tr>
                                <td class="status-label"><?php echo $status['name']; ?>:</td>
                                <td class="status-value"><?php echo esc_html($status['value']); ?></td>
                                <td class="status-badge">
                                    <span class="badge badge-<?php echo $status['status']; ?>">
                                        <?php
                                        switch($status['status']) {
                                            case 'good': echo '✓ OK'; break;
                                            case 'warning': echo '⚠ Warning'; break;
                                            case 'error': echo '✗ Error'; break;
                                            default: echo 'ℹ Info';
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <div class="status-column">
                    <h4><?php _e('Integrations', 'my-login-form'); ?></h4>
                    <table class="status-table">
                        <?php foreach ($system_status as $key => $status): ?>
                            <?php if (in_array($key, ['supabase', 'social_login', 'profile_page'])): ?>
                            <tr>
                                <td class="status-label"><?php echo $status['name']; ?>:</td>
                                <td class="status-value"><?php echo esc_html($status['value']); ?></td>
                                <td class="status-badge">
                                    <span class="badge badge-<?php echo $status['status']; ?>">
                                        <?php
                                        switch($status['status']) {
                                            case 'good': echo '✓ Active'; break;
                                            case 'warning': echo '⚠ Inactive'; break;
                                            default: echo 'ℹ Disabled';
                                        }
                                        ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </table>
                </div>
                
                <div class="status-column">
                    <h4><?php _e('Default Forms', 'my-login-form'); ?></h4>
                    <table class="status-table">
                        <?php foreach ($default_forms_status as $form): ?>
                            <tr>
                                <td class="status-label"><?php echo $form['name']; ?>:</td>
                                <td class="status-value">
                                    <?php echo $form['exists'] ? __('Created', 'my-login-form') : __('Missing', 'my-login-form'); ?>
                                </td>
                                <td class="status-badge">
                                    <span class="badge badge-<?php echo $form['status']; ?>">
                                        <?php echo $form['exists'] ? '✓' : '⚠'; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Quick Actions -->
    <div class="dashboard-card">
        <div class="card-header">
            <h3><?php _e('Quick Actions', 'my-login-form'); ?></h3>
        </div>
        <div class="card-body">
            <div class="quick-actions-grid">
                <a href="<?php echo admin_url('admin.php?page=my-login-form-designer'); ?>" class="quick-action">
                    <div class="action-icon">➕</div>
                    <div class="action-title"><?php _e('Create Form', 'my-login-form'); ?></div>
                    <div class="action-desc"><?php _e('Build a new login or registration form', 'my-login-form'); ?></div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=my-login-form-users-data'); ?>" class="quick-action">
                    <div class="action-icon">👥</div>
                    <div class="action-title"><?php _e('Manage Users', 'my-login-form'); ?></div>
                    <div class="action-desc"><?php _e('View and manage registered users', 'my-login-form'); ?></div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=my-login-form-social-supabase'); ?>" class="quick-action">
                    <div class="action-icon">☁️</div>
                    <div class="action-title"><?php _e('Supabase Sync', 'my-login-form'); ?></div>
                    <div class="action-desc"><?php _e('Configure Supabase integration', 'my-login-form'); ?></div>
                </a>
                
                <a href="<?php echo admin_url('admin.php?page=my-login-form-settings'); ?>" class="quick-action">
                    <div class="action-icon">⚙️</div>
                    <div class="action-title"><?php _e('Settings', 'my-login-form'); ?></div>
                    <div class="action-desc"><?php _e('Configure plugin settings', 'my-login-form'); ?></div>
                </a>
            </div>
        </div>
    </div>
</div>