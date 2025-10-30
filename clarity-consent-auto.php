<?php
/**
 * Clarity Consent Auto
 *
 * @package           Clarity_Consent_Auto
 * @author            Gioxx
 * @copyright         2025 Gioxx
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Clarity Consent Auto
 * Plugin URI:        https://go.gioxx.org/clarityconsentauto
 * Description:       Consent layer for Microsoft Clarity - automatically grants consent using existing Clarity configuration.
 * Version:           2.0.1
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * Author:            Gioxx
 * Author URI:        https://gioxx.org
 * Text Domain:       clarity-consent-auto
 * Domain Path:       /languages
 * License:           GPL v2 or later
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

class ClarityConsentLayer {
    
    private $detected_project_id = null;
    private $detected_plugin = null;
    
    public function __construct() {
        add_action('wp_enqueue_scripts', array($this, 'inject_consent_script'));
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'init_settings'));
        add_action('admin_notices', array($this, 'show_status_notice'));
        $this->detect_existing_clarity(); // Always detect existing Project ID
    }

    /**
     * Detect existing Clarity configurations
     */
    private function detect_existing_clarity() {
        // METHOD 1: Check if we already have a saved ID
        $saved_id = get_option('clarity_project_id');
        if ($saved_id && $this->is_valid_clarity_id($saved_id)) {
            $this->detected_project_id = $saved_id;
            $this->detected_plugin = esc_html__('Previously saved ID', 'clarity-consent-auto');
            return;
        }
        
        // METHOD 2: Check if Microsoft Clarity plugin is active
        if (!function_exists('is_plugin_active')) {
            include_once(ABSPATH . 'wp-admin/includes/plugin.php');
        }
        
        if (is_plugin_active('microsoft-clarity/clarity.php')) {
            $this->detected_plugin = esc_html__('Microsoft Clarity Plugin (active)', 'clarity-consent-auto');
            return;
        }
        
        // METHOD 3: Check specific known options
        $known_clarity_options = array(
            'microsoft_clarity_project_id',
            'microsoft_clarity_settings',
            'clarity_settings',
            'seopress_analytics_option_name',
            'seopress_analytics_clarity',
            'siteseo_analytics_clarity_project_id',
            'aioseo_options'
        );
        
        foreach ($known_clarity_options as $option_name) {
            $option_value = get_option($option_name);
            if ($option_value) {
                $project_id = $this->extract_project_id($option_value);
                if ($project_id) {
                    $this->detected_project_id = $project_id;
                    $this->detected_plugin = esc_html__('Detected from:', 'clarity-consent-auto') . ' ' . $option_name;
                    return;
                }
            }
        }
    }
    
    /**
     * Extract Project ID from various configurations
     */
    private function extract_project_id($value) {
        if (empty($value)) return false;
        
        if (is_array($value) || is_object($value)) {
            $value = maybe_serialize($value);
        }
        
        $value = (string) $value;
        
        // Patterns for Clarity Project ID
        $patterns = array(
            '/clarity\.ms\/tag\/([a-zA-Z0-9]{8,15})/',
            '/([a-zA-Z0-9]{8,15})/' // Generic pattern for IDs like "aq9itx5whc"
        );
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $value, $matches)) {
                if (isset($matches[1]) && $this->is_valid_clarity_id($matches[1])) {
                    return $matches[1];
                }
            }
        }
        
        return false;
    }
    
    /**
     * Validate Clarity Project ID
     */
    private function is_valid_clarity_id($id) {
        return (
            strlen($id) >= 8 && strlen($id) <= 15 &&
            preg_match('/^[a-zA-Z0-9]+$/', $id) &&
            preg_match('/[0-9]/', $id) &&
            !in_array(strtolower($id), ['switching', 'wordpress', 'settings'])
        );
    }
    
    /**
     * Inject only consent script (not Clarity itself)
     */
    public function inject_consent_script() {
        // If no Project ID detected, do nothing
        if (!$this->detected_project_id) {
            return;
        }
        
        // Inject only consent script, not Clarity
        wp_enqueue_script(
            'clarity-consent-layer',
            plugin_dir_url(__FILE__) . 'js/clarity-consent-layer.js',
            array(),
            '2.0.1',
            true
        );
        
        // Pass only consent settings
        wp_localize_script('clarity-consent-layer', 'clarityConsent', array(
            'adStorage' => get_option('clarity_ad_storage', 'granted'),
            'analyticsStorage' => get_option('clarity_analytics_storage', 'granted')
        ));
    }
    
    /**
     * Status notice
     */
    public function show_status_notice() {
        // Only if Microsoft plugin is active but we don't have the ID
        if ($this->detected_plugin === esc_html__('Microsoft Clarity Plugin (active)', 'clarity-consent-auto') && !$this->detected_project_id) {
            ?>
            <div class="notice notice-info is-dismissible">
                <p>
                    <strong>Clarity Consent Auto:</strong> 
                    <?php esc_html_e('Microsoft Clarity plugin detected. Please complete setup to start to use Clarity Consent Auto plugin.', 'clarity-consent-auto'); ?>
                </p>
            </div>
            <?php
        }
    }
    
    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_options_page(
            esc_html__('Clarity Consent Layer', 'clarity-consent-auto'),
            esc_html__('Clarity Consent', 'clarity-consent-auto'),
            'manage_options',
            'clarity-consent-auto',
            array($this, 'admin_page')
        );
    }

    /**
     * Initialize settings
     */
    public function init_settings() {
        register_setting(
            'clarity_consent_settings', 
            'clarity_ad_storage',
            array(
                'type' => 'string',
                'sanitize_callback' => array($this, 'sanitize_consent_option'),
                'default' => 'granted'
            )
        );
        
        register_setting(
            'clarity_consent_settings', 
            'clarity_analytics_storage',
            array(
                'type' => 'string', 
                'sanitize_callback' => array($this, 'sanitize_consent_option'),
                'default' => 'granted'
            )
        );
    }

    /**
     * Sanitize consent options - only allow 'granted' or 'denied'
     */
    public function sanitize_consent_option($input) {
        $valid_options = array('granted', 'denied'); // List of accepted values
        $sanitized = sanitize_text_field($input); // Sanitize input
        
        if (in_array($sanitized, $valid_options, true)) {
            return $sanitized; // Check if the value is valid
        }

        return 'granted'; // If invalid, returns default
    }

    /**
     * Admin page
     */
    public function admin_page() {
        // Re-detect on every load
        $this->detect_existing_clarity();
        $is_ms_plugin_active = is_plugin_active('microsoft-clarity/clarity.php');
        
        ?>
        <div class="wrap">
            <h1><?php esc_html_e('Clarity Consent Layer', 'clarity-consent-auto'); ?></h1>
            <p class="description"><?php esc_html_e('Automatic consent layer for Microsoft Clarity.', 'clarity-consent-auto'); ?></p>

            <p class="description">
                <?php esc_html_e('Learn more', 'clarity-consent-auto'); ?>:<br />
                <span class="dashicons dashicons-external"></span> <a href="https://learn.microsoft.com/en-us/clarity/setup-and-installation/consent-management" target="_blank">Clarity Consent Management</a><br>
                <span class="dashicons dashicons-external"></span> <a href="https://learn.microsoft.com/en-us/clarity/setup-and-installation/clarity-consent-api-v2" target="_blank">Clarity Consent API v2</a>
            </p>

            <!-- CLARITY STATUS -->
            <div class="card" style="margin-bottom: 20px;">
                <h2 class="title">📊 <?php esc_html_e('Microsoft Clarity Status', 'clarity-consent-auto'); ?></h2>
                <table class="form-table" style="margin-top: 0;">
                    <tr>
                        <th scope="row" style="width: 200px;"><?php esc_html_e('Microsoft Clarity Plugin', 'clarity-consent-auto'); ?></th>
                        <td>
                            <?php if ($is_ms_plugin_active): ?>
                                <span style="color: #46b450; font-weight: 500;">✅ <?php esc_html_e('Active', 'clarity-consent-auto'); ?></span>
                            <?php else: ?>
                                <span style="color: #dc3232; font-weight: 500;">❌ <?php esc_html_e('Not installed/active', 'clarity-consent-auto'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <?php if ($is_ms_plugin_active): ?>
                    <tr>
                        <th scope="row"><?php esc_html_e('Project ID', 'clarity-consent-auto'); ?></th>
                        <td>
                            <?php if ($this->detected_project_id): ?>
                                <code style="font-size: 14px; background: #e7f7e7; padding: 5px 8px; border-radius: 3px;">
                                    <?php echo esc_html($this->detected_project_id); ?>
                                </code>
                                <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 5px;"></span>
                                <br><small><?php 
                                    // translators: %s is the source/origin of the detected Project ID (e.g., "Previously saved ID", "Microsoft Clarity Plugin")
                                    echo sprintf(esc_html__('Source: %s', 'clarity-consent-auto'), esc_html($this->detected_plugin)); 
                                ?></small>
                            <?php else: ?>
                                <span style="color: #f56e28; font-weight: 500;">⚠️ <?php esc_html_e('Not detected', 'clarity-consent-auto'); ?></span>
                                <br><small><?php esc_html_e('Configure Microsoft Clarity plugin with a Project ID first', 'clarity-consent-auto'); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th scope="row"><?php esc_html_e('Consent Layer', 'clarity-consent-auto'); ?></th>
                        <td>
                            <?php if ($this->detected_project_id): ?>
                                <span style="color: #46b450; font-weight: 500;">✅ <?php esc_html_e('Active', 'clarity-consent-auto'); ?></span>
                                <br><small><?php esc_html_e('Consent is automatically passed to Clarity', 'clarity-consent-auto'); ?></small>
                            <?php else: ?>
                                <span style="color: #dc3232; font-weight: 500;">⏸️ <?php esc_html_e('On Standby', 'clarity-consent-auto'); ?></span>
                                <br><small><?php esc_html_e('Waiting for Project ID from Microsoft Clarity plugin', 'clarity-consent-auto'); ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
            
            <?php if (!$is_ms_plugin_active): ?>
                <!-- MICROSOFT CLARITY PLUGIN NOT ACTIVE - SETUP INSTRUCTIONS -->
                <div class="notice notice-warning">
                    <h3 style="margin-top: 15px;">👉 <?php esc_html_e('Microsoft Clarity Plugin Required', 'clarity-consent-auto'); ?></h3>
                    <p><?php esc_html_e('This consent layer requires the official Microsoft Clarity plugin to be installed and active.', 'clarity-consent-auto'); ?></p>
                    <p><strong><?php esc_html_e('Setup steps:', 'clarity-consent-auto'); ?></strong></p>
                    <ol style="margin-left: 20px;">
                        <li><?php esc_html_e('Install the official Microsoft Clarity plugin from WordPress repository', 'clarity-consent-auto'); ?></li>
                        <li><?php esc_html_e('Activate the Microsoft Clarity plugin', 'clarity-consent-auto'); ?></li>
                        <li><?php esc_html_e('Configure Microsoft Clarity with your Project ID', 'clarity-consent-auto'); ?></li>
                        <li><?php esc_html_e('Return to this page to configure consent settings', 'clarity-consent-auto'); ?></li>
                    </ol>
                    
                    <p style="margin-top: 20px;">
                        <a href="<?php echo esc_url(admin_url('plugin-install.php?s=microsoft+clarity&tab=search&type=term')); ?>" class="button button-primary">
                            🔍 <?php esc_html_e('Search for Microsoft Clarity Plugin', 'clarity-consent-auto'); ?>
                        </a>
                        <a href="<?php echo esc_url(admin_url('plugins.php')); ?>" class="button button-secondary" style="margin-left: 10px;">
                            🔧 <?php esc_html_e('Manage Plugins', 'clarity-consent-auto'); ?>
                        </a>
                    </p>
                    
                    <hr style="margin: 20px 0;">
                    <p>⚠️ <em><?php esc_html_e('Once the Microsoft Clarity plugin is active, configuration options will appear below.', 'clarity-consent-auto'); ?></em></p>
                </div>
                
            <?php else: ?>
                <!-- MICROSOFT CLARITY PLUGIN IS ACTIVE - SHOW CONFIGURATION -->
                
                <?php if (!$this->detected_project_id): ?>
                <!-- PROJECT ID MISSING -->
                <div class="notice notice-info inline">
                    <p>
                        <strong>🔧 <?php esc_html_e('Configuration needed:', 'clarity-consent-auto'); ?></strong><br>
                        <?php esc_html_e('Microsoft Clarity plugin is active but no Project ID was detected.', 'clarity-consent-auto'); ?>
                        <br><?php esc_html_e('Please configure your Project ID in the Microsoft Clarity plugin settings.', 'clarity-consent-auto'); ?>
                    </p>
                </div>
                <?php endif; ?>
                
                <!-- CONFIGURATION FORM - ONLY SHOWN WHEN MS CLARITY IS ACTIVE -->
                <h2>⚙️ <?php esc_html_e('Consent Configuration', 'clarity-consent-auto'); ?></h2>
                <p class="description"><?php esc_html_e('Configure how consent is automatically granted to Microsoft Clarity:', 'clarity-consent-auto'); ?></p>

                <form method="post" action="options.php">
                    <?php settings_fields('clarity_consent_settings'); ?>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Ad Storage Consent', 'clarity-consent-auto'); ?></th>
                            <td>
                                <select name="clarity_ad_storage">
                                    <option value="granted" <?php selected(get_option('clarity_ad_storage', 'granted'), 'granted'); ?>><?php esc_html_e('Granted (Allow)', 'clarity-consent-auto'); ?></option>
                                    <option value="denied" <?php selected(get_option('clarity_ad_storage', 'granted'), 'denied'); ?>><?php esc_html_e('Denied (Deny)', 'clarity-consent-auto'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Consent for storing advertising-related data', 'clarity-consent-auto'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Analytics Storage Consent', 'clarity-consent-auto'); ?></th>
                            <td>
                                <select name="clarity_analytics_storage">
                                    <option value="granted" <?php selected(get_option('clarity_analytics_storage', 'granted'), 'granted'); ?>><?php esc_html_e('Granted (Allow)', 'clarity-consent-auto'); ?></option>
                                    <option value="denied" <?php selected(get_option('clarity_analytics_storage', 'granted'), 'denied'); ?>><?php esc_html_e('Denied (Deny)', 'clarity-consent-auto'); ?></option>
                                </select>
                                <p class="description"><?php esc_html_e('Consent for storing analytics data', 'clarity-consent-auto'); ?></p>
                            </td>
                        </tr>
                    </table>
                    
                    <?php submit_button('💾 ' . esc_html__('Save settings', 'clarity-consent-auto')); ?>
                </form>
            <?php endif; ?>

            <div class="footer" style="padding-top: 35px;">
                <hr>
                <span class="dashicons dashicons-superhero"></span> Gioxx, <?php echo esc_html( gmdate( 'Y' ) ); ?> &#x2022; 
                <span class="dashicons dashicons-wordpress"></span> 
                <a href="<?php echo esc_url( 'https://go.gioxx.org/clarityconsentauto' ); ?>">Gioxx.org</a> &#x2022; 
                <span class="dashicons dashicons-heart"></span> 
                <a href="<?php echo esc_url( 'https://github.com/gioxx/clarity-consent-auto' ); ?>">GitHub</a>
            </div>
        </div>
        
        <style>
        .card { 
            background: #fff; 
            border: 1px solid #ccd0d4; 
            padding: 15px; 
            border-radius: 4px; 
        }
        .card .title { 
            margin-top: 0; 
            font-size: 16px; 
        }
        </style>
        <?php
    }
}

// Initialize the plugin
new ClarityConsentLayer();
?>
