<?php
/**
 * uninstall.php - Simple Clarity Consent Layer Cleanup
 */

if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Options to remove
$options = array(
    // Current plugin options
    'clarity_ad_storage',
    'clarity_analytics_storage',
    
    // Legacy cleanup
    'clarity_project_id',
    'clarity_auto_project_id', 
    'clarity_detected_from',
    'clarity_detection_notice_dismissed',
    'clarity_wordpress_site_id'
);

// Remove options
foreach ($options as $option) {
    delete_option($option);
    delete_site_option($option); // Multisite support
}

// Remove transients
$transients = array(
    'clarity_consent_temp',
    'clarity_consent_cache', 
    'clarity_layer_temp',
    'clarity_consent_auto_detection'
);

foreach ($transients as $transient) {
    delete_transient($transient);
    delete_site_transient($transient);
}
?>
