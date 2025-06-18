<?php
/**
 * Plugin Name: WPNav Links
 * Plugin URI: https://wpnav.com/plugins/wpnav-links
 * Description: A simplified WordPress external link redirect tool with customizable redirect pages and basic security features.
 * Version: 1.2.1
 * Author: WPNav
 * Author URI: https://wpnav.com
 * Text Domain: wpnav-links
 * Domain Path: /languages
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 5.0
 * Tested up to: 6.4
 * Requires PHP: 7.4
 */

if (!defined('ABSPATH')) {
    exit;
}

define('WPNAV_LINKS_VERSION', '1.2.1');
define('WPNAV_LINKS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('WPNAV_LINKS_PLUGIN_URL', plugin_dir_url(__FILE__));
define('WPNAV_LINKS_DB_VERSION', '1.2.1');
define('WPNAV_LINKS_TABLE', 'nav_link_redirects');

require_once WPNAV_LINKS_PLUGIN_DIR . 'includes/class-wpnav-links.php';
require_once WPNAV_LINKS_PLUGIN_DIR . 'includes/class-admin.php';
require_once WPNAV_LINKS_PLUGIN_DIR . 'includes/class-public.php';

function wpnav_activate_plugin() {
    $plugin = new WPNAV_Links();
    $plugin->create_tables();

    $default_options = array(
        'enabled' => true,
        'auto_redirect_enabled' => true,
        'redirect_delay' => 5,
        'open_in_new_tab' => true,
        'url_format' => 'query',
        'auto_whitelist' => array(
            'same_root' => true,
            'search_engines' => true
        ),
        'whitelist_domains' => "google.com\nbaidu.com\nbing.com\nyoutube.com\nfacebook.com\ntwitter.com",
        'intercept_comments' => true,
        'intercept_widgets' => true,
        'exclude_css_class' => 'no-redirect',
        'show_external_icon' => true,
        'template' => 'default',
        'color_scheme' => 'blue',
        'page_title' => 'External Link Warning',
        'page_subtitle' => '',
        'url_label' => 'You are about to visit:',
        'warning_text' => 'You are about to leave this site and visit an external website. We are not responsible for the content of external websites.',
        'show_warning_message' => true,
        'show_logo' => false,
        'show_url_full' => false,
        'show_security_info' => true,
        'show_security_tips' => false,
        'show_back_button' => true,
        'custom_css' => '',
        'button_text_continue' => 'Continue',
        'button_text_back' => 'Back',
        'button_style' => 'rounded',
        'countdown_text' => 'Auto redirect in {seconds} seconds',
        'show_progress_bar' => false,
        'admin_exempt' => true,
        'cookie_duration' => 30,
        'stats_retention' => 90
    );

    $existing_options = get_option('wpnav_links_options', array());
    $merged_options = array_merge($default_options, $existing_options);
    update_option('wpnav_links_options', $merged_options);

    if (!wp_next_scheduled('wpnav_cleanup_old_data')) {
        wp_schedule_event(time(), 'daily', 'wpnav_cleanup_old_data');
    }

    flush_rewrite_rules();
    add_option('wpnav_links_activation_redirect', true);
}
register_activation_hook(__FILE__, 'wpnav_activate_plugin');

function wpnav_deactivate_plugin() {
    wp_clear_scheduled_hook('wpnav_cleanup_old_data');
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'wpnav_deactivate_plugin');

function wpnav_uninstall_plugin() {
    delete_option('wpnav_links_options');
    delete_option('wpnav_links_db_version');
    delete_option('wpnav_links_activation_redirect');

    global $wpdb;
    $table_name = $wpdb->prefix . WPNAV_LINKS_TABLE;
    $wpdb->query("DROP TABLE IF EXISTS $table_name");

    wp_clear_scheduled_hook('wpnav_cleanup_old_data');
}
register_uninstall_hook(__FILE__, 'wpnav_uninstall_plugin');

add_action('wpnav_cleanup_old_data', 'wpnav_clean_old_statistics');
function wpnav_clean_old_statistics() {
    $options = get_option('wpnav_links_options');
    $retention_days = isset($options['stats_retention']) ? intval($options['stats_retention']) : 90;

    $plugin = new WPNAV_Links();
    $deleted_count = $plugin->cleanup_old_data($retention_days);

    if (defined('WP_DEBUG') && WP_DEBUG) {
        error_log("WPNav Links: Cleaned up {$deleted_count} old records");
    }
}

function wpnav_run_plugin() {
    $plugin = new WPNAV_Links();
    $plugin->init();
}

add_action('plugins_loaded', 'wpnav_run_plugin');

add_action('admin_init', 'wpnav_activation_redirect');
function wpnav_activation_redirect() {
    if (get_option('wpnav_links_activation_redirect', false)) {
        delete_option('wpnav_links_activation_redirect');
        if (!isset($_GET['activate-multi'])) {
            wp_redirect(admin_url('tools.php?page=wpnav-links&tab=basic_settings'));
            exit;
        }
    }
}

add_action('wp_dashboard_setup', 'wpnav_add_dashboard_widget');
function wpnav_add_dashboard_widget() {
    if (current_user_can('manage_options')) {
        wp_add_dashboard_widget(
            'wpnav_links_stats',
            __('External Links Monitor', 'wpnav-links'),
            'wpnav_dashboard_widget_content',
            null,
            null,
            'normal',
            'high'
        );
    }
}

function wpnav_dashboard_widget_content() {
    $plugin = new WPNAV_Links();
    $options = get_option('wpnav_links_options', array());

    $total_redirects = $plugin->get_total_count();
    $today_redirects = $plugin->get_total_count(date('Y-m-d'), date('Y-m-d'));
    $recent_redirects = $plugin->get_total_count(date('Y-m-d', strtotime('-7 days')), date('Y-m-d'));
    $last_week_redirects = $plugin->get_total_count(date('Y-m-d', strtotime('-14 days')), date('Y-m-d', strtotime('-7 days')));
    $top_urls = $plugin->get_top_urls(3);

    $trend = 0;
    if ($last_week_redirects > 0) {
        $trend = (($recent_redirects - $last_week_redirects) / $last_week_redirects) * 100;
    } elseif ($recent_redirects > 0) {
        $trend = 100;
    }

    $plugin_enabled = !empty($options['enabled']);
    $auto_redirect = !empty($options['auto_redirect_enabled']);
    $whitelist_count = 0;
    if (!empty($options['whitelist_domains'])) {
        $domains = explode("\n", $options['whitelist_domains']);
        $whitelist_count = count(array_filter(array_map('trim', $domains)));
    }

    echo '<div class="wpnav-dashboard-widget">';

    echo '<div class="wpnav-status-bar">';
    echo '<span class="wpnav-status-item ' . ($plugin_enabled ? 'active' : 'inactive') . '">';
    echo '<span class="wpnav-status-dot"></span>';
    echo ($plugin_enabled ? esc_html__('Active', 'wpnav-links') : esc_html__('Inactive', 'wpnav-links'));
    echo '</span>';
    if ($plugin_enabled && !$auto_redirect) {
        echo '<span class="wpnav-warning-badge">' . esc_html__('Manual Only', 'wpnav-links') . '</span>';
    }
    echo '</div>';

    echo '<div class="wpnav-stats-grid">';

    echo '<div class="wpnav-stat-card primary">';
    echo '<div class="wpnav-stat-number">' . number_format($total_redirects) . '</div>';
    echo '<div class="wpnav-stat-label">' . esc_html__('Total Redirects', 'wpnav-links') . '</div>';
    echo '</div>';

    echo '<div class="wpnav-stat-card">';
    echo '<div class="wpnav-stat-number">' . number_format($today_redirects) . '</div>';
    echo '<div class="wpnav-stat-label">' . esc_html__('Today', 'wpnav-links') . '</div>';
    echo '</div>';

    echo '<div class="wpnav-stat-card">';
    echo '<div class="wpnav-stat-number">' . number_format($recent_redirects);
    if ($trend != 0) {
        $trend_class = $trend > 0 ? 'up' : 'down';
        $trend_icon = $trend > 0 ? '↗' : '↘';
        echo '<span class="wpnav-trend ' . $trend_class . '">' . $trend_icon . abs(round($trend)) . '%</span>';
    }
    echo '</div>';
    echo '<div class="wpnav-stat-label">' . esc_html__('Last 7 Days', 'wpnav-links') . '</div>';
    echo '</div>';

    echo '<div class="wpnav-stat-card">';
    echo '<div class="wpnav-stat-number">' . number_format($whitelist_count) . '</div>';
    echo '<div class="wpnav-stat-label">' . esc_html__('Whitelisted', 'wpnav-links') . '</div>';
    echo '</div>';

    echo '</div>';

    if (!empty($top_urls)) {
        echo '<div class="wpnav-top-domains">';
        echo '<h4>' . esc_html__('Top Domains This Week', 'wpnav-links') . '</h4>';
        echo '<ul class="wpnav-domain-list">';
        foreach ($top_urls as $index => $url) {
            $domain = parse_url($url->target_url, PHP_URL_HOST);
            $is_https = strpos($url->target_url, 'https://') === 0;
            echo '<li class="wpnav-domain-item">';
            echo '<span class="wpnav-rank">' . ($index + 1) . '</span>';
            echo '<span class="wpnav-domain">' . esc_html($domain) . '</span>';
            echo '<span class="wpnav-https-indicator ' . ($is_https ? 'secure' : 'insecure') . '"></span>';
            echo '<span class="wpnav-count">' . number_format($url->count) . '</span>';
            echo '</li>';
        }
        echo '</ul>';
        echo '</div>';
    } else {
        echo '<div class="wpnav-no-data">';
        echo '<p>' . esc_html__('No external link activity yet.', 'wpnav-links') . '</p>';
        if (!$plugin_enabled) {
            echo '<p><small>' . esc_html__('Enable the plugin to start tracking external links.', 'wpnav-links') . '</small></p>';
        }
        echo '</div>';
    }

    echo '<div class="wpnav-quick-actions">';
    echo '<div class="wpnav-action-buttons">';

    if (!$plugin_enabled) {
        echo '<a href="' . admin_url('tools.php?page=wpnav-links&tab=basic_settings') . '" class="button button-primary button-small">' . esc_html__('Enable Plugin', 'wpnav-links') . '</a>';
    } else {
        echo '<a href="' . admin_url('tools.php?page=wpnav-links&tab=logs_statistics') . '" class="button button-small">' . esc_html__('View Details', 'wpnav-links') . '</a>';
    }

    echo '<a href="' . admin_url('tools.php?page=wpnav-links&tab=whitelist') . '" class="button button-small">' . esc_html__('Manage Whitelist', 'wpnav-links') . '</a>';
    echo '</div>';
    echo '</div>';

    echo '</div>';

    echo '<style>
    .wpnav-dashboard-widget {
        font-size: 13px;
    }

    .wpnav-status-bar {
        display: flex;
        align-items: center;
        justify-content: space-between;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 1px solid #ddd;
    }

    .wpnav-status-item {
        display: flex;
        align-items: center;
        gap: 6px;
        font-weight: 600;
        font-size: 12px;
    }

    .wpnav-status-item.active {
        color: #00a32a;
    }

    .wpnav-status-item.inactive {
        color: #d63638;
    }

    .wpnav-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        background: currentColor;
        flex-shrink: 0;
    }

    .wpnav-warning-badge {
        background: #f0b849;
        color: white;
        padding: 2px 6px;
        border-radius: 10px;
        font-size: 10px;
        font-weight: 600;
    }

    .wpnav-stats-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px;
        margin-bottom: 15px;
    }

    .wpnav-stat-card {
        background: #f8f9fa;
        border: 1px solid #e2e4e7;
        border-radius: 4px;
        padding: 12px 10px;
        text-align: center;
        position: relative;
    }

    .wpnav-stat-card.primary {
        background: #e7f3ff;
        border-color: #72aee6;
    }

    .wpnav-stat-number {
        font-size: 18px;
        font-weight: 700;
        color: #2c3338;
        line-height: 1.2;
        margin-bottom: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }

    .wpnav-stat-label {
        font-size: 11px;
        color: #646970;
        font-weight: 500;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .wpnav-trend {
        font-size: 10px;
        padding: 1px 3px;
        border-radius: 2px;
        font-weight: 600;
    }

    .wpnav-trend.up {
        background: #d1e7dd;
        color: #0a3622;
    }

    .wpnav-trend.down {
        background: #f8d7da;
        color: #58151c;
    }

    .wpnav-top-domains h4 {
        margin: 0 0 10px;
        font-size: 13px;
        color: #2c3338;
        font-weight: 600;
    }

    .wpnav-domain-list {
        margin: 0;
        padding: 0;
        list-style: none;
    }

    .wpnav-domain-item {
        display: flex;
        align-items: center;
        padding: 6px 0;
        border-bottom: 1px solid #f0f0f1;
        gap: 8px;
    }

    .wpnav-domain-item:last-child {
        border-bottom: none;
    }

    .wpnav-rank {
        background: #2271b1;
        color: white;
        width: 16px;
        height: 16px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 10px;
        font-weight: 700;
        flex-shrink: 0;
    }

    .wpnav-domain {
        flex: 1;
        font-size: 12px;
        color: #2c3338;
        font-weight: 500;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }

    .wpnav-https-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        flex-shrink: 0;
    }

    .wpnav-https-indicator.secure {
        background: #00a32a;
    }

    .wpnav-https-indicator.insecure {
        background: #d63638;
    }

    .wpnav-count {
        font-size: 11px;
        color: #646970;
        font-weight: 600;
        flex-shrink: 0;
    }

    .wpnav-no-data {
        text-align: center;
        padding: 20px 0;
        color: #646970;
    }

    .wpnav-no-data p {
        margin: 5px 0;
    }

    .wpnav-quick-actions {
        margin-top: 15px;
        padding-top: 10px;
        border-top: 1px solid #ddd;
    }

    .wpnav-action-buttons {
        display: flex;
        gap: 8px;
        flex-wrap: wrap;
    }

    .wpnav-action-buttons .button {
        font-size: 11px;
        padding: 4px 8px;
        height: auto;
        line-height: 1.4;
        text-decoration: none;
    }

    @media screen and (max-width: 782px) {
        .wpnav-stats-grid {
            grid-template-columns: 1fr;
        }

        .wpnav-action-buttons {
            flex-direction: column;
        }

        .wpnav-action-buttons .button {
            text-align: center;
        }
    }
    </style>';
}

add_filter('plugin_row_meta', 'wpnav_plugin_row_meta', 10, 2);
function wpnav_plugin_row_meta($links, $file) {
    if (plugin_basename(__FILE__) === $file) {
        $row_meta = array(
            'support' => '<a href="https://sharecms.com/forums" target="_blank">' . esc_html__('Support', 'wpnav-links') . '</a>',
            'document' => '<a href="https://wpnav.com/document/wpnav-links" target="_blank">' . esc_html__('Documentation', 'wpnav-links') . '</a>',
        );
        return array_merge($links, $row_meta);
    }
    return $links;
}

add_action('init', 'wpnav_check_database_version');
function wpnav_check_database_version() {
    $installed_version = get_option('wpnav_links_db_version');

    if ($installed_version !== WPNAV_LINKS_DB_VERSION) {
        $plugin = new WPNAV_Links();
        $plugin->create_tables();
        update_option('wpnav_links_db_version', WPNAV_LINKS_DB_VERSION);
    }
}

add_action('wp_enqueue_scripts', 'wpnav_enqueue_frontend_styles');
function wpnav_enqueue_frontend_styles() {
    $options = get_option('wpnav_links_options');
    if (!isset($options['enabled']) || !$options['enabled']) {
        return;
    }

    if (!is_admin() && !empty($options['show_external_icon'])) {
        wp_enqueue_style(
            'wpnav-external-indicator',
            WPNAV_LINKS_PLUGIN_URL . 'assets/css/external-indicator.css',
            array(),
            WPNAV_LINKS_VERSION
        );
    }
}

if (!wp_next_scheduled('wpnav_daily_maintenance')) {
    wp_schedule_event(time(), 'daily', 'wpnav_daily_maintenance');
}

add_action('wpnav_daily_maintenance', 'wpnav_daily_maintenance_tasks');
function wpnav_daily_maintenance_tasks() {
    wp_cache_flush();

    global $wpdb;
    $table_name = $wpdb->prefix . WPNAV_LINKS_TABLE;
    $wpdb->query("OPTIMIZE TABLE $table_name");
}

require_once plugin_dir_path(__FILE__) . 'lib/plugin-update-checker/plugin-update-checker.php';
use YahnisElsts\PluginUpdateChecker\v5p3\PucFactory;

$WpNavLinksUpdateChecker = PucFactory::buildUpdateChecker(
    'https://updates.weixiaoduo.com/wpnav-links.json',
    __FILE__,
    'wpnav-links'
);