<?php

if (!defined('ABSPATH')) {
    exit;
}

class WPNAV_Public {
    private $options;
    private $security;

    public function __construct($options, $security) {
        $this->options = $options;
        $this->security = $security;

        if (isset($options['enabled']) && $options['enabled']) {
            add_action('wp_enqueue_scripts', array($this, 'enqueue_scripts'));
            $this->setup_content_filters();
            add_action('wp_footer', array($this, 'add_footer_script'));
        }
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            'wpnav-redirect-script',
            WPNAV_LINKS_PLUGIN_URL . 'redirect.js',
            array('jquery'),
            WPNAV_LINKS_VERSION,
            true
        );

        wp_localize_script(
            'wpnav-redirect-script',
            'wpnav_params',
            array(
                'home_url' => home_url(),
                'site_domain' => parse_url(home_url(), PHP_URL_HOST),
                'exclude_class' => $this->options['exclude_css_class'],
                'open_new_tab' => $this->options['open_in_new_tab'],
                'redirect_method' => isset($this->options['url_format']) ? $this->options['url_format'] : 'query',
                'url_encoding' => 'base64',
                'permalink_structure' => get_option('permalink_structure') ? true : false,
                'whitelist_domains' => $this->get_whitelist_domains(),
                'strings' => array(
                    'external_link' => __('External link', 'wpnav-links'),
                    'opens_new_window' => __('(opens in a new window)', 'wpnav-links')
                )
            )
        );
    }

    private function get_whitelist_domains() {
        $whitelist = array();
        if (!empty($this->options['whitelist_domains'])) {
            $whitelist = explode("\n", $this->options['whitelist_domains']);
            $whitelist = array_map('trim', $whitelist);
            $whitelist = array_filter($whitelist);
        }
        return $whitelist;
    }

    private function setup_content_filters() {
        if (isset($this->options['intercept_content']) && $this->options['intercept_content']) {
            add_filter('the_content', array($this, 'process_content'));
        }

        if (isset($this->options['intercept_comments']) && $this->options['intercept_comments']) {
            add_filter('comment_text', array($this, 'process_content'));
        }

        if (isset($this->options['intercept_widgets']) && $this->options['intercept_widgets']) {
            add_filter('widget_text', array($this, 'process_content'));
            add_filter('widget_custom_html_content', array($this, 'process_content'));
        }
    }

    public function process_content($content) {
        if (empty($content)) {
            return $content;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);

        $dom->loadHTML('<?xml encoding="UTF-8"><div>' . $content . '</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $links = $dom->getElementsByTagName('a');
        $modified = false;

        $links_to_process = array();
        foreach ($links as $link) {
            $links_to_process[] = $link;
        }

        foreach ($links_to_process as $link) {
            $href = $link->getAttribute('href');
            if (empty($href)) {
                continue;
            }

            if (!empty($this->options['exclude_css_class'])) {
                $class = $link->getAttribute('class');
                if (strpos($class, $this->options['exclude_css_class']) !== false) {
                    continue;
                }
            }

            if ($this->is_external_link($href)) {
                if ($this->security->check_whitelist_domain($href)) {
                    continue;
                }

                $rel = $link->getAttribute('rel');
                $rel_values = empty($rel) ? array() : explode(' ', $rel);
                $rel_values = array_merge($rel_values, array('nofollow', 'noopener', 'noreferrer'));
                $rel_values = array_unique($rel_values);
                $link->setAttribute('rel', implode(' ', $rel_values));

                if ($this->options['open_in_new_tab']) {
                    $link->setAttribute('target', '_blank');

                    // Add screen reader text for accessibility
                    $title = $link->getAttribute('title');
                    if (empty($title)) {
                        $link->setAttribute('title', __('External link (opens in a new window)', 'wpnav-links'));
                    }
                }

                $redirect_url = $this->security->get_redirect_url($href);
                $link->setAttribute('href', $redirect_url);
                $link->setAttribute('data-wpnav-external', '1');

                $modified = true;
            }
        }

        if (!$modified) {
            return $content;
        }

        $new_html = $dom->saveHTML($dom->documentElement);
        $new_html = preg_replace('/<\?xml encoding="UTF-8"\?>/', '', $new_html);
        $new_html = preg_replace('/<\/?div>/', '', $new_html);

        return $new_html;
    }

    private function is_external_link($url) {
        if (strpos($url, '://') === false && strpos($url, '//') !== 0) {
            return false;
        }

        $parsed_url = parse_url($url);

        if (empty($parsed_url) || !isset($parsed_url['host'])) {
            return false;
        }

        $site_domain = parse_url(home_url(), PHP_URL_HOST);
        $is_external = ($parsed_url['host'] !== $site_domain);

        if ($is_external) {
            if (!empty($this->options['auto_whitelist'])) {
                if (!empty($this->options['auto_whitelist']['same_root'])) {
                    $site_root = $this->get_root_domain($site_domain);
                    $url_root = $this->get_root_domain($parsed_url['host']);

                    if ($site_root === $url_root) {
                        $is_external = false;
                    }
                }

                if ($is_external && !empty($this->options['auto_whitelist']['search_engines'])) {
                    $search_engines = array('google.', 'bing.', 'yahoo.', 'baidu.', 'yandex.', 'duckduckgo.');
                    foreach ($search_engines as $engine) {
                        if (strpos($parsed_url['host'], $engine) !== false) {
                            $is_external = false;
                            break;
                        }
                    }
                }
            }
        }

        return $is_external;
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

    public function add_footer_script() {
        ?>
        <script type="text/javascript">
        (function($) {
            function processAjaxContent() {
                var observer = new MutationObserver(function(mutations) {
                    mutations.forEach(function(mutation) {
                        if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                            var $links = $(mutation.addedNodes).find('a');
                            if ($links.length) {
                                processExternalLinks($links);
                            }
                        }
                    });
                });

                observer.observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }

            function processExternalLinks($elements) {
                $elements.each(function() {
                    var $link = $(this);
                    var href = $link.attr('href');

                    if (!href) return;

                    if ($link.attr('data-wpnav-external')) return;

                    if ($link.hasClass(wpnav_params.exclude_class)) return;

                    if (isExternalLink(href) && !isWhitelistedDomain(href)) {
                        $link.attr('rel', 'nofollow noopener noreferrer');

                        if (wpnav_params.open_new_tab) {
                            $link.attr('target', '_blank');

                            // Add accessibility attributes
                            var currentTitle = $link.attr('title') || '';
                            if (!currentTitle.includes('<?php echo esc_js(__('opens in a new window', 'wpnav-links')); ?>')) {
                                var newTitle = currentTitle ?
                                    currentTitle + ' <?php echo esc_js(__('(opens in a new window)', 'wpnav-links')); ?>' :
                                    '<?php echo esc_js(__('External link (opens in a new window)', 'wpnav-links')); ?>';
                                $link.attr('title', newTitle);
                            }
                        }

                        $link.attr('href', getRedirectUrl(href));
                        $link.attr('data-wpnav-external', '1');
                    }
                });
            }

            function isExternalLink(url) {
                if (url.indexOf('://') === -1 && url.indexOf('//') !== 0) {
                    return false;
                }

                var parser = document.createElement('a');
                parser.href = url;

                return parser.hostname !== wpnav_params.site_domain;
            }

            function isWhitelistedDomain(url) {
                if (!wpnav_params.whitelist_domains || wpnav_params.whitelist_domains.length === 0) {
                    return false;
                }

                var parser = document.createElement('a');
                parser.href = url;
                var hostname = parser.hostname;

                return wpnav_params.whitelist_domains.indexOf(hostname) !== -1;
            }

            function getRedirectUrl(url) {
                var encodedUrl = encodeUrl(url);
                var currentPage = encodeURIComponent(window.location.href);

                switch(wpnav_params.redirect_method) {
                    case 'path':
                        if (wpnav_params.permalink_structure) {
                            return wpnav_params.home_url + '/go/' + encodedUrl;
                        } else {
                            return wpnav_params.home_url + '?wpnav_redirect=1&url_param=' + encodeURIComponent(encodedUrl) + '&ref=' + currentPage;
                        }
                        break;

                    case 'target':
                        return wpnav_params.home_url + '?wpnav_redirect=1&target=' + encodeURIComponent(url) + '&ref=' + currentPage;
                        break;

                    default:
                        return wpnav_params.home_url + '?wpnav_redirect=1&url=' + encodeURIComponent(encodedUrl) + '&ref=' + currentPage;
                        break;
                }
            }

            function encodeUrl(url) {
                if (wpnav_params.redirect_method === 'target') {
                    return url;
                }

                try {
                    return btoa(url).replace(/[+/=]/g, function(match) {
                        return {'+': '-', '/': '_', '=': ''}[match];
                    });
                } catch (e) {
                    return encodeURIComponent(url);
                }
            }

            $(document).ready(function() {
                processExternalLinks($('a:not([data-wpnav-external])'));
                processAjaxContent();
            });
        })(jQuery);
        </script>
        <?php
    }
}
