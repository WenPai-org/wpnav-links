<?php

if (!defined('ABSPATH')) {
    exit;
}

$current_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'basic_settings';
$plugin = new WPNAV_Links();
$success_message = '';
$error_message = '';

// Check if WPCY.COM plugin is active
function wpnav_is_wp_china_yes_active() {
    if (!function_exists('is_plugin_active')) {
        include_once(ABSPATH . 'wp-admin/includes/plugin.php');
    }

    // Check for common WPCY.COM plugin paths
    $possible_plugins = array(
        'wp-china-yes/wp-china-yes.php',
        'wp-china-yes/index.php',
        'wp-china-yes/main.php'
    );

    foreach ($possible_plugins as $plugin) {
        if (is_plugin_active($plugin)) {
            return true;
        }
    }

    // Also check if the WP_CHINA_YES constant is defined
    return defined('WP_CHINA_YES');
}

$wp_china_yes_active = wpnav_is_wp_china_yes_active();

if (isset($_POST['wpnav_basic_nonce']) && wp_verify_nonce($_POST['wpnav_basic_nonce'], 'wpnav_basic_settings')) {
    if (!current_user_can('manage_options')) {
        $error_message = __('You do not have sufficient permissions to access this page.', 'wpnav-links');
    } else {
        $old_options = get_option('wpnav_links_options', array());
        $options = $old_options;

        $options['enabled'] = isset($_POST['enabled']) ? 1 : 0;
        $options['auto_redirect_enabled'] = isset($_POST['auto_redirect_enabled']) ? 1 : 0;
        $options['redirect_delay'] = isset($_POST['redirect_delay']) ? max(1, min(30, intval($_POST['redirect_delay']))) : 5;
        $options['open_in_new_tab'] = isset($_POST['open_in_new_tab']) ? 1 : 0;
        $options['url_format'] = isset($_POST['url_format']) ? sanitize_text_field($_POST['url_format']) : 'query';
        $options['intercept_content'] = isset($_POST['intercept_content']) ? 1 : 0;
        $options['intercept_comments'] = isset($_POST['intercept_comments']) ? 1 : 0;
        $options['intercept_widgets'] = isset($_POST['intercept_widgets']) ? 1 : 0;
        $options['exclude_css_class'] = isset($_POST['exclude_css_class']) ? sanitize_text_field($_POST['exclude_css_class']) : 'no-redirect';
        $options['admin_exempt'] = isset($_POST['admin_exempt']) ? 1 : 0;
        $options['cookie_duration'] = isset($_POST['cookie_duration']) ? max(1, min(365, intval($_POST['cookie_duration']))) : 30;
        $options['stats_retention'] = isset($_POST['stats_retention']) ? max(1, min(365, intval($_POST['stats_retention']))) : 90;

        $old_options_json = json_encode($old_options);
        $new_options_json = json_encode($options);
        $data_changed = ($old_options_json !== $new_options_json);

        if ($data_changed) {
            $result = update_option('wpnav_links_options', $options);
            if ($result !== false) {
                $success_message = __('Basic settings saved successfully!', 'wpnav-links');
            } else {
                $error_message = __('Failed to save basic settings. Database error.', 'wpnav-links');
            }
        } else {
            $success_message = __('Basic settings saved successfully!', 'wpnav-links');
        }
        $current_tab = 'basic_settings';
    }
}

if (isset($_POST['wpnav_whitelist_nonce']) && wp_verify_nonce($_POST['wpnav_whitelist_nonce'], 'wpnav_whitelist_nonce')) {
    if (!current_user_can('manage_options')) {
        $error_message = __('You do not have sufficient permissions to access this page.', 'wpnav-links');
    } else {
        $old_options = get_option('wpnav_links_options', array());
        $options = $old_options;

        $options['whitelist_domains'] = isset($_POST['whitelist_domains']) ? sanitize_textarea_field($_POST['whitelist_domains']) : '';
        $options['auto_whitelist'] = array(
            'same_root' => isset($_POST['auto_whitelist_same_root']) ? 1 : 0,
            'search_engines' => isset($_POST['auto_whitelist_search_engines']) ? 1 : 0
        );

        $old_options_json = json_encode($old_options);
        $new_options_json = json_encode($options);
        $data_changed = ($old_options_json !== $new_options_json);

        if ($data_changed) {
            $result = update_option('wpnav_links_options', $options);
            if ($result !== false) {
                $success_message = __('Whitelist settings saved successfully!', 'wpnav-links');
            } else {
                $error_message = __('Failed to save whitelist settings. Database error.', 'wpnav-links');
            }
        } else {
            $success_message = __('Whitelist settings saved successfully!', 'wpnav-links');
        }
        $current_tab = 'whitelist';
    }
}

if (isset($_POST['wpnav_redirect_nonce']) && wp_verify_nonce($_POST['wpnav_redirect_nonce'], 'wpnav_redirect_page_nonce')) {
    if (!current_user_can('manage_options')) {
        $error_message = __('You do not have sufficient permissions to access this page.', 'wpnav-links');
    } else {
        $old_options = get_option('wpnav_links_options', array());
        $options = $old_options;

        $options['template'] = isset($_POST['template']) ? sanitize_text_field($_POST['template']) : 'default';
        $options['color_scheme'] = isset($_POST['color_scheme']) ? sanitize_text_field($_POST['color_scheme']) : 'blue';
        $options['page_title'] = isset($_POST['page_title']) ? sanitize_text_field($_POST['page_title']) : 'External Link Warning';
        $options['page_subtitle'] = isset($_POST['page_subtitle']) ? sanitize_text_field($_POST['page_subtitle']) : '';
        $options['url_label'] = isset($_POST['url_label']) ? sanitize_text_field($_POST['url_label']) : 'You are about to visit:';
        $options['warning_text'] = isset($_POST['warning_text']) ? sanitize_textarea_field($_POST['warning_text']) : '';
        $options['show_warning_message'] = isset($_POST['show_warning_message']) ? 1 : 0;
        $options['show_logo'] = isset($_POST['show_logo']) ? 1 : 0;
        $options['show_url_full'] = isset($_POST['show_url_full']) ? 1 : 0;
        $options['show_security_info'] = isset($_POST['show_security_info']) ? 1 : 0;
        $options['show_security_tips'] = isset($_POST['show_security_tips']) ? 1 : 0;
        $options['show_back_button'] = isset($_POST['show_back_button']) ? 1 : 0;
        $options['custom_css'] = isset($_POST['custom_css']) ? wp_strip_all_tags($_POST['custom_css']) : '';
        $options['button_text_continue'] = isset($_POST['button_text_continue']) ? sanitize_text_field($_POST['button_text_continue']) : 'Continue';
        $options['button_text_back'] = isset($_POST['button_text_back']) ? sanitize_text_field($_POST['button_text_back']) : 'Back';
        $options['button_style'] = isset($_POST['button_style']) ? sanitize_text_field($_POST['button_style']) : 'rounded';
        $options['countdown_text'] = isset($_POST['countdown_text']) ? sanitize_text_field($_POST['countdown_text']) : 'Auto redirect in {seconds} seconds';
        $options['show_progress_bar'] = isset($_POST['show_progress_bar']) ? 1 : 0;

        $old_options_json = json_encode($old_options);
        $new_options_json = json_encode($options);
        $data_changed = ($old_options_json !== $new_options_json);

        if ($data_changed) {
            $result = update_option('wpnav_links_options', $options);
            if ($result !== false) {
                $success_message = __('Redirect page settings saved successfully!', 'wpnav-links');
            } else {
                $error_message = __('Failed to save redirect page settings. Database error.', 'wpnav-links');
            }
        } else {
            $success_message = __('Redirect page settings saved successfully!', 'wpnav-links');
        }
        $current_tab = 'redirect_page';
    }
}

if (isset($_POST['wpnav_import_nonce']) && wp_verify_nonce($_POST['wpnav_import_nonce'], 'wpnav_import_csv')) {
    if (!current_user_can('manage_options')) {
        $error_message = __('You do not have sufficient permissions to access this page.', 'wpnav-links');
    } elseif (!$wp_china_yes_active) {
        $error_message = __('Import functionality requires 文派叶子 🍃（WPCY.COM）to be active.', 'wpnav-links');
    } else {
        if (!empty($_FILES['whitelist_csv']['tmp_name'])) {
            $csv_file = $_FILES['whitelist_csv']['tmp_name'];
            $domains = array();

            if (($handle = fopen($csv_file, "r")) !== FALSE) {
                while (($data = fgetcsv($handle, 1000, ",")) !== FALSE) {
                    if (!empty($data[0])) {
                        $domains[] = sanitize_text_field($data[0]);
                    }
                }
                fclose($handle);
            }

            if (!empty($domains)) {
                $options = get_option('wpnav_links_options', array());
                $existing_domains = explode("\n", isset($options['whitelist_domains']) ? $options['whitelist_domains'] : '');
                $existing_domains = array_map('trim', $existing_domains);
                $combined_domains = array_merge($existing_domains, $domains);
                $combined_domains = array_filter($combined_domains);
                $combined_domains = array_unique($combined_domains);

                $options['whitelist_domains'] = implode("\n", $combined_domains);

                $result = update_option('wpnav_links_options', $options);
                if ($result !== false) {
                    $imported_count = count($domains);
                    $success_message = sprintf(__('Successfully imported %d domains.', 'wpnav-links'), $imported_count);
                } else {
                    $error_message = __('Failed to import CSV data. Database error.', 'wpnav-links');
                }
            } else {
                $error_message = __('No valid domains found in the CSV file.', 'wpnav-links');
            }
        } else {
            $error_message = __('Please select a CSV file to upload.', 'wpnav-links');
        }
        $current_tab = 'import_export';
    }
}

if (isset($_GET['message'])) {
    $message_type = sanitize_text_field($_GET['message']);
    if ($message_type === 'saved') {
        $success_message = __('Settings saved successfully!', 'wpnav-links');
    } elseif ($message_type === 'imported') {
        $count = isset($_GET['count']) ? intval($_GET['count']) : 0;
        $success_message = sprintf(__('Successfully imported %d domains.', 'wpnav-links'), $count);
    }
}

$options = get_option('wpnav_links_options', array());

$current_time = current_time('timestamp');
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d', $current_time);
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime('-30 days', $current_time));

$limit = 20;
$current_page = isset($_GET['paged']) ? max(1, intval($_GET['paged'])) : 1;
$offset = ($current_page - 1) * $limit;

$orderby = isset($_GET['orderby']) ? sanitize_text_field($_GET['orderby']) : 'click_time';
$order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

$stats = $plugin->get_stats(array(
    'start_date' => $start_date,
    'end_date' => $end_date,
    'limit' => $limit,
    'offset' => $offset,
    'order_by' => $orderby,
    'order' => $order
));

$total_items = $plugin->get_total_count($start_date, $end_date);
$total_pages = ceil($total_items / $limit);
$top_urls = $plugin->get_top_urls(10);

$custom_template_exists = file_exists(get_stylesheet_directory() . '/wpnav-redirect-template.php') ||
                         file_exists(get_template_directory() . '/wpnav-redirect-template.php');
?>
<div class="wrap">
    <h1><?php echo esc_html( get_admin_page_title() ); ?>
        <span style="font-size: 13px; padding-left: 10px;">
            <?php printf( esc_html__( 'Version: %s', 'wpnav-links' ), esc_html( WPNAV_LINKS_VERSION ) ); ?>
        </span>
        <a href="https://wpnav.com/document/wpnav-links" target="_blank" class="button button-secondary" style="margin-left: 10px;">
            <?php esc_html_e( 'Document', 'wpnav-links' ); ?>
        </a>
        <a href="https://sharecms.com/forums/" target="_blank" class="button button-secondary">
            <?php esc_html_e( 'Support', 'wpnav-links' ); ?>
        </a>
    </h1>

    <?php if (!empty($success_message)): ?>
    <div class="notice notice-success is-dismissible">
        <p><strong><?php echo esc_html($success_message); ?></strong></p>
    </div>
    <?php endif; ?>

    <?php if (!empty($error_message)): ?>
    <div class="notice notice-error is-dismissible">
        <p><strong><?php echo esc_html($error_message); ?></strong></p>
    </div>
    <?php endif; ?>

    <?php if ($custom_template_exists && $current_tab == 'redirect_page'): ?>
    <div class="notice notice-info is-dismissible">
        <p><strong><?php esc_html_e('Custom Template Detected:', 'wpnav-links'); ?></strong> <?php esc_html_e('Your theme has a custom redirect template. Settings below may not apply if your custom template doesn\'t support them.', 'wpnav-links'); ?></p>
    </div>
    <?php endif; ?>
<div class="card">
    <div class="wpnav-tabs">
        <button type="button" class="wpnav-tab <?php echo $current_tab == 'basic_settings' ? 'active' : ''; ?>" data-tab="basic_settings">
            <?php esc_html_e('Basic Settings', 'wpnav-links'); ?>
        </button>
        <button type="button" class="wpnav-tab <?php echo $current_tab == 'redirect_page' ? 'active' : ''; ?>" data-tab="redirect_page">
            <?php esc_html_e('Redirect Page', 'wpnav-links'); ?>
        </button>
        <button type="button" class="wpnav-tab <?php echo $current_tab == 'whitelist' ? 'active' : ''; ?>" data-tab="whitelist">
            <?php esc_html_e('Whitelist', 'wpnav-links'); ?>
        </button>
        <button type="button" class="wpnav-tab <?php echo $current_tab == 'logs_statistics' ? 'active' : ''; ?>" data-tab="logs_statistics">
            <?php esc_html_e('Statistics', 'wpnav-links'); ?>
        </button>
        <button type="button" class="wpnav-tab <?php echo $current_tab == 'import_export' ? 'active' : ''; ?>" data-tab="import_export">
            <?php esc_html_e('Import/Export', 'wpnav-links'); ?>
        </button>
    </div>
    <div class="wpnav-tab-content">
        <div class="wpnav-tab-section" data-section="basic_settings" style="<?php echo $current_tab != 'basic_settings' ? 'display: none;' : ''; ?>">
                <h2><?php esc_html_e('Basic Settings', 'wpnav-links'); ?></h2>
                <form method="post" action="">
                    <?php wp_nonce_field('wpnav_basic_settings', 'wpnav_basic_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Enable Redirect', 'wpnav-links'); ?></th>
                            <td>
                                <input type="checkbox" id="enabled" name="enabled" value="1" <?php checked(!empty($options['enabled'])); ?> />
                                <label for="enabled"><?php esc_html_e('Enable external link redirect functionality', 'wpnav-links'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Auto Redirect', 'wpnav-links'); ?></th>
                            <td>
                                <input type="checkbox" id="auto_redirect_enabled" name="auto_redirect_enabled" value="1" <?php checked(!empty($options['auto_redirect_enabled'])); ?> />
                                <label for="auto_redirect_enabled"><?php esc_html_e('Enable automatic redirect', 'wpnav-links'); ?></label>
                                <p class="description"><?php esc_html_e('When enabled, users will be automatically redirected after the specified delay.', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                        <tr id="redirect_delay_row" style="<?php echo empty($options['auto_redirect_enabled']) ? 'display: none;' : ''; ?>">
                            <th scope="row"><?php esc_html_e('Redirect Delay', 'wpnav-links'); ?></th>
                            <td>
                                <input type="number" id="redirect_delay" name="redirect_delay" min="1" max="30" value="<?php echo esc_attr(isset($options['redirect_delay']) ? $options['redirect_delay'] : 5); ?>" />
                                <p class="description"><?php esc_html_e('Seconds to wait before automatic redirect (1-30 seconds).', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Open in New Tab', 'wpnav-links'); ?></th>
                            <td>
                                <input type="checkbox" id="open_in_new_tab" name="open_in_new_tab" value="1" <?php checked(!empty($options['open_in_new_tab'])); ?> />
                                <label for="open_in_new_tab"><?php esc_html_e('Open external links in new tab/window', 'wpnav-links'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('URL Format', 'wpnav-links'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="url_format" value="query" <?php checked(isset($options['url_format']) ? $options['url_format'] : 'query', 'query'); ?> />
                                        <?php esc_html_e('Query Format (?url=encoded_url)', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="url_format" value="path" <?php checked(isset($options['url_format']) ? $options['url_format'] : 'query', 'path'); ?> />
                                        <?php esc_html_e('Path Format (/go/encoded_url)', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="url_format" value="target" <?php checked(isset($options['url_format']) ? $options['url_format'] : 'query', 'target'); ?> />
                                        <?php esc_html_e('Target Format (?target=encoded_url)', 'wpnav-links'); ?>
                                    </label>
                                </fieldset>
                                <p class="description"><?php esc_html_e('Choose URL format style for external link redirects.', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e('Content Processing', 'wpnav-links'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Process Post Content', 'wpnav-links'); ?></th>
                            <td>
                                <input type="checkbox" id="intercept_content" name="intercept_content" value="1" <?php checked(!empty($options['intercept_content'])); ?> />
                                <label for="intercept_content"><?php esc_html_e('Process external links in post and page content', 'wpnav-links'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Process Comments', 'wpnav-links'); ?></th>
                            <td>
                                <input type="checkbox" id="intercept_comments" name="intercept_comments" value="1" <?php checked(!empty($options['intercept_comments'])); ?> />
                                <label for="intercept_comments"><?php esc_html_e('Process external links in comments', 'wpnav-links'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Process Widgets', 'wpnav-links'); ?></th>
                            <td>
                                <input type="checkbox" id="intercept_widgets" name="intercept_widgets" value="1" <?php checked(!empty($options['intercept_widgets'])); ?> />
                                <label for="intercept_widgets"><?php esc_html_e('Process external links in widgets', 'wpnav-links'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Exclude CSS Class', 'wpnav-links'); ?></th>
                            <td>
                                <input type="text" id="exclude_css_class" name="exclude_css_class" value="<?php echo esc_attr(isset($options['exclude_css_class']) ? $options['exclude_css_class'] : 'no-redirect'); ?>" class="regular-text" />
                                <p class="description"><?php esc_html_e('Links with this CSS class will not be redirected.', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <h3><?php esc_html_e('User Experience', 'wpnav-links'); ?></h3>
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Admin Exempt', 'wpnav-links'); ?></th>
                            <td>
                                <input type="checkbox" id="admin_exempt" name="admin_exempt" value="1" <?php checked(!empty($options['admin_exempt'])); ?> />
                                <label for="admin_exempt"><?php esc_html_e('Skip redirect page for admin users', 'wpnav-links'); ?></label>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Cookie Duration', 'wpnav-links'); ?></th>
                            <td>
                                <input type="number" id="cookie_duration" name="cookie_duration" min="1" max="365" value="<?php echo esc_attr(isset($options['cookie_duration']) ? $options['cookie_duration'] : 30); ?>" />
                                <p class="description"><?php esc_html_e('Days to remember user\'s "Do not show again" preference.', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Stats Retention', 'wpnav-links'); ?></th>
                            <td>
                                <input type="number" id="stats_retention" name="stats_retention" min="1" max="365" value="<?php echo esc_attr(isset($options['stats_retention']) ? $options['stats_retention'] : 90); ?>" />
                                <p class="description"><?php esc_html_e('Days to retain statistics data before automatic cleanup.', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="wpnav_save_basic_settings" class="button button-primary" value="<?php esc_attr_e('Save Settings', 'wpnav-links'); ?>" />
                    </p>
                </form>
        </div>

        <div class="wpnav-tab-section" data-section="whitelist" style="<?php echo $current_tab != 'whitelist' ? 'display: none;' : ''; ?>">
                <h2><?php esc_html_e('Domain Whitelist Management', 'wpnav-links'); ?></h2>
                <span id="wpnav-whitelist-status" class="wpnav-notice" style="display:none;"></span>
                <p><?php esc_html_e('Manage domains that should not be redirected through the external link page.', 'wpnav-links'); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field('wpnav_whitelist_nonce', 'wpnav_whitelist_nonce'); ?>

                    <div class="wpnav-section-header">
                        <h3><?php esc_html_e('Domain List', 'wpnav-links'); ?></h3>
                        <div class="wpnav-section-actions">
                            <button type="button" class="button button-small" id="wpnav-select-all-domains"><?php esc_html_e('Add Common', 'wpnav-links'); ?></button>
                            <button type="button" class="button button-small" id="wpnav-clear-domains"><?php esc_html_e('Clear All', 'wpnav-links'); ?></button>
                        </div>
                    </div>

                    <p class="description"><?php esc_html_e('Enter domains that should not redirect, one per line. Example: google.com (do not include http:// or https://)', 'wpnav-links'); ?></p>

                    <textarea name="whitelist_domains" rows="12" class="large-text code" placeholder="google.com&#10;facebook.com&#10;youtube.com"><?php echo esc_textarea(isset($options['whitelist_domains']) ? $options['whitelist_domains'] : ''); ?></textarea>

                    <p><?php esc_html_e('Click to add common domains to your whitelist:', 'wpnav-links'); ?></p>
                    <div class="wpnav-quick-domains">
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="google.com"><?php esc_html_e('Google', 'wpnav-links'); ?></button>
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="baidu.com"><?php esc_html_e('Baidu', 'wpnav-links'); ?></button>
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="bing.com"><?php esc_html_e('Bing', 'wpnav-links'); ?></button>
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="youtube.com"><?php esc_html_e('YouTube', 'wpnav-links'); ?></button>
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="facebook.com"><?php esc_html_e('Facebook', 'wpnav-links'); ?></button>
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="twitter.com"><?php esc_html_e('Twitter', 'wpnav-links'); ?></button>
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="linkedin.com"><?php esc_html_e('LinkedIn', 'wpnav-links'); ?></button>
                        <button type="button" class="button button-small wpnav-add-domain" data-domain="instagram.com"><?php esc_html_e('Instagram', 'wpnav-links'); ?></button>
                    </div>

                    <div class="wpnav-auto-rules">
                        <h4><?php esc_html_e('Auto Whitelist Rules', 'wpnav-links'); ?></h4>
                        <table class="wp-list-table widefat fixed">
                            <tbody>
                                <tr>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="auto_whitelist_same_root" value="1" <?php checked(!empty($options['auto_whitelist']['same_root'])); ?> />
                                            <?php esc_html_e('Same root domain no redirect', 'wpnav-links'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Subdomains of your site won\'t redirect (e.g., blog.example.com and shop.example.com)', 'wpnav-links'); ?></p>
                                    </td>
                                </tr>
                                <tr>
                                    <td>
                                        <label>
                                            <input type="checkbox" name="auto_whitelist_search_engines" value="1" <?php checked(!empty($options['auto_whitelist']['search_engines'])); ?> />
                                            <?php esc_html_e('Search engines no redirect', 'wpnav-links'); ?>
                                        </label>
                                        <p class="description"><?php esc_html_e('Major search engines (Google, Baidu, Bing, etc.) won\'t redirect', 'wpnav-links'); ?></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <p class="submit">
                        <input type="submit" name="wpnav_update_whitelist" class="button button-primary" value="<?php esc_attr_e('Save Whitelist', 'wpnav-links'); ?>" />
                    </p>
                </form>
        </div>

        <div class="wpnav-tab-section" data-section="redirect_page" style="<?php echo $current_tab != 'redirect_page' ? 'display: none;' : ''; ?>">
                <h2><?php esc_html_e('Redirect Page Design', 'wpnav-links'); ?></h2>
                <p><?php esc_html_e('Customize the appearance and content of the redirect warning page shown to users.', 'wpnav-links'); ?></p>

                <form method="post" action="">
                    <?php wp_nonce_field('wpnav_redirect_page_nonce', 'wpnav_redirect_nonce'); ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Template Style', 'wpnav-links'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="radio" name="template" value="simple" <?php checked(isset($options['template']) ? $options['template'] : 'default', 'simple'); ?> />
                                        <strong><?php esc_html_e('Simple', 'wpnav-links'); ?></strong> - <?php esc_html_e('Ultra-minimal design with just essentials', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="template" value="minimal" <?php checked(isset($options['template']) ? $options['template'] : 'default', 'minimal'); ?> />
                                        <strong><?php esc_html_e('Minimal', 'wpnav-links'); ?></strong> - <?php esc_html_e('Clean and lightweight design', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="template" value="default" <?php checked(isset($options['template']) ? $options['template'] : 'default', 'default'); ?> />
                                        <strong><?php esc_html_e('Default', 'wpnav-links'); ?></strong> - <?php esc_html_e('Balanced information display', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="radio" name="template" value="full" <?php checked(isset($options['template']) ? $options['template'] : 'default', 'full'); ?> />
                                        <strong><?php esc_html_e('Full', 'wpnav-links'); ?></strong> - <?php esc_html_e('Comprehensive with security info', 'wpnav-links'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Color Scheme', 'wpnav-links'); ?></th>
                            <td>
                                <select name="color_scheme">
                                    <option value="blue" <?php selected(isset($options['color_scheme']) ? $options['color_scheme'] : 'blue', 'blue'); ?>><?php esc_html_e('Blue - Professional', 'wpnav-links'); ?></option>
                                    <option value="green" <?php selected(isset($options['color_scheme']) ? $options['color_scheme'] : 'blue', 'green'); ?>><?php esc_html_e('Green - Safe', 'wpnav-links'); ?></option>
                                    <option value="red" <?php selected(isset($options['color_scheme']) ? $options['color_scheme'] : 'blue', 'red'); ?>><?php esc_html_e('Red - Warning', 'wpnav-links'); ?></option>
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Page Content', 'wpnav-links'); ?></th>
                            <td>
                                <p>
                                    <label for="page_title"><?php esc_html_e('Page Title:', 'wpnav-links'); ?></label>
                                    <input type="text" id="page_title" name="page_title" value="<?php echo esc_attr(isset($options['page_title']) ? $options['page_title'] : 'External Link Warning'); ?>" class="regular-text" />
                                </p>
                                <p>
                                    <label for="page_subtitle"><?php esc_html_e('Page Subtitle (optional):', 'wpnav-links'); ?></label>
                                    <input type="text" id="page_subtitle" name="page_subtitle" value="<?php echo esc_attr(isset($options['page_subtitle']) ? $options['page_subtitle'] : ''); ?>" class="regular-text" />
                                </p>
                                <p>
                                    <label for="url_label"><?php esc_html_e('URL Label:', 'wpnav-links'); ?></label>
                                    <input type="text" id="url_label" name="url_label" value="<?php echo esc_attr(isset($options['url_label']) ? $options['url_label'] : 'You are about to visit:'); ?>" class="regular-text" />
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Display Options', 'wpnav-links'); ?></th>
                            <td>
                                <fieldset>
                                    <label>
                                        <input type="checkbox" name="show_warning_message" value="1" <?php checked(isset($options['show_warning_message']) ? $options['show_warning_message'] : 1); ?> />
                                        <?php esc_html_e('Show warning message', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="show_logo" value="1" <?php checked(!empty($options['show_logo'])); ?> />
                                        <?php esc_html_e('Show site logo', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="show_url_full" value="1" <?php checked(!empty($options['show_url_full'])); ?> />
                                        <?php esc_html_e('Show full URL below domain', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="show_security_info" value="1" <?php checked(isset($options['show_security_info']) ? $options['show_security_info'] : 1); ?> />
                                        <?php esc_html_e('Show HTTPS security status', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="show_security_tips" value="1" <?php checked(!empty($options['show_security_tips'])); ?> />
                                        <?php esc_html_e('Show security tips and warnings', 'wpnav-links'); ?>
                                    </label><br>
                                    <label>
                                        <input type="checkbox" name="show_back_button" value="1" <?php checked(isset($options['show_back_button']) ? $options['show_back_button'] : 1); ?> />
                                        <?php esc_html_e('Show back button', 'wpnav-links'); ?>
                                    </label>
                                </fieldset>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="warning_text"><?php esc_html_e('Warning Message', 'wpnav-links'); ?></label></th>
                            <td>
                                <textarea id="warning_text" name="warning_text" rows="3" class="large-text" placeholder="<?php esc_attr_e('You are about to leave this site and visit an external website. We are not responsible for the content of external websites.', 'wpnav-links'); ?>"><?php echo esc_textarea(isset($options['warning_text']) ? $options['warning_text'] : ''); ?></textarea>
                                <p class="description"><?php esc_html_e('Main warning message displayed to users. Leave empty to hide the warning box.', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Button Settings', 'wpnav-links'); ?></th>
                            <td>
                                <p>
                                    <label for="button_text_continue"><?php esc_html_e('Continue Button Text:', 'wpnav-links'); ?></label>
                                    <input type="text" id="button_text_continue" name="button_text_continue" value="<?php echo esc_attr(isset($options['button_text_continue']) ? $options['button_text_continue'] : 'Continue'); ?>" class="regular-text" />
                                </p>
                                <p>
                                    <label for="button_text_back"><?php esc_html_e('Back Button Text:', 'wpnav-links'); ?></label>
                                    <input type="text" id="button_text_back" name="button_text_back" value="<?php echo esc_attr(isset($options['button_text_back']) ? $options['button_text_back'] : 'Back'); ?>" class="regular-text" />
                                </p>
                                <p>
                                    <label for="button_style"><?php esc_html_e('Button Style:', 'wpnav-links'); ?></label>
                                    <select name="button_style">
                                        <option value="rounded" <?php selected(isset($options['button_style']) ? $options['button_style'] : 'rounded', 'rounded'); ?>><?php esc_html_e('Rounded', 'wpnav-links'); ?></option>
                                        <option value="square" <?php selected(isset($options['button_style']) ? $options['button_style'] : 'rounded', 'square'); ?>><?php esc_html_e('Square', 'wpnav-links'); ?></option>
                                        <option value="pill" <?php selected(isset($options['button_style']) ? $options['button_style'] : 'rounded', 'pill'); ?>><?php esc_html_e('Pill Shape', 'wpnav-links'); ?></option>
                                    </select>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><?php esc_html_e('Countdown Settings', 'wpnav-links'); ?></th>
                            <td>
                                <p>
                                    <label for="countdown_text"><?php esc_html_e('Countdown Text:', 'wpnav-links'); ?></label>
                                    <input type="text" id="countdown_text" name="countdown_text" value="<?php echo esc_attr(isset($options['countdown_text']) ? $options['countdown_text'] : 'Auto redirect in {seconds} seconds'); ?>" class="regular-text" />
                                    <br><small><?php esc_html_e('Use {seconds} as placeholder for countdown number', 'wpnav-links'); ?></small>
                                </p>
                                <p>
                                    <label>
                                        <input type="checkbox" name="show_progress_bar" value="1" <?php checked(!empty($options['show_progress_bar'])); ?> />
                                        <?php esc_html_e('Show progress bar during countdown', 'wpnav-links'); ?>
                                    </label>
                                </p>
                            </td>
                        </tr>
                        <tr>
                            <th scope="row"><label for="custom_css"><?php esc_html_e('Custom CSS', 'wpnav-links'); ?></label></th>
                            <td>
                                <textarea id="custom_css" name="custom_css" rows="8" class="large-text code"><?php echo esc_textarea(isset($options['custom_css']) ? $options['custom_css'] : ''); ?></textarea>
                                <p class="description"><?php esc_html_e('Add custom CSS to further customize the redirect page appearance', 'wpnav-links'); ?></p>
                            </td>
                        </tr>
                    </table>

                    <p class="submit">
                        <input type="submit" name="wpnav_update_redirect_page" class="button button-primary" value="<?php esc_attr_e('Save Redirect Page Settings', 'wpnav-links'); ?>" />
                    </p>
                </form>

                <h2><?php esc_html_e('Live Preview', 'wpnav-links'); ?></h2>
                <div class="wpnav-redirect-preview">
                    <div class="preview-container wpnav-color-<?php echo esc_attr(isset($options['color_scheme']) ? $options['color_scheme'] : 'blue'); ?>">
                        <div class="wpnav-container wpnav-<?php echo esc_attr(isset($options['template']) ? $options['template'] : 'default'); ?>">
                            <div class="wpnav-header">
                                <?php if (!empty($options['show_logo'])) : ?>
                                <div class="wpnav-site-logo">Site Logo</div>
                                <?php endif; ?>
                                <h1 class="wpnav-title" id="preview-page-title"><?php echo esc_html(isset($options['page_title']) ? $options['page_title'] : 'External Link Warning'); ?></h1>
                                <?php if (!empty($options['page_subtitle'])) : ?>
                                <p class="wpnav-subtitle" id="preview-page-subtitle" style="<?php echo empty($options['page_subtitle']) ? 'display: none;' : ''; ?>"><?php echo esc_html($options['page_subtitle']); ?></p>
                                <?php endif; ?>
                            </div>
                            <?php
                            $current_warning_text = isset($options['warning_text']) ? trim($options['warning_text']) : '';
                            $show_warning_setting = isset($options['show_warning_message']) ? $options['show_warning_message'] : 1;
                            if ($show_warning_setting && !empty($current_warning_text)) :
                            ?>
                            <div class="wpnav-warning" id="preview-warning">
                                <span id="preview-warning-text"><?php echo esc_html($current_warning_text); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="wpnav-url-container">
                                <div class="wpnav-url-label" id="preview-url-label"><?php echo esc_html(isset($options['url_label']) ? $options['url_label'] : 'You are about to visit:'); ?></div>
                                <div class="wpnav-url">
                                    <span class="wpnav-url-domain">example.com</span>
                                    <div class="wpnav-url-full" style="<?php echo empty($options['show_url_full']) ? 'display: none;' : ''; ?>">https://example.com/sample-page</div>
                                    <?php if (isset($options['show_security_info']) ? $options['show_security_info'] : 1) : ?>
                                    <div class="wpnav-security-status">
                                        <span class="wpnav-https-badge secure">HTTPS</span>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php if (!empty($options['show_security_tips'])) : ?>
                            <div class="wpnav-security-tips">
                                <h4><?php esc_html_e('Security Tips', 'wpnav-links'); ?></h4>
                                <ul>
                                    <li><?php esc_html_e('Verify the link is from a trusted source', 'wpnav-links'); ?></li>
                                    <li><?php esc_html_e('Check for secure HTTPS connection', 'wpnav-links'); ?></li>
                                </ul>
                            </div>
                            <?php endif; ?>
                            <div class="wpnav-buttons">
                                <span class="wpnav-btn wpnav-btn-primary wpnav-btn-<?php echo esc_attr(isset($options['button_style']) ? $options['button_style'] : 'rounded'); ?>" id="preview-continue-btn"><?php echo esc_html(isset($options['button_text_continue']) ? $options['button_text_continue'] : 'Continue'); ?></span>
                                <?php if (isset($options['show_back_button']) ? $options['show_back_button'] : 1) : ?>
                                <span class="wpnav-btn wpnav-btn-secondary wpnav-btn-<?php echo esc_attr(isset($options['button_style']) ? $options['button_style'] : 'rounded'); ?>" id="preview-back-btn" style="<?php echo empty($options['show_back_button']) ? 'display: none;' : ''; ?>"><?php echo esc_html(isset($options['button_text_back']) ? $options['button_text_back'] : 'Back'); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="wpnav-countdown" id="preview-countdown">
                                <span id="preview-countdown-text"><?php echo str_replace('{seconds}', '5', isset($options['countdown_text']) ? $options['countdown_text'] : 'Auto redirect in {seconds} seconds'); ?></span>
                                <?php if (!empty($options['show_progress_bar'])) : ?>
                                <div class="wpnav-progress-bar">
                                    <div class="wpnav-progress-fill"></div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                      </div>

        <div class="wpnav-tab-section" data-section="import_export" style="<?php echo $current_tab != 'import_export' ? 'display: none;' : ''; ?>">

                <?php if (!$wp_china_yes_active) : ?>
                    <div class="notice notice-info inline">
                        <p>
                            <?php
                            $wpcy_installed = class_exists('WP_China_Yes'); // Check if WP China Yes plugin is installed
                            $wpcy_link = $wpcy_installed
                                ? admin_url('admin.php?page=wp-china-yes')
                                : 'https://wpcy.com';

                            printf(
                                __('Install the %s high-performance component to use batch whitelist import/export functionality.', 'wpnav-links'),
                                sprintf(
                                    '<a href="%s" target="_blank" rel="noopener noreferrer">%s</a>',
                                    esc_url($wpcy_link),
                                    __('文派叶子 🍃（WPCY.COM）', 'wpnav-links')
                                )
                            );
                            ?>
                        </p>
                    </div>
                <?php endif; ?>

                <div class="wpnav-export-item" <?php echo !$wp_china_yes_active ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                    <h2><?php esc_html_e('Import Whitelist', 'wpnav-links'); ?></h2>
                    <p><?php esc_html_e('Upload CSV file to bulk import domains to your whitelist.', 'wpnav-links'); ?></p>

                    <form method="post" enctype="multipart/form-data" action="">
                        <?php wp_nonce_field('wpnav_import_csv', 'wpnav_import_nonce'); ?>

                        <table class="form-table">
                            <tr>
                                <th scope="row"><?php esc_html_e('CSV File', 'wpnav-links'); ?></th>
                                <td>
                                    <input type="file" name="whitelist_csv" accept=".csv" <?php echo !$wp_china_yes_active ? 'disabled' : ''; ?> />
                                    <p class="description"><?php esc_html_e('Select a CSV file containing domain names (one per line)', 'wpnav-links'); ?></p>
                                </td>
                            </tr>
                        </table>

                        <p class="submit">
                            <input type="submit" name="wpnav_import_csv" class="button button-primary" value="<?php esc_attr_e('Import CSV', 'wpnav-links'); ?>" <?php echo !$wp_china_yes_active ? 'disabled' : ''; ?> />
                        </p>
                    </form>
                </div>

                <div class="wpnav-export-item" <?php echo !$wp_china_yes_active ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                    <h2><?php esc_html_e('Export Whitelist', 'wpnav-links'); ?></h2>
                    <p><?php esc_html_e('Download current whitelist domains as CSV file.', 'wpnav-links'); ?></p>
                    <button type="button" class="button button-primary" id="wpnav-export-whitelist" <?php echo !$wp_china_yes_active ? 'disabled' : ''; ?>>
                        <?php esc_html_e('Export Whitelist', 'wpnav-links'); ?>
                    </button>
                </div>

                <div class="wpnav-export-item" <?php echo !$wp_china_yes_active ? 'style="opacity: 0.5; pointer-events: none;"' : ''; ?>>
                    <h2><?php esc_html_e('Export Statistics', 'wpnav-links'); ?></h2>
                    <p><?php esc_html_e('Download statistics data for the selected date range.', 'wpnav-links'); ?></p>
                    <form method="post" action="<?php echo esc_url(admin_url('admin-ajax.php')); ?>" style="display: inline;">
                        <input type="hidden" name="action" value="wpnav_export_stats" />
                        <?php wp_nonce_field('wpnav_admin_nonce', 'nonce'); ?>

                        <div style="margin-bottom: 10px;">
                            <label><?php esc_html_e('Start Date:', 'wpnav-links'); ?> <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" <?php echo !$wp_china_yes_active ? 'disabled' : ''; ?> /></label>
                            <label style="margin-left: 10px;"><?php esc_html_e('End Date:', 'wpnav-links'); ?> <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>" <?php echo !$wp_china_yes_active ? 'disabled' : ''; ?> /></label>
                        </div>

                        <button type="submit" class="button button-primary" <?php echo !$wp_china_yes_active ? 'disabled' : ''; ?>>
                            <?php esc_html_e('Export Statistics', 'wpnav-links'); ?>
                        </button>
                    </form>
                </div>

                <div class="card">
                <h2><?php esc_html_e('CSV Format Guide', 'wpnav-links'); ?></h2>
                <p><?php esc_html_e('The CSV file for whitelist import should follow this format:', 'wpnav-links'); ?></p>

                <table class="widefat" style="margin-top: 10px;">
                    <thead>
                        <tr>
                            <th><?php esc_html_e('Domain', 'wpnav-links'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>google.com</td>
                        </tr>
                        <tr>
                            <td>facebook.com</td>
                        </tr>
                        <tr>
                            <td>youtube.com</td>
                        </tr>
                    </tbody>
                </table>

                <ul style="margin-top: 15px;">
                    <li><strong><?php esc_html_e('Domain:', 'wpnav-links'); ?></strong> <?php esc_html_e('The domain name without http:// or https:// (required)', 'wpnav-links'); ?></li>
                    <li><?php esc_html_e('One domain per line in the CSV file', 'wpnav-links'); ?></li>
                    <li><?php esc_html_e('Empty lines will be ignored during import', 'wpnav-links'); ?></li>
                    <li><?php esc_html_e('Duplicate domains will be automatically removed', 'wpnav-links'); ?></li>
                </ul>
        </div>
        </div>

        <div class="wpnav-tab-section" data-section="logs_statistics" style="<?php echo $current_tab != 'logs_statistics' ? 'display: none;' : ''; ?>">
                <h2><?php esc_html_e('Access Statistics', 'wpnav-links'); ?></h2>
                <p><?php esc_html_e('View external link access statistics and logs.', 'wpnav-links'); ?></p>
                <div class="wpnav-info-grid">
                    <div class="wpnav-info-item">
                        <?php if (!empty($top_urls)) : ?>
                        <h3><?php esc_html_e('Top External Links', 'wpnav-links'); ?></h3>
                        <div class="wpnav-top-links">
                            <?php foreach ($top_urls as $index => $url) : ?>
                            <div class="wpnav-top-link-item">
                                <span class="wpnav-rank">#<?php echo ($index + 1); ?></span>
                                <div class="wpnav-link-info">
                                    <a href="<?php echo esc_url($url->target_url); ?>" target="_blank" title="<?php echo esc_attr($url->target_url); ?>" class="wpnav-link-title">
                                        <?php echo esc_html(substr($url->target_url, 0, 60) . (strlen($url->target_url) > 60 ? '...' : '')); ?>
                                    </a>
                                    <span class="wpnav-click-count"><?php echo sprintf(__('%s clicks', 'wpnav-links'), esc_html($url->count)); ?></span>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php else : ?>
                        <h3><?php esc_html_e('Top External Links', 'wpnav-links'); ?></h3>
                        <p><?php esc_html_e('No data available yet. Top links will appear here after users start clicking external links.', 'wpnav-links'); ?></p>
                        <?php endif; ?>
                    </div>

                    <div class="wpnav-info-item">
                        <h3><?php esc_html_e('Activity Overview', 'wpnav-links'); ?></h3>
                        <?php
                        $today_count = $plugin->get_total_count(date('Y-m-d'), date('Y-m-d'));
                        $month_count = $plugin->get_total_count(date('Y-m-01'), date('Y-m-d'));
                        $https_stats = $plugin->get_https_stats();
                        $unique_domains = $plugin->get_unique_domains_count();
                        $avg_daily = 0;
                        if ($total_items > 0) {
                            $days_active = max(1, (strtotime($end_date) - strtotime($start_date)) / 86400);
                            $avg_daily = round($total_items / $days_active, 1);
                        }
                        ?>
                        <div class="wpnav-activity-stats">
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo number_format($today_count); ?></span>
                                <span class="wpnav-activity-label"><?php esc_html_e('Today', 'wpnav-links'); ?></span>
                            </div>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo number_format($month_count); ?></span>
                                <span class="wpnav-activity-label"><?php esc_html_e('This Month', 'wpnav-links'); ?></span>
                            </div>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo number_format_i18n($total_items); ?></span>
                                <span class="wpnav-activity-label"><?php esc_html_e('Total Records', 'wpnav-links'); ?></span>
                            </div>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo $total_pages; ?></span>
                                <span class="wpnav-activity-label"><?php esc_html_e('Pages', 'wpnav-links'); ?></span>
                            </div>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo $avg_daily; ?></span>
                                <span class="wpnav-activity-label"><?php esc_html_e('Daily Avg', 'wpnav-links'); ?></span>
                            </div>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo number_format($unique_domains); ?></span>
                                <span class="wpnav-activity-label"><?php esc_html_e('Unique Domains', 'wpnav-links'); ?></span>
                            </div>
                            <?php if ($https_stats && $https_stats['total'] > 0): ?>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo round($https_stats['https_percentage']); ?>%</span>
                                <span class="wpnav-activity-label"><?php esc_html_e('HTTPS Links', 'wpnav-links'); ?></span>
                            </div>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number"><?php echo round($https_stats['http_percentage']); ?>%</span>
                                <span class="wpnav-activity-label"><?php esc_html_e('HTTP Links', 'wpnav-links'); ?></span>
                            </div>
                            <?php else: ?>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number">-</span>
                                <span class="wpnav-activity-label"><?php esc_html_e('HTTPS Links', 'wpnav-links'); ?></span>
                            </div>
                            <div class="wpnav-activity-item">
                                <span class="wpnav-activity-number">-</span>
                                <span class="wpnav-activity-label"><?php esc_html_e('HTTP Links', 'wpnav-links'); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <div class="wpnav-info-item">
                        <h3><?php esc_html_e('Usage Guide', 'wpnav-links'); ?></h3>
                        <ol>
                            <li><?php esc_html_e('Enable redirect functionality in basic settings', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('Configure which content areas to process', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('Customize redirect page appearance', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('Add domains to whitelist that should not redirect', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('View access data in statistics tab', 'wpnav-links'); ?></li>
                        </ol>
                    </div>
                </div>

                <?php if (!empty($stats)) : ?>
                  <div class="card">
                <form method="get" action="">
                    <input type="hidden" name="page" value="wpnav-links" />
                    <input type="hidden" name="tab" value="logs_statistics" />
                    <table class="form-table">
                        <tr>
                            <th scope="row"><?php esc_html_e('Date Range', 'wpnav-links'); ?></th>
                            <td>
                                <input type="date" name="start_date" value="<?php echo esc_attr($start_date); ?>" />
                                <span style="margin: 0 10px;"><?php esc_html_e('to', 'wpnav-links'); ?></span>
                                <input type="date" name="end_date" value="<?php echo esc_attr($end_date); ?>" />
                                <input type="submit" class="button" value="<?php esc_attr_e('Apply Filter', 'wpnav-links'); ?>" style="margin-left: 10px;" />
                                <a href="<?php echo esc_url(admin_url('tools.php?page=wpnav-links&tab=logs_statistics')); ?>" class="button"><?php esc_html_e('Reset', 'wpnav-links'); ?></a>
                            </td>
                        </tr>
                    </table>
                </form>
                </div>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th scope="col" class="column-target-url"><?php esc_html_e('Target URL', 'wpnav-links'); ?></th>
                            <th scope="col" class="column-source-page"><?php esc_html_e('Source Page', 'wpnav-links'); ?></th>
                            <th scope="col" class="column-click-time"><?php esc_html_e('Time', 'wpnav-links'); ?></th>
                            <th scope="col" class="column-ip-address"><?php esc_html_e('IP Address', 'wpnav-links'); ?></th>
                            <th scope="col" class="column-https-status"><?php esc_html_e('Security', 'wpnav-links'); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($stats as $row) : ?>
                        <tr>
                            <td class="column-target-url">
                                <a href="<?php echo esc_url($row->target_url); ?>" target="_blank" title="<?php echo esc_attr($row->target_url); ?>" class="wpnav-url-link">
                                    <?php echo esc_html(substr($row->target_url, 0, 50) . (strlen($row->target_url) > 50 ? '...' : '')); ?>
                                    <span class="dashicons dashicons-external" style="font-size: 12px;"></span>
                                </a>
                            </td>
                            <td class="column-source-page">
                                <?php if (!empty($row->source_page)) : ?>
                                <a href="<?php echo esc_url($row->source_page); ?>" target="_blank" title="<?php echo esc_attr($row->source_page); ?>" class="wpnav-url-link">
                                    <?php echo esc_html(substr($row->source_page, 0, 50) . (strlen($row->source_page) > 50 ? '...' : '')); ?>
                                    <span class="dashicons dashicons-external" style="font-size: 12px;"></span>
                                </a>
                                <?php else : ?>
                                <span class="wpnav-no-data"><?php esc_html_e('N/A', 'wpnav-links'); ?></span>
                                <?php endif; ?>
                            </td>
                            <td class="column-click-time">
                                <span title="<?php echo esc_attr($row->click_time); ?>">
                                    <?php echo esc_html(date('M j, H:i', strtotime($row->click_time))); ?>
                                </span>
                            </td>
                            <td class="column-ip-address">
                                <code><?php echo esc_html($row->user_ip); ?></code>
                            </td>
                            <td class="column-https-status">
                                <?php $is_https = strpos($row->target_url, 'https://') === 0; ?>
                                <span class="wpnav-https-badge <?php echo $is_https ? 'secure' : 'insecure'; ?>">
                                    <?php echo $is_https ? 'HTTPS' : 'HTTP'; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php
                if ($total_pages > 1) {
                    $current_url = remove_query_arg('paged');
                    echo '<div class="tablenav bottom"><div class="tablenav-pages">';

                    if ($current_page > 1) {
                        echo '<a class="button" href="' . esc_url(add_query_arg('paged', $current_page - 1, $current_url)) . '">&laquo;</a> ';
                    }

                    echo '<span class="paging-input">' . sprintf(__('Page %1$s of %2$s', 'wpnav-links'), $current_page, $total_pages) . '</span>';

                    if ($current_page < $total_pages) {
                        echo ' <a class="button" href="' . esc_url(add_query_arg('paged', $current_page + 1, $current_url)) . '">&raquo;</a>';
                    }

                    echo '</div></div>';
                }
                ?>

                <?php else : ?>
                <div class="wpnav-no-data-message">
                    <p><?php esc_html_e('No redirect records found for the selected date range.', 'wpnav-links'); ?></p>
                    <p class="description"><?php esc_html_e('Records will appear here after users click on external links processed by the plugin.', 'wpnav-links'); ?></p>
                </div>
                <?php endif; ?>

        </div>
    </div>
        </div>

    <div class="card">
        <h2><?php esc_html_e('System Information', 'wpnav-links'); ?></h2>
        <table class="wp-list-table widefat fixed">
            <tbody>
                <tr>
                    <th><?php esc_html_e('WordPress Version', 'wpnav-links'); ?></th>
                    <td><?php echo get_bloginfo('version'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('PHP Version', 'wpnav-links'); ?></th>
                    <td><?php echo PHP_VERSION; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Permalink Structure', 'wpnav-links'); ?></th>
                    <td><?php echo get_option('permalink_structure') ?: esc_html__('Default', 'wpnav-links'); ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Database Table', 'wpnav-links'); ?></th>
                    <td><?php global $wpdb; echo $wpdb->prefix . WPNAV_LINKS_TABLE; ?></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Plugin URL', 'wpnav-links'); ?></th>
                    <td><code><?php echo home_url('/go/'); ?></code></td>
                </tr>
                <tr>
                    <th><?php esc_html_e('Custom Template', 'wpnav-links'); ?></th>
                    <td><?php echo $custom_template_exists ? esc_html__('Yes - Custom template detected', 'wpnav-links') : esc_html__('No - Using default template', 'wpnav-links'); ?></td>
                </tr>
            </tbody>
        </table>
    </div>
</div>
