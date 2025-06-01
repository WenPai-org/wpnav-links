<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPNAV_Admin {
    private $options;
    private $plugin;

    public function __construct($options) {
        $this->options = $options;
        $this->plugin = new WPNAV_Links();

        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_filter('plugin_action_links_wpnav-links/wpnav-links.php', array($this, 'add_settings_link'));
        add_action('wp_ajax_wpnav_export_stats', array($this, 'ajax_export_stats'));
    }

    public function add_admin_menu() {
        add_submenu_page(
            'tools.php',
            __('External Links Redirect', 'wpnav-links'),
            __('External Links', 'wpnav-links'),
            'manage_options',
            'wpnav-links',
            array($this, 'display_admin_page')
        );
    }

    public function enqueue_admin_scripts($hook) {
        if ($hook !== 'tools_page_wpnav-links') {
            return;
        }

        wp_enqueue_style(
            'wpnav-admin-style',
            WPNAV_LINKS_PLUGIN_URL . 'assets/css/admin.css',
            array(),
            WPNAV_LINKS_VERSION
        );

        wp_enqueue_script(
            'wpnav-admin-script',
            WPNAV_LINKS_PLUGIN_URL . 'assets/js/admin.js',
            array('jquery'),
            WPNAV_LINKS_VERSION,
            true
        );

        wp_localize_script(
            'wpnav-admin-script',
            'wpnav_admin',
            array(
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('wpnav_admin_nonce'),
                'strings' => array(
                    'domain_exists' => __('Domain %s is already in the whitelist.', 'wpnav-links'),
                    'domain_added' => __('Added %s to whitelist.', 'wpnav-links'),
                    'common_domains_added' => __('Added common domains to whitelist.', 'wpnav-links'),
                    'whitelist_cleared' => __('Whitelist cleared.', 'wpnav-links'),
                    'confirm_clear' => __('Are you sure you want to clear all domains from the whitelist?', 'wpnav-links'),
                    'whitelist_empty' => __('Whitelist is empty, cannot export.', 'wpnav-links'),
                    'export_success' => __('Whitelist exported successfully.', 'wpnav-links'),
                    'export_unsupported' => __('Export not supported in this browser.', 'wpnav-links'),
                    'saving' => __('Saving...', 'wpnav-links'),
                    'draft_saved' => __('Draft saved', 'wpnav-links'),
                    'restore_draft' => __('A draft was found for this field. Would you like to restore it?', 'wpnav-links'),
                    'settings_saved' => __('Settings saved successfully!', 'wpnav-links'),
                    'domains_imported' => __('Successfully imported %d domains.', 'wpnav-links')
                )
            )
        );
    }

    public function add_settings_link($links) {
        $settings_link = '<a href="' . admin_url('tools.php?page=wpnav-links') . '">' . __('Settings', 'wpnav-links') . '</a>';
        array_unshift($links, $settings_link);
        return $links;
    }

    public function display_admin_page() {
        include WPNAV_LINKS_PLUGIN_DIR . 'admin/admin-page.php';
    }

    public function ajax_export_stats() {
        if (!check_ajax_referer('wpnav_admin_nonce', 'nonce', false)) {
            wp_send_json_error(array('message' => __('Security check failed', 'wpnav-links')));
        }

        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Insufficient permissions', 'wpnav-links')));
        }

        $start_date = isset($_POST['start_date']) ? sanitize_text_field($_POST['start_date']) : '';
        $end_date = isset($_POST['end_date']) ? sanitize_text_field($_POST['end_date']) : '';

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename=wpnav_stats_' . date('Y-m-d') . '.csv');

        $output = fopen('php://output', 'w');

        fputcsv($output, array(
            __('Target URL', 'wpnav-links'),
            __('Source Page', 'wpnav-links'),
            __('Click Time', 'wpnav-links'),
            __('IP Address', 'wpnav-links'),
            __('User Agent', 'wpnav-links')
        ));

        $stats = $this->plugin->get_stats(array(
            'start_date' => $start_date,
            'end_date' => $end_date,
            'limit' => 5000
        ));

        foreach ($stats as $row) {
            fputcsv($output, array(
                $row->target_url,
                $row->source_page,
                $row->click_time,
                $row->user_ip,
                isset($row->user_agent) ? $row->user_agent : ''
            ));
        }

        fclose($output);
        wp_die();
    }
}
