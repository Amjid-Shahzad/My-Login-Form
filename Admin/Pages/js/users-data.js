// Select all checkboxes
document.getElementById('select-all')?.addEventListener('click', function() {
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = this.checked;
    });
});

// Apply bulk action
function applyBulkAction() {
    const action = document.getElementById('bulk_action_select').value;
    if (action === '-1') {
        alert('<?php _e('Please select an action', 'my-login-form'); ?>');
        return;
    }
    
    const checkboxes = document.querySelectorAll('input[name="user_ids[]"]:checked');
    if (checkboxes.length === 0) {
        alert('<?php _e('Please select at least one user', 'my-login-form'); ?>');
        return;
    }
    
    if (action === 'delete' && !confirm('<?php _e('Are you sure you want to delete selected users? This action cannot be undone.', 'my-login-form'); ?>')) {
        return;
    }
    
    // Set the bulk action field and submit the form
    document.getElementById('bulk_action_field').value = action;
    document.getElementById('users-form').submit();
}

// Export CSV
function exportCSV() {
    window.location.href = '<?php echo admin_url('admin-ajax.php?action=my_login_form_export_csv'); ?>';
}

// Sync with Firebase
function syncWithFirebase() {
    if (!confirm('<?php _e('Sync all pending users with Firebase?', 'my-login-form'); ?>')) return;
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php _e("Syncing...", "my-login-form"); ?>';
    button.disabled = true;
    
    fetch('<?php echo admin_url('admin-ajax.php?action=my_login_form_sync_firebase'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            nonce: '<?php echo wp_create_nonce('my_login_form_dashboard_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert(data.data.message);
            location.reload();
        } else {
            alert('<?php _e('Error: ', 'my-login-form'); ?>' + data.data);
        }
    })
    .catch(error => {
        alert('<?php _e('Network error: ', 'my-login-form'); ?>' + error);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Create WordPress Users
function createWordPressUsers() {
    if (!confirm('<?php _e('Create WordPress user accounts for all registered users?', 'my-login-form'); ?>')) return;
    
    const button = event.target.closest('button');
    const originalText = button.innerHTML;
    button.innerHTML = '<i class="fas fa-spinner fa-spin"></i> <?php _e("Creating...", "my-login-form"); ?>';
    button.disabled = true;
    
    fetch('<?php echo admin_url('admin-ajax.php?action=my_login_form_create_wp_users'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            nonce: '<?php echo wp_create_nonce('my_login_form_dashboard_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.data.message);
        location.reload();
    })
    .catch(error => {
        alert('<?php _e('Network error: ', 'my-login-form'); ?>' + error);
    })
    .finally(() => {
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// View User Details
function viewUser(userId) {
    const modal = document.getElementById('userDetailsModal');
    const content = document.getElementById('userDetailsContent');
    
    modal.style.display = 'flex';
    content.innerHTML = '<div class="loading-spinner"><i class="fas fa-spinner fa-spin"></i> <?php _e("Loading user details...", "my-login-form"); ?></div>';
    
    fetch('<?php echo admin_url('admin-ajax.php?action=my_login_form_get_user'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('my_login_form_dashboard_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const user = data.data;
            let html = `
            <div class="user-details-grid">
                <div class="details-section">
                    <h3><i class="fas fa-user-circle"></i> <?php _e('Personal Information', 'my-login-form'); ?></h3>
                    <table class="details-table">
                        <tr><td class="label"><?php _e('Full Name', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.first_name)} ${escapeHtml(user.last_name)}</td></tr>
                        <tr><td class="label"><?php _e('Email', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.email)}</td></tr>
                        <tr><td class="label"><?php _e('Phone', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.phone || '—')}</td></tr>
                        <tr><td class="label"><?php _e('Gender', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.gender || '—')}</td></tr>
                        <tr><td class="label"><?php _e('Date of Birth', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.dob || '—')}</td></tr>
                        <tr><td class="label"><?php _e('Country', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.country || '—')}</td></tr>
                        <tr><td class="label"><?php _e('City', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.city || '—')}</td></tr>
                        <tr><td class="label"><?php _e('Address', 'my-login-form'); ?>:</td><td class="value">${escapeHtml(user.address || '—')}</td></tr>
                    </table>
                </div>
                
                <div class="details-section">
                    <h3><i class="fas fa-chart-line"></i> <?php _e('Account Information', 'my-login-form'); ?></h3>
                    <table class="details-table">
                        <tr><td class="label"><?php _e('Registered', 'my-login-form'); ?>:</td><td class="value">${new Date(user.created_at).toLocaleString()}</td></tr>
                        <tr><td class="label"><?php _e('Last Updated', 'my-login-form'); ?>:</td><td class="value">${new Date(user.updated_at).toLocaleString()}</td></tr>
                        <tr><td class="label"><?php _e('Last Login', 'my-login-form'); ?>:</td><td class="value">${user.last_login ? new Date(user.last_login).toLocaleString() : '—'}</td></tr>
                        <tr><td class="label"><?php _e('Login Count', 'my-login-form'); ?>:</td><td class="value">${user.login_count || 0}</td></tr>
                        <tr><td class="label"><?php _e('Email Verified', 'my-login-form'); ?>:</td><td class="value">${user.email_verified ? '✅ Yes' : '❌ No'}</td></tr>
                        <tr><td class="label"><?php _e('Phone Verified', 'my-login-form'); ?>:</td><td class="value">${user.phone_verified ? '✅ Yes' : '❌ No'}</td></tr>
                    </table>
                </div>
                
                <div class="details-section">
                    <h3><i class="fas fa-plug"></i> <?php _e('Integrations', 'my-login-form'); ?></h3>
                    <table class="details-table">
                        <tr><td class="label"><?php _e('WordPress User', 'my-login-form'); ?>:</td><td class="value">${user.wp_user_id ? '✓ ID: ' + user.wp_user_id : '✗ Not created'}</td></tr>
                        <tr><td class="label"><?php _e('Firebase Sync', 'my-login-form'); ?>:</td><td class="value">${user.firebase_uid ? '✓ Synced (UID: ' + user.firebase_uid.substring(0, 20) + '...)' : '✗ Not synced'}</td></tr>
                        <tr><td class="label"><?php _e('Social Login', 'my-login-form'); ?>:</td><td class="value">${user.social_provider ? user.social_provider.charAt(0).toUpperCase() + user.social_provider.slice(1) + ' (ID: ' + (user.social_id ? user.social_id.substring(0, 20) + '...' : 'N/A') + ')' : '✗ Email registration'}</td></tr>
                        <tr><td class="label"><?php _e('Profile Picture', 'my-login-form'); ?>:</td><td class="value">${user.profile_picture ? '<a href="' + escapeHtml(user.profile_picture) + '" target="_blank">View Image</a>' : '—'}</td></tr>
                    </table>
                </div>
            </div>
            `;
            content.innerHTML = html;
        } else {
            content.innerHTML = '<p style="color: #dc3545;"><?php _e('Error loading user details', 'my-login-form'); ?></p>';
        }
    })
    .catch(error => {
        content.innerHTML = '<p style="color: #dc3545;"><?php _e('Network error', 'my-login-form'); ?></p>';
    });
}

// Edit User
function editUser(userId) {
    alert('<?php _e('Edit user feature coming soon!', 'my-login-form'); ?>');
}

// Delete User
function deleteUser(userId) {
    if (!confirm('<?php _e('Are you sure you want to delete this user? This action cannot be undone.', 'my-login-form'); ?>')) {
        return;
    }
    
    fetch('<?php echo admin_url('admin-ajax.php?action=my_login_form_delete_user'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: new URLSearchParams({
            user_id: userId,
            nonce: '<?php echo wp_create_nonce('my_login_form_dashboard_nonce'); ?>'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            alert('<?php _e('User deleted successfully', 'my-login-form'); ?>');
            location.reload();
        } else {
            alert('<?php _e('Error: ', 'my-login-form'); ?>' + data.data);
        }
    })
    .catch(error => {
        alert('<?php _e('Network error: ', 'my-login-form'); ?>' + error);
    });
}

// Close user modal
function closeUserModal() {
    document.getElementById('userDetailsModal').style.display = 'none';
}

// Close modal on background click
document.getElementById('userDetailsModal')?.addEventListener('click', function(e) {
    if (e.target === this) {
        closeUserModal();
    }
});

// Close modal on escape key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeUserModal();
    }
});

// Helper function to escape HTML
function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
