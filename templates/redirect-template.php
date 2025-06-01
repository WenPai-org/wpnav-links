<?php

if (!defined('ABSPATH')) {
    exit;
}

$target_url = get_query_var('target_url', '');
if (empty($target_url) && isset($_GET['target'])) {
    $target_url = urldecode($_GET['target']);
}

$source_url = get_query_var('source_url', '');
if (empty($source_url) && isset($_GET['ref'])) {
    $source_url = urldecode($_GET['ref']);
}

if (empty($target_url)) {
    wp_redirect(home_url());
    exit;
}

$options = get_option('wpnav_links_options');

$delay = isset($options['redirect_delay']) ? intval($options['redirect_delay']) : 5;
$auto_redirect_enabled = isset($options['auto_redirect_enabled']) ? $options['auto_redirect_enabled'] : 1;
$color_scheme = isset($options['color_scheme']) ? $options['color_scheme'] : 'blue';
$page_title = isset($options['page_title']) ? $options['page_title'] : __('External Link Warning', 'wpnav-links');
$page_subtitle = isset($options['page_subtitle']) ? $options['page_subtitle'] : '';
$url_label = isset($options['url_label']) ? $options['url_label'] : __('You are about to visit:', 'wpnav-links');
$warning_text = isset($options['warning_text']) ? $options['warning_text'] : __('You are about to leave this site and visit an external website. We are not responsible for the content of external websites.', 'wpnav-links');
$show_warning_message = isset($options['show_warning_message']) ? $options['show_warning_message'] : 1;
$cookie_duration = isset($options['cookie_duration']) ? intval($options['cookie_duration']) : 30;
$template = isset($options['template']) ? $options['template'] : 'default';
$show_logo = isset($options['show_logo']) ? $options['show_logo'] : false;
$show_url_full = isset($options['show_url_full']) ? $options['show_url_full'] : false;
$show_security_info = isset($options['show_security_info']) ? $options['show_security_info'] : 1;
$show_security_tips = isset($options['show_security_tips']) ? $options['show_security_tips'] : 0;
$show_back_button = isset($options['show_back_button']) ? $options['show_back_button'] : 1;
$custom_css = isset($options['custom_css']) ? $options['custom_css'] : '';
$button_text_continue = isset($options['button_text_continue']) ? $options['button_text_continue'] : __('Continue', 'wpnav-links');
$button_text_back = isset($options['button_text_back']) ? $options['button_text_back'] : __('Back', 'wpnav-links');
$button_style = isset($options['button_style']) ? $options['button_style'] : 'rounded';
$countdown_text = isset($options['countdown_text']) ? $options['countdown_text'] : __('Auto redirect in {seconds} seconds', 'wpnav-links');
$show_progress_bar = isset($options['show_progress_bar']) ? $options['show_progress_bar'] : 0;

$domain = parse_url($target_url, PHP_URL_HOST);
$is_https = strpos($target_url, 'https://') === 0;

$color_scheme_class = 'wpnav-color-' . $color_scheme;
$no_redirect_checked = isset($_COOKIE['wpnav_noredirect']) && $_COOKIE['wpnav_noredirect'] == '1';

$is_mobile = wp_is_mobile();
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="robots" content="noindex,nofollow">
    <title><?php printf(__('External Link Redirect - %s', 'wpnav-links'), get_bloginfo('name')); ?></title>

    <?php wp_head(); ?>

    <?php if (!empty($custom_css)) : ?>
    <style type="text/css">
        <?php echo wp_kses($custom_css, array()); ?>
    </style>
    <?php endif; ?>
</head>

<body class="wpnav-redirect-page <?php echo esc_attr($color_scheme_class); ?> <?php echo $is_mobile ? 'wpnav-mobile' : 'wpnav-desktop'; ?>" ontouchstart="">
    <div class="wpnav-page-overlay">
        <?php if ($template == 'simple') : ?>
        <div class="wpnav-container wpnav-simple">
            <h1 class="wpnav-title"><?php echo esc_html($page_title); ?></h1>

            <div class="wpnav-url-container">
                <div class="wpnav-url" data-url="<?php echo esc_attr($target_url); ?>">
                    <span class="wpnav-url-domain"><?php echo esc_html($domain); ?></span>
                </div>
            </div>

            <div class="wpnav-buttons">
                <button type="button" class="wpnav-btn wpnav-btn-primary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-continue" data-target="<?php echo esc_attr($target_url); ?>">
                    <?php echo esc_html($button_text_continue); ?>
                </button>
                <?php if ($show_back_button) : ?>
                <button type="button" class="wpnav-btn wpnav-btn-secondary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-back">
                    <?php echo esc_html($button_text_back); ?>
                </button>
                <?php endif; ?>
            </div>

            <?php if ($auto_redirect_enabled && $delay > 0) : ?>
            <div class="wpnav-countdown">
                <span id="wpnav-countdown-text"><?php echo str_replace('{seconds}', $delay, esc_html($countdown_text)); ?></span>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($template == 'minimal') : ?>
        <div class="wpnav-container wpnav-minimal">
            <?php if ($show_logo && has_custom_logo()) : ?>
            <div class="wpnav-logo">
                <?php the_custom_logo(); ?>
            </div>
            <?php endif; ?>

            <h1 class="wpnav-title"><?php echo esc_html($page_title); ?></h1>
            <?php if (!empty($page_subtitle)) : ?>
            <p class="wpnav-subtitle"><?php echo esc_html($page_subtitle); ?></p>
            <?php endif; ?>

            <?php if ($show_warning_message && !empty(trim($warning_text))) : ?>
            <div class="wpnav-warning"><?php echo esc_html($warning_text); ?></div>
            <?php endif; ?>

            <div class="wpnav-url-container">
                <div class="wpnav-url-label"><?php echo esc_html($url_label); ?></div>
                <div class="wpnav-url" data-url="<?php echo esc_attr($target_url); ?>">
                    <span class="wpnav-url-domain"><?php echo esc_html($domain); ?></span>
                    <?php if ($show_url_full) : ?>
                    <div class="wpnav-url-full"><?php echo esc_html($target_url); ?></div>
                    <?php endif; ?>
                    <?php if ($show_security_info) : ?>
                    <div class="wpnav-security-status">
                        <span class="wpnav-https-badge <?php echo $is_https ? 'secure' : 'insecure'; ?>">
                            <?php echo $is_https ? 'HTTPS' : 'HTTP'; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="wpnav-buttons">
                <button type="button" class="wpnav-btn wpnav-btn-primary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-continue" data-target="<?php echo esc_attr($target_url); ?>">
                    <?php echo esc_html($button_text_continue); ?>
                </button>
                <?php if ($show_back_button) : ?>
                <button type="button" class="wpnav-btn wpnav-btn-secondary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-back">
                    <?php echo esc_html($button_text_back); ?>
                </button>
                <?php endif; ?>
            </div>

            <div class="wpnav-options">
                <label class="wpnav-checkbox">
                    <input type="checkbox" id="wpnav-no-redirect" <?php checked($no_redirect_checked); ?>>
                    <span class="checkmark"></span>
                    <?php esc_html_e('Don\'t show this again', 'wpnav-links'); ?>
                </label>
            </div>

            <?php if ($auto_redirect_enabled && $delay > 0) : ?>
            <div class="wpnav-countdown">
                <span id="wpnav-countdown-text"><?php echo str_replace('{seconds}', $delay, esc_html($countdown_text)); ?></span>
                <?php if ($show_progress_bar) : ?>
                <div class="wpnav-progress-bar">
                    <div class="wpnav-progress-fill" id="progress-fill"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php elseif ($template == 'full') : ?>
        <div class="wpnav-container wpnav-full">
            <div class="wpnav-header">
                <?php if ($show_logo && has_custom_logo()) : ?>
                <div class="wpnav-logo">
                    <?php the_custom_logo(); ?>
                </div>
                <?php endif; ?>

                <h1 class="wpnav-title"><?php echo esc_html($page_title); ?></h1>
                <?php if (!empty($page_subtitle)) : ?>
                <p class="wpnav-subtitle"><?php echo esc_html($page_subtitle); ?></p>
                <?php endif; ?>
            </div>

            <?php if ($show_warning_message && !empty(trim($warning_text))) : ?>
            <div class="wpnav-warning">
                <?php echo esc_html($warning_text); ?>
            </div>
            <?php endif; ?>

            <div class="wpnav-info">
                <div class="wpnav-info-item">
                    <h3 class="wpnav-info-title"><?php esc_html_e('Destination Information', 'wpnav-links'); ?></h3>
                    <div class="wpnav-url-container">
                        <div class="wpnav-url-label"><?php echo esc_html($url_label); ?></div>
                        <div class="wpnav-url" data-url="<?php echo esc_attr($target_url); ?>">
                            <div class="wpnav-url-parts">
                                <span class="wpnav-url-domain"><?php echo esc_html($domain); ?></span>
                                <span class="wpnav-url-path"><?php echo esc_html(str_replace($domain, '', parse_url($target_url, PHP_URL_HOST) . parse_url($target_url, PHP_URL_PATH))); ?></span>
                            </div>
                            <?php if ($show_url_full) : ?>
                            <div class="wpnav-url-full"><?php echo esc_html($target_url); ?></div>
                            <?php endif; ?>
                            <button type="button" class="wpnav-copy-btn" data-copy="<?php echo esc_attr($target_url); ?>">
                                <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                                    <path d="M16 1H4C2.9 1 2 1.9 2 3V17H4V3H16V1ZM19 5H8C6.9 5 6 5.9 6 7V21C6 22.1 6.9 23 8 23H19C20.1 23 21 22.1 21 21V7C21 5.9 20.1 5 19 5ZM19 21H8V7H19V21Z" fill="currentColor"/>
                                </svg>
                                <?php esc_html_e('Copy', 'wpnav-links'); ?>
                            </button>
                        </div>
                    </div>

                    <?php if ($show_security_info) : ?>
                    <div class="wpnav-security-status">
                        <div class="wpnav-security-header">
                            <span class="wpnav-security-label"><?php esc_html_e('Security Status', 'wpnav-links'); ?></span>
                            <span class="wpnav-https-badge <?php echo $is_https ? 'secure' : 'insecure'; ?>">
                                <?php echo $is_https ? 'HTTPS' : 'HTTP'; ?>
                            </span>
                        </div>
                        <?php if (!$is_https) : ?>
                        <div class="wpnav-security-warning">
                            <p>⚠️ <?php esc_html_e('This website does not use HTTPS encryption. Your data may not be secure.', 'wpnav-links'); ?></p>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if ($show_security_tips) : ?>
                <div class="wpnav-info-item">
                    <h3 class="wpnav-info-title"><?php esc_html_e('Security Tips', 'wpnav-links'); ?></h3>
                    <div class="wpnav-tips">
                        <ul class="wpnav-tips-list">
                            <li><?php esc_html_e('Verify the link is from a trusted source', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('Avoid entering personal information on HTTP sites', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('Be cautious of download requests', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('Check for secure HTTPS connection', 'wpnav-links'); ?></li>
                        </ul>
                    </div>

                    <?php if (!$is_https) : ?>
                    <div class="wpnav-warning-tips">
                        <h4>⚠️ <?php esc_html_e('Additional Caution Required', 'wpnav-links'); ?></h4>
                        <ul>
                            <li><?php esc_html_e('This link does not use secure HTTPS', 'wpnav-links'); ?></li>
                            <li><?php esc_html_e('Consider if this site is trustworthy before proceeding', 'wpnav-links'); ?></li>
                        </ul>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="wpnav-actions">
                <div class="wpnav-buttons">
                    <button type="button" class="wpnav-btn wpnav-btn-primary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-continue" data-target="<?php echo esc_attr($target_url); ?>">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 4l-1.41 1.41L16.17 11H4v2h12.17l-5.58 5.59L12 20l8-8z" fill="currentColor"/>
                        </svg>
                        <?php echo esc_html($button_text_continue); ?>
                    </button>

                    <?php if ($show_back_button) : ?>
                    <button type="button" class="wpnav-btn wpnav-btn-secondary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-back">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none">
                            <path d="M12 20l1.41-1.41L7.83 13H20v-2H7.83l5.58-5.59L12 4l-8 8z" fill="currentColor"/>
                        </svg>
                        <?php echo esc_html($button_text_back); ?>
                    </button>
                    <?php endif; ?>
                </div>

                <div class="wpnav-options">
                    <label class="wpnav-checkbox">
                        <input type="checkbox" id="wpnav-no-redirect" <?php checked($no_redirect_checked); ?>>
                        <span class="checkmark"></span>
                        <?php printf(__('Don\'t show this warning for %d days', 'wpnav-links'), $cookie_duration); ?>
                    </label>
                </div>

                <?php if ($auto_redirect_enabled && $delay > 0) : ?>
                <div class="wpnav-countdown">
                    <div class="countdown-circle">
                        <svg width="32" height="32" viewBox="0 0 32 32">
                            <circle cx="16" cy="16" r="14" stroke="currentColor" stroke-width="2" fill="none" opacity="0.3"/>
                            <circle id="countdown-progress" cx="16" cy="16" r="14" stroke="currentColor" stroke-width="2" fill="none"
                                stroke-dasharray="87.96" stroke-dashoffset="0" transform="rotate(-90 16 16)"/>
                        </svg>
                    </div>
                    <span id="wpnav-countdown-text"><?php echo str_replace('{seconds}', $delay, esc_html($countdown_text)); ?></span>
                    <?php if ($show_progress_bar) : ?>
                    <div class="wpnav-progress-bar">
                        <div class="wpnav-progress-fill" id="progress-fill"></div>
                    </div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>

            <div class="wpnav-footer">
                <div class="wpnav-footer-text">
                    <?php printf(__('Protected by %s', 'wpnav-links'), '<a href="' . esc_url(home_url()) . '">' . esc_html(get_bloginfo('name')) . '</a>'); ?>
                </div>
            </div>
        </div>

        <?php else : ?>
        <div class="wpnav-container wpnav-default">
            <?php if ($show_logo && has_custom_logo()) : ?>
            <div class="wpnav-logo">
                <?php the_custom_logo(); ?>
            </div>
            <?php endif; ?>

            <h1 class="wpnav-title"><?php echo esc_html($page_title); ?></h1>
            <?php if (!empty($page_subtitle)) : ?>
            <p class="wpnav-subtitle"><?php echo esc_html($page_subtitle); ?></p>
            <?php endif; ?>

            <?php if ($show_warning_message && !empty(trim($warning_text))) : ?>
            <div class="wpnav-warning">
                <?php echo esc_html($warning_text); ?>
            </div>
            <?php endif; ?>

            <div class="wpnav-url-container">
                <div class="wpnav-url-label"><?php echo esc_html($url_label); ?></div>
                <div class="wpnav-url" data-url="<?php echo esc_attr($target_url); ?>">
                    <span class="wpnav-url-domain"><?php echo esc_html($domain); ?></span>
                    <?php if ($show_url_full) : ?>
                    <div class="wpnav-url-full"><?php echo esc_html($target_url); ?></div>
                    <?php endif; ?>
                    <?php if ($show_security_info) : ?>
                    <div class="wpnav-security-status">
                        <span class="wpnav-https-badge <?php echo $is_https ? 'secure' : 'insecure'; ?>">
                            <?php echo $is_https ? 'HTTPS' : 'HTTP'; ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <?php if ($show_security_tips) : ?>
            <div class="wpnav-security-tips">
                <h4><?php esc_html_e('Security Tips', 'wpnav-links'); ?></h4>
                <ul>
                    <li><?php esc_html_e('Verify the link is from a trusted source', 'wpnav-links'); ?></li>
                    <li><?php esc_html_e('Check for secure HTTPS connection', 'wpnav-links'); ?></li>
                    <li><?php esc_html_e('Be cautious of download requests', 'wpnav-links'); ?></li>
                </ul>
            </div>
            <?php endif; ?>

            <div class="wpnav-buttons">
                <button type="button" class="wpnav-btn wpnav-btn-primary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-continue" data-target="<?php echo esc_attr($target_url); ?>">
                    <?php echo esc_html($button_text_continue); ?>
                </button>
                <?php if ($show_back_button) : ?>
                <button type="button" class="wpnav-btn wpnav-btn-secondary wpnav-btn-<?php echo esc_attr($button_style); ?>" id="wpnav-back">
                    <?php echo esc_html($button_text_back); ?>
                </button>
                <?php endif; ?>
            </div>

            <div class="wpnav-options">
                <label class="wpnav-checkbox">
                    <input type="checkbox" id="wpnav-no-redirect" <?php checked($no_redirect_checked); ?>>
                    <span class="checkmark"></span>
                    <?php printf(__('Don\'t show this again (%d days)', 'wpnav-links'), $cookie_duration); ?>
                </label>
            </div>

            <?php if ($auto_redirect_enabled && $delay > 0) : ?>
            <div class="wpnav-countdown">
                <span id="wpnav-countdown-text"><?php echo str_replace('{seconds}', $delay, esc_html($countdown_text)); ?></span>
                <?php if ($show_progress_bar) : ?>
                <div class="wpnav-progress-bar">
                    <div class="wpnav-progress-fill" id="progress-fill"></div>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

    <script type="text/javascript">
        (function() {
            var countdown = <?php echo $auto_redirect_enabled ? esc_js($delay) : 0; ?>;
            var countdownElement = document.getElementById('wpnav-countdown-text');
            var progressElement = document.getElementById('progress-fill');
            var continueButton = document.getElementById('wpnav-continue');
            var backButton = document.getElementById('wpnav-back');
            var targetUrl = continueButton ? continueButton.getAttribute('data-target') : '';
            var noRedirectCheckbox = document.getElementById('wpnav-no-redirect');
            var copyButtons = document.querySelectorAll('.wpnav-copy-btn');
            var countdownTemplate = <?php echo json_encode($countdown_text); ?>;

            var initialCountdown = countdown;
            var autoRedirectEnabled = <?php echo $auto_redirect_enabled ? 'true' : 'false'; ?>;

            // Enhanced back button functionality
            function goBack() {
                var referrer = document.referrer;
                var sourceUrl = <?php echo json_encode($source_url); ?>;

                // Try multiple back strategies
                if (referrer && referrer !== window.location.href) {
                    // If we have a valid referrer, go there
                    window.location.href = referrer;
                } else if (sourceUrl && sourceUrl !== window.location.href) {
                    // If we have source URL from plugin, use it
                    window.location.href = sourceUrl;
                } else if (window.history.length > 1) {
                    // Use browser history if available
                    window.history.back();
                } else {
                    // Fallback to homepage
                    window.location.href = <?php echo json_encode(home_url()); ?>;
                }
            }

            if (backButton) {
                backButton.addEventListener('click', function(e) {
                    e.preventDefault();
                    goBack();
                });
            }

            if (noRedirectCheckbox) {
                noRedirectCheckbox.addEventListener('change', function() {
                    var cookieExpiry = new Date();
                    cookieExpiry.setDate(cookieExpiry.getDate() + <?php echo esc_js($cookie_duration); ?>);
                    document.cookie = "wpnav_noredirect=" + (this.checked ? "1" : "0") +
                                     "; expires=" + cookieExpiry.toUTCString() +
                                     "; path=/; SameSite=Strict";
                });
            }

            copyButtons.forEach(function(btn) {
                btn.addEventListener('click', function() {
                    var url = this.getAttribute('data-copy');
                    if (navigator.clipboard) {
                        navigator.clipboard.writeText(url).then(function() {
                            showToast(<?php echo json_encode(__('Link copied to clipboard', 'wpnav-links')); ?>);
                        });
                    } else {
                        var input = document.createElement('input');
                        input.value = url;
                        document.body.appendChild(input);
                        input.select();
                        document.execCommand('copy');
                        document.body.removeChild(input);
                        showToast(<?php echo json_encode(__('Link copied to clipboard', 'wpnav-links')); ?>);
                    }
                });
            });

            function showToast(message) {
                var toast = document.createElement('div');
                toast.className = 'wpnav-toast';
                toast.textContent = message;
                document.body.appendChild(toast);

                setTimeout(function() {
                    toast.classList.add('show');
                }, 100);

                setTimeout(function() {
                    toast.classList.remove('show');
                    setTimeout(function() {
                        if (document.body.contains(toast)) {
                            document.body.removeChild(toast);
                        }
                    }, 300);
                }, 2000);
            }

            if (continueButton) {
                continueButton.addEventListener('click', function() {
                    window.location.href = targetUrl;
                });

                continueButton.addEventListener('touchstart', function() {
                    this.classList.add('touched');
                });
            }

            if (autoRedirectEnabled && countdown > 0 && countdownElement && targetUrl) {
                var countdownTimer = setInterval(function() {
                    countdown--;

                    if (countdownElement) {
                        var displayText = countdownTemplate.replace('{seconds}', countdown);
                        countdownElement.textContent = displayText;
                    }

                    if (progressElement) {
                        var progress = (initialCountdown - countdown) / initialCountdown;
                        progressElement.style.width = (100 - (progress * 100)) + '%';
                    }

                    if (countdown <= 0) {
                        clearInterval(countdownTimer);
                        if (continueButton) {
                            continueButton.click();
                        }
                    }
                }, 1000);
            }

            // Enhanced keyboard navigation
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    e.preventDefault();
                    goBack();
                } else if (e.key === 'Enter' && continueButton) {
                    e.preventDefault();
                    continueButton.click();
                } else if (e.key === 'Backspace' && backButton) {
                    e.preventDefault();
                    goBack();
                }
            });

            // Enhanced touch gestures
            var startY = 0;
            var currentY = 0;
            var isDragging = false;

            document.addEventListener('touchstart', function(e) {
                startY = e.touches[0].clientY;
                isDragging = true;
            });

            document.addEventListener('touchmove', function(e) {
                if (!isDragging) return;
                currentY = e.touches[0].clientY;
                var diff = currentY - startY;

                if (Math.abs(diff) > 100) {
                    if (diff > 0 && backButton) {
                        goBack();
                    } else if (diff < 0 && continueButton) {
                        continueButton.click();
                    }
                    isDragging = false;
                }
            });

            document.addEventListener('touchend', function() {
                isDragging = false;
            });

            // Focus management for accessibility
            if (continueButton) {
                continueButton.focus();
            }
        })();
    </script>

    <?php wp_footer(); ?>
</body>
</html>
