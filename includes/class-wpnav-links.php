<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPNAV_Links {
    private $options;
    private $table_name;

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . WPNAV_LINKS_TABLE;
    }

    public function init() {
        add_action('plugins_loaded', array($this, 'load_textdomain'));

        $this->options = get_option('wpnav_links_options');

        if (is_admin()) {
            new WPNAV_Admin($this->options);
        }

        new WPNAV_Public($this->options, $this);

        add_action('init', array($this, 'add_rewrite_rules'));
        add_filter('query_vars', array($this, 'add_query_vars'));
        add_action('template_redirect', array($this, 'handle_external_redirect'));
        add_action('admin_bar_menu', array($this, 'add_admin_bar_menu'), 100);
    }

    public function load_textdomain() {
        load_plugin_textdomain('wpnav-links', false, dirname(plugin_basename(__FILE__)) . '/languages/');
    }

    public function add_rewrite_rules() {
        $url_format = isset($this->options['url_format']) ? $this->options['url_format'] : 'query';

        if ($url_format === 'path') {
            add_rewrite_rule('^go/([^/]+)/?$', 'index.php?wpnav_redirect=1&url_param=$matches[1]', 'top');
        } else {
            add_rewrite_rule('^go/?$', 'index.php?wpnav_redirect=1', 'top');
        }
    }

    public function add_query_vars($vars) {
        $vars[] = 'wpnav_redirect';
        $vars[] = 'target';
        $vars[] = 'url_param';
        $vars[] = 'ref';
        $vars[] = 'url';
        return $vars;
    }

    public function handle_external_redirect() {
        global $wp_query;

        if (!isset($wp_query->query_vars['wpnav_redirect']) || $wp_query->query_vars['wpnav_redirect'] != 1) {
            return;
        }

        $url = $this->get_redirect_url_from_request();

        if (empty($url)) {
            $this->handle_redirect_error(__('Invalid or missing URL parameter', 'wpnav-links'));
            return;
        }

        if (!$this->validate_url($url)) {
            $this->handle_redirect_error(__('Invalid URL format', 'wpnav-links'));
            return;
        }

        $ref = isset($wp_query->query_vars['ref']) ? esc_url_raw($wp_query->query_vars['ref']) : wp_get_referer();

        set_query_var('target_url', $url);
        set_query_var('source_url', $ref);

        if ($this->should_skip_redirect($url)) {
            wp_redirect($url, 302);
            exit;
        }

        $this->record_redirect($url, $ref);
        $this->load_redirect_template($url, $ref);
        exit;
    }

    private function get_redirect_url_from_request() {
        global $wp_query;

        $url_format = isset($this->options['url_format']) ? $this->options['url_format'] : 'query';
        $url = '';

        if ($url_format === 'path') {
            if (isset($wp_query->query_vars['url_param'])) {
                $url_param = $wp_query->query_vars['url_param'];
                $decoded = base64_decode($url_param);
                if ($decoded !== false) {
                    $url = $decoded;
                }
            }
        } elseif ($url_format === 'target') {
            if (isset($wp_query->query_vars['target'])) {
                $url = urldecode($wp_query->query_vars['target']);
            } elseif (isset($_GET['target'])) {
                $url = urldecode(sanitize_text_field($_GET['target']));
            }
        } else {
            if (isset($wp_query->query_vars['target'])) {
                $url = urldecode($wp_query->query_vars['target']);
            } elseif (isset($_GET['target'])) {
                $url = urldecode(sanitize_text_field($_GET['target']));
            } elseif (isset($wp_query->query_vars['url'])) {
                $url_param = $wp_query->query_vars['url'];
                $decoded = base64_decode($url_param);
                if ($decoded !== false) {
                    $url = $decoded;
                } else {
                    $url = urldecode($url_param);
                }
            } elseif (isset($_GET['url'])) {
                $url_param = sanitize_text_field($_GET['url']);
                $decoded = base64_decode($url_param);
                if ($decoded !== false) {
                    $url = $decoded;
                } else {
                    $url = urldecode($url_param);
                }
            }
        }

        if (!empty($url) && strpos($url, 'http') !== 0) {
            $url = 'http://' . $url;
        }

        return $url;
    }

    private function handle_redirect_error($message) {
        if (WP_DEBUG) {
            error_log('WPNAV Redirect Error: ' . $message);
        }

        wp_die(
            esc_html__('Redirect Error: ', 'wpnav-links') . esc_html($message),
            esc_html__('External Link Redirect Error', 'wpnav-links'),
            array(
                'response' => 400,
                'back_link' => true
            )
        );
    }

    private function should_skip_redirect($url) {
        if (!empty($this->options['admin_exempt']) && current_user_can('manage_options')) {
            return true;
        }

        if ($this->check_whitelist_domain($url)) {
            return true;
        }

        if (isset($_COOKIE['wpnav_noredirect']) && $_COOKIE['wpnav_noredirect'] == '1') {
            return true;
        }

        return false;
    }

    public function check_whitelist_domain($url) {
        $domain = parse_url($url, PHP_URL_HOST);
        if (empty($domain)) {
            return false;
        }

        $whitelist = array();
        if (!empty($this->options['whitelist_domains'])) {
            $whitelist = explode("\n", $this->options['whitelist_domains']);
            $whitelist = array_map('trim', $whitelist);
        }

        if (in_array($domain, $whitelist)) {
            return true;
        }

        foreach ($whitelist as $pattern) {
            if (strpos($pattern, '*') !== false) {
                $pattern = str_replace('*', '(.*)', preg_quote($pattern, '/'));
                if (preg_match('/^' . $pattern . '$/i', $domain)) {
                    return true;
                }
            }
        }

        if (!empty($this->options['auto_whitelist']['same_root'])) {
            $site_domain = parse_url(home_url(), PHP_URL_HOST);
            $site_root = $this->get_root_domain($site_domain);
            $url_root = $this->get_root_domain($domain);

            if ($site_root == $url_root) {
                return true;
            }
        }

        if (!empty($this->options['auto_whitelist']['search_engines'])) {
            $search_engines = array('google.', 'bing.', 'yahoo.', 'baidu.', 'yandex.', 'duckduckgo.');
            foreach ($search_engines as $engine) {
                if (strpos($domain, $engine) !== false) {
                    return true;
                }
            }
        }

        return false;
    }

    private function get_root_domain($domain) {
        $domain_parts = explode('.', $domain);
        if (count($domain_parts) > 2) {
            $tld_part = array_slice($domain_parts, -2, 2);
            if (in_array($tld_part[0], array('co', 'com', 'net', 'org', 'gov', 'edu'))) {
                return implode('.', array_slice($domain_parts, -3, 3));
            }
            return implode('.', array_slice($domain_parts, -2, 2));
        }
        return $domain;
    }

    private function load_redirect_template($url, $ref) {
        wp_enqueue_style(
            'wpnav-redirect-style',
            WPNAV_LINKS_PLUGIN_URL . 'assets/css/frontend.css',
            array(),
            WPNAV_LINKS_VERSION
        );

        $template_locations = array(
            get_stylesheet_directory() . '/wpnav-redirect-template.php',
            get_template_directory() . '/wpnav-redirect-template.php',
            WPNAV_LINKS_PLUGIN_DIR . 'templates/redirect-template.php'
        );

        foreach ($template_locations as $template) {
            if (file_exists($template)) {
                include $template;
                return;
            }
        }

        include WPNAV_LINKS_PLUGIN_DIR . 'templates/redirect-template.php';
    }

    public function get_redirect_url($url) {
        $url_format = isset($this->options['url_format']) ? $this->options['url_format'] : 'query';

        if ($url_format === 'path') {
            $encoded_url = base64_encode($url);
            if (get_option('permalink_structure')) {
                return home_url('/go/' . $encoded_url);
            } else {
                return home_url('?wpnav_redirect=1&url_param=' . urlencode($encoded_url));
            }
        } elseif ($url_format === 'target') {
            return home_url('?wpnav_redirect=1&target=' . urlencode($url));
        } else {
            return home_url('?wpnav_redirect=1&url=' . urlencode(base64_encode($url)));
        }
    }

    public function add_admin_bar_menu($wp_admin_bar) {
        if (!current_user_can('manage_options')) {
            return;
        }

        $wp_admin_bar->add_node(array(
            'id'    => 'wpnav-links',
            'title' => esc_html__('External Links', 'wpnav-links'),
            'href'  => admin_url('tools.php?page=wpnav-links'),
        ));
    }

    public function create_tables() {
        global $wpdb;

        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $this->table_name (
            id BIGINT UNSIGNED AUTO_INCREMENT,
            target_url VARCHAR(512) NOT NULL,
            source_page VARCHAR(255) NOT NULL,
            click_time DATETIME DEFAULT CURRENT_TIMESTAMP,
            user_ip VARCHAR(45),
            user_agent TEXT,
            is_https TINYINT(1) DEFAULT 0,
            PRIMARY KEY (id),
            INDEX (target_url(191)),
            INDEX (click_time)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        update_option('wpnav_links_db_version', WPNAV_LINKS_DB_VERSION);
    }

    public function record_redirect($target_url, $source_page) {
        global $wpdb;

        $is_https = strpos($target_url, 'https://') === 0 ? 1 : 0;
        $ip = $this->get_client_ip();
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '';

        $data = array(
            'target_url' => esc_url_raw($target_url),
            'source_page' => esc_url_raw($source_page),
            'click_time' => current_time('mysql'),
            'user_ip' => sanitize_text_field($ip),
            'user_agent' => sanitize_text_field($user_agent),
            'is_https' => $is_https
        );

        $result = $wpdb->insert($this->table_name, $data);
        return $result ? $wpdb->insert_id : false;
    }

    private function get_client_ip() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'];
        }
        return $ip;
    }

    public function get_stats($args = array()) {
        global $wpdb;

        $defaults = array(
            'start_date' => date('Y-m-d', strtotime('-30 days')),
            'end_date' => date('Y-m-d'),
            'limit' => 10,
            'offset' => 0,
            'order_by' => 'click_time',
            'order' => 'DESC'
        );

        $args = wp_parse_args($args, $defaults);

        $start_date = sanitize_text_field($args['start_date']);
        $end_date = sanitize_text_field($args['end_date']);
        $limit = intval($args['limit']);
        $offset = intval($args['offset']);
        $order_by = sanitize_sql_orderby($args['order_by']) ?: 'click_time';
        $order = strtoupper($args['order']) == 'ASC' ? 'ASC' : 'DESC';

        $query = $wpdb->prepare(
            "SELECT * FROM {$this->table_name}
            WHERE click_time BETWEEN %s AND %s
            ORDER BY {$order_by} {$order}
            LIMIT %d OFFSET %d",
            "{$start_date} 00:00:00",
            "{$end_date} 23:59:59",
            $limit,
            $offset
        );

        return $wpdb->get_results($query);
    }

    public function get_total_count($start_date = '', $end_date = '') {
        global $wpdb;

        if (empty($start_date) || empty($end_date)) {
            return $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");
        }

        $query = $wpdb->prepare(
            "SELECT COUNT(*) FROM {$this->table_name}
            WHERE click_time BETWEEN %s AND %s",
            "{$start_date} 00:00:00",
            "{$end_date} 23:59:59"
        );

        return $wpdb->get_var($query);
    }

    public function get_top_urls($limit = 10) {
        global $wpdb;

        $query = $wpdb->prepare(
            "SELECT target_url, COUNT(*) as count
            FROM {$this->table_name}
            GROUP BY target_url
            ORDER BY count DESC
            LIMIT %d",
            $limit
        );

        return $wpdb->get_results($query);
    }

    public function get_https_stats() {
        global $wpdb;

        $total = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name}");

        if ($total == 0) {
            return false;
        }

        $https_count = $wpdb->get_var("SELECT COUNT(*) FROM {$this->table_name} WHERE is_https = 1");
        $http_count = $total - $https_count;

        return array(
            'total' => $total,
            'https_count' => $https_count,
            'http_count' => $http_count,
            'https_percentage' => ($https_count / $total) * 100,
            'http_percentage' => ($http_count / $total) * 100
        );
    }

    public function get_unique_domains_count() {
        global $wpdb;

        $query = "SELECT COUNT(DISTINCT SUBSTRING_INDEX(SUBSTRING_INDEX(target_url, '/', 3), '://', -1)) as unique_domains FROM {$this->table_name}";

        $result = $wpdb->get_var($query);

        return $result ? intval($result) : 0;
    }

    public function cleanup_old_data($days = 90) {
        global $wpdb;

        $days = max(1, intval($days));

        $query = $wpdb->prepare(
            "DELETE FROM {$this->table_name}
            WHERE click_time < DATE_SUB(NOW(), INTERVAL %d DAY)",
            $days
        );

        return $wpdb->query($query);
    }

    public function validate_url($url) {
        if (empty($url) || !wp_http_validate_url($url)) {
            return false;
        }

        $parsed_url = parse_url($url);
        if (empty($parsed_url['host'])) {
            return false;
        }

        if ($this->contains_script_injection($url)) {
            return false;
        }

        return true;
    }

    private function contains_script_injection($url) {
        $patterns = array(
            '/javascript:/i',
            '/data:/i',
            '/vbscript:/i',
            '/<script/i',
            '/onclick/i',
            '/onerror/i',
            '/onload/i',
            '/eval\(/i'
        );

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    public function sanitize_url($url) {
        $url = preg_replace('/[\x00-\x1F\x7F]/', '', $url);
        $url = esc_url_raw($url);
        return $url;
    }
}
