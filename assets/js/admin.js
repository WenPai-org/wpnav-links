jQuery(document).ready(function($) {
    $('.wpnav-tab').click(function(e) {
        e.preventDefault();

        $('.wpnav-tab').removeClass('active');
        $(this).addClass('active');

        var tab = $(this).data('tab');
        $('.wpnav-tab-section').hide();
        $('.wpnav-tab-section[data-section="' + tab + '"]').show();

        if (history.pushState) {
            var newUrl = updateUrlParameter(window.location.href, 'tab', tab);
            history.pushState({path: newUrl}, '', newUrl);
        }
    });

    $('.wpnav-add-domain').click(function(e) {
        e.preventDefault();
        var domain = $(this).data('domain');
        var $textarea = $('textarea[name="whitelist_domains"]');
        var domains = $textarea.val().split('\n').filter(function(d) { return d.trim(); });

        if (domains.indexOf(domain) >= 0) {
            showNotice('wpnav-whitelist-status', wpnav_admin.strings.domain_exists.replace('%s', domain), 'error');
            return;
        }

        domains.push(domain);
        $textarea.val(domains.join('\n'));
        showNotice('wpnav-whitelist-status', wpnav_admin.strings.domain_added.replace('%s', domain), 'success');

        $(this).addClass('wpnav-loading').prop('disabled', true);
        setTimeout(function() {
            $(this).removeClass('wpnav-loading').prop('disabled', false);
        }.bind(this), 1000);
    });

    $('#wpnav-select-all-domains').click(function(e) {
        e.preventDefault();
        var commonDomains = [
            'google.com', 'facebook.com', 'youtube.com', 'twitter.com',
            'instagram.com', 'linkedin.com', 'baidu.com', 'bing.com'
        ];
        var $textarea = $('textarea[name="whitelist_domains"]');
        var currentDomains = $textarea.val().split('\n').filter(function(d) { return d.trim(); });
        var newDomains = [...new Set([...currentDomains, ...commonDomains])];

        $textarea.val(newDomains.join('\n'));
        showNotice('wpnav-whitelist-status', wpnav_admin.strings.common_domains_added, 'success');
    });

    $('#wpnav-clear-domains').click(function(e) {
        e.preventDefault();
        if (confirm(wpnav_admin.strings.confirm_clear)) {
            $('textarea[name="whitelist_domains"]').val('');
            showNotice('wpnav-whitelist-status', wpnav_admin.strings.whitelist_cleared, 'success');
        }
    });

    $('#wpnav-export-whitelist').click(function(e) {
        e.preventDefault();

        var whitelist = $('textarea[name="whitelist_domains"]').val();
        var domains = whitelist.split('\n').filter(function(d) { return d.trim(); });

        if (domains.length === 0) {
            showNotice('wpnav-whitelist-status', wpnav_admin.strings.whitelist_empty, 'error');
            return;
        }

        var csv = domains.join('\n');
        var blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        var link = document.createElement('a');

        if (link.download !== undefined) {
            var url = URL.createObjectURL(blob);
            link.setAttribute('href', url);
            link.setAttribute('download', 'wpnav_whitelist_' + getFormattedDate() + '.csv');
            link.style.visibility = 'hidden';
            document.body.appendChild(link);
            link.click();
            document.body.removeChild(link);

            showNotice('wpnav-whitelist-status', wpnav_admin.strings.export_success, 'success');
        } else {
            showNotice('wpnav-whitelist-status', wpnav_admin.strings.export_unsupported, 'error');
        }
    });

    $('form').submit(function(e) {
        var $form = $(this);
        var $submitButton = $form.find('input[type="submit"], button[type="submit"]');

        if ($form.attr('id') === 'sync-settings-form' || $form.attr('id') === 'sync-config-form') {
            return true;
        }

        var formValid = validateForm($form);
        if (!formValid) {
            e.preventDefault();
            return false;
        }

        if ($submitButton.length) {
            var originalValue = $submitButton.val() || $submitButton.text();
            $submitButton.prop('disabled', true).addClass('wpnav-loading');

            if ($submitButton.is('input')) {
                $submitButton.val(wpnav_admin.strings.saving);
            } else {
                $submitButton.text(wpnav_admin.strings.saving);
            }

            setTimeout(function() {
                $submitButton.prop('disabled', false).removeClass('wpnav-loading');
                if ($submitButton.is('input')) {
                    $submitButton.val(originalValue);
                } else {
                    $submitButton.text(originalValue);
                }
            }, 10000);
        }
    });

    if (window.location.search.indexOf('settings-updated=true') !== -1) {
        var message = wpnav_admin.strings.settings_saved || 'Settings saved successfully!';
        if (window.location.search.indexOf('imported=') !== -1) {
            var match = window.location.search.match(/imported=(\d+)/);
            if (match) {
                message = wpnav_admin.strings.domains_imported ?
                    wpnav_admin.strings.domains_imported.replace('%d', match[1]) :
                    'Successfully imported ' + match[1] + ' domains.';
            }
        }

        $('.wrap h1').after('<div class="notice notice-success is-dismissible"><p>' + message + '</p></div>');

        setTimeout(function() {
            $('.notice-success').fadeIn();
        }, 100);
    }

    if (window.location.search.indexOf('tab=') !== -1) {
        setTimeout(function() {
            $('.notice-success').fadeIn();
        }, 100);
    }

    $('.wp-list-table tbody tr').hover(
        function() {
            $(this).addClass('wpnav-row-hover');
        },
        function() {
            $(this).removeClass('wpnav-row-hover');
        }
    );

    $('#auto_redirect_enabled').change(function() {
        if ($(this).is(':checked')) {
            $('#redirect_delay_row').show();
        } else {
            $('#redirect_delay_row').hide();
        }
    });

    $('#page_title').on('input', function() {
        $('#preview-page-title').text($(this).val());
    });

    $('#page_subtitle').on('input', function() {
        var subtitle = $(this).val();
        if (subtitle) {
            $('#preview-page-subtitle').text(subtitle).show();
        } else {
            $('#preview-page-subtitle').hide();
        }
    });

    $('#url_label').on('input', function() {
        $('#preview-url-label').text($(this).val());
    });

    $('#warning_text').on('input', function() {
        $('#preview-warning-text').text($(this).val());
    });

    $('#countdown_text').on('input', function() {
        var text = $(this).val().replace('{seconds}', '5');
        $('#preview-countdown-text').text(text);
    });

    $('#button_text_continue').on('input', function() {
        $('#preview-continue-btn').text($(this).val());
    });

    $('#button_text_back').on('input', function() {
        $('#preview-back-btn').text($(this).val());
    });

    $('input[name="show_warning_message"], textarea[name="warning_text"]').on('change input', function() {
        var showWarning = $('input[name="show_warning_message"]').is(':checked');
        var warningText = $('textarea[name="warning_text"]').val().trim();

        if (showWarning && warningText !== '') {
            $('#preview-warning').show();
        } else {
            $('#preview-warning').hide();
        }
    });

    function setupRedirectPagePreview() {
        function updatePreview() {
            var colorScheme = $('select[name="color_scheme"]').val();
            var template = $('input[name="template"]:checked').val();
            var warningText = $('textarea[name="warning_text"]').val();
            var showWarningMessage = $('input[name="show_warning_message"]').is(':checked');
            var continueText = $('input[name="button_text_continue"]').val();
            var backText = $('input[name="button_text_back"]').val();
            var showLogo = $('input[name="show_logo"]').is(':checked');
            var showUrlFull = $('input[name="show_url_full"]').is(':checked');
            var showSecurityTips = $('input[name="show_security_tips"]').is(':checked');
            var showBackButton = $('input[name="show_back_button"]').is(':checked');

            var $preview = $('.preview-container');
            $preview.removeClass('wpnav-color-blue wpnav-color-green wpnav-color-red');
            $preview.addClass('wpnav-color-' + colorScheme);

            var $container = $preview.find('.wpnav-container');
            $container.removeClass('wpnav-simple wpnav-minimal wpnav-default wpnav-full');
            $container.addClass('wpnav-' + template);

            $('#preview-warning-text').text(warningText);
            $('#preview-continue-btn').text(continueText);
            $('#preview-back-btn').text(backText);

            $('.wpnav-site-logo').toggle(showLogo);
            $('.wpnav-url-full').toggle(showUrlFull);
            $('.wpnav-security-tips').toggle(showSecurityTips);
            $('#preview-back-btn').toggle(showBackButton);
            $('#preview-warning').toggle(showWarningMessage && warningText.trim() !== '');

            updateTemplateSpecificElements(template);
        }

        function updateTemplateSpecificElements(template) {
            var $container = $('.wpnav-container');
            var $header = $('.wpnav-header');

            switch(template) {
                case 'simple':
                    $container.css({
                        'max-width': '480px',
                        'padding': '30px 25px',
                        'text-align': 'center'
                    });
                    $header.find('.wpnav-title').css('font-size', '22px');
                    break;

                case 'minimal':
                    $container.css({
                        'max-width': '680px',
                        'padding': '30px',
                        'text-align': 'center'
                    });
                    $header.find('.wpnav-title').css('font-size', '24px');
                    break;

                case 'full':
                    $container.css({
                        'max-width': '880px',
                        'padding': '50px',
                        'text-align': 'left'
                    });
                    $header.find('.wpnav-title').css('font-size', '36px');
                    break;

                default:
                    $container.css({
                        'max-width': '780px',
                        'padding': '40px',
                        'text-align': 'center'
                    });
                    $header.find('.wpnav-title').css('font-size', '28px');
                    break;
            }
        }

        $('select[name="color_scheme"]').on('change', updatePreview);
        $('input[name="template"]').on('change', updatePreview);
        $('textarea[name="warning_text"]').on('input', updatePreview);
        $('input[name="show_warning_message"]').on('change', updatePreview);
        $('input[name="button_text_continue"]').on('input', updatePreview);
        $('input[name="button_text_back"]').on('input', updatePreview);
        $('input[name="show_logo"]').on('change', updatePreview);
        $('input[name="show_url_full"]').on('change', updatePreview);
        $('input[name="show_security_tips"]').on('change', updatePreview);
        $('input[name="show_back_button"]').on('change', updatePreview);

        updatePreview();
    }

    if ($('.wpnav-tab[data-tab="redirect_page"]').length) {
        setupRedirectPagePreview();
    }

    $('#redirect_delay, #cookie_duration, #stats_retention').on('input', function() {
        var $this = $(this);
        var val = parseInt($this.val());
        var min = parseInt($this.attr('min'));
        var max = parseInt($this.attr('max'));

        if (val < min || val > max) {
            $this.addClass('error').css('border-color', '#dc3232');
        } else {
            $this.removeClass('error').css('border-color', '');
        }
    });

    var draftTimeout;
    $('textarea[name="whitelist_domains"], textarea[name="warning_text"], textarea[name="custom_css"]').on('input', function() {
        var $this = $(this);
        clearTimeout(draftTimeout);

        draftTimeout = setTimeout(function() {
            var draftKey = 'wpnav_draft_' + $this.attr('name');
            try {
                sessionStorage.setItem(draftKey, $this.val());

                if ($this.siblings('.draft-saved').length === 0) {
                    $this.after('<span class="draft-saved" style="font-size: 11px; color: #46b450; margin-left: 5px;">' + wpnav_admin.strings.draft_saved + '</span>');
                    setTimeout(function() {
                        $('.draft-saved').fadeOut();
                    }, 2000);
                }
            } catch (e) {
                console.log('Session storage not available');
            }
        }, 2000);
    });

    $('textarea[name="whitelist_domains"], textarea[name="warning_text"], textarea[name="custom_css"]').each(function() {
        var $this = $(this);
        var draftKey = 'wpnav_draft_' + $this.attr('name');

        try {
            var draft = sessionStorage.getItem(draftKey);
            if (draft && draft !== $this.val() && draft.trim() !== '') {
                var restore = confirm(wpnav_admin.strings.restore_draft);
                if (restore) {
                    $this.val(draft);
                    if ($this.attr('name') === 'warning_text') {
                        $('#preview-warning-text').text(draft);
                    }
                }
            }
        } catch (e) {
            console.log('Session storage not available');
        }
    });

    $('form').on('submit', function() {
        setTimeout(function() {
            if (window.location.search.indexOf('settings-updated=true') !== -1) {
                try {
                    sessionStorage.removeItem('wpnav_draft_whitelist_domains');
                    sessionStorage.removeItem('wpnav_draft_warning_text');
                    sessionStorage.removeItem('wpnav_draft_custom_css');
                } catch (e) {
                    console.log('Session storage not available');
                }
            }
        }, 100);
    });

    function validateForm($form) {
        var isValid = true;
        var errors = [];

        var redirectDelay = $('#redirect_delay').val();
        if (redirectDelay && (redirectDelay < 1 || redirectDelay > 30)) {
            errors.push('Redirect delay must be between 1 and 30 seconds.');
            isValid = false;
        }

        var cookieDuration = $('#cookie_duration').val();
        if (cookieDuration && (cookieDuration < 1 || cookieDuration > 365)) {
            errors.push('Cookie duration must be between 1 and 365 days.');
            isValid = false;
        }

        var statsRetention = $('#stats_retention').val();
        if (statsRetention && (statsRetention < 1 || statsRetention > 365)) {
            errors.push('Stats retention must be between 1 and 365 days.');
            isValid = false;
        }

        if (!isValid) {
            var tabName = $form.closest('.wpnav-tab-section').attr('data-section');
            var statusId = 'wpnav-settings-status';

            if (tabName === 'whitelist') {
                statusId = 'wpnav-whitelist-status';
            } else if (tabName === 'redirect_page') {
                statusId = 'wpnav-redirect-status';
            } else if (tabName === 'logs_statistics') {
                statusId = 'wpnav-logs-status';
            }

            showNotice(statusId, errors.join(' '), 'error');
        }

        return isValid;
    }

    function updateUrlParameter(url, param, paramVal) {
        var newAdditionalURL = "";
        var tempArray = url.split("?");
        var baseURL = tempArray[0];
        var additionalURL = tempArray[1];
        var temp = "";

        if (additionalURL) {
            tempArray = additionalURL.split("&");
            for (var i = 0; i < tempArray.length; i++) {
                if (tempArray[i].split('=')[0] != param) {
                    newAdditionalURL += temp + tempArray[i];
                    temp = "&";
                }
            }
        }

        var rows_txt = temp + "" + param + "=" + paramVal;
        return baseURL + "?" + newAdditionalURL + rows_txt;
    }

    function showNotice(elementId, message, type) {
        var $notice = $('#' + elementId);

        if ($notice.length === 0) {
            $notice = $('<span id="' + elementId + '" class="wpnav-notice"></span>');
            $('.card h2').first().after($notice);
        }

        $notice.removeClass('wpnav-notice-success wpnav-notice-error')
               .addClass('wpnav-notice-' + type)
               .text(message)
               .show()
               .delay(4000)
               .fadeOut();
    }

    function getFormattedDate() {
        var now = new Date();
        var year = now.getFullYear();
        var month = ('0' + (now.getMonth() + 1)).slice(-2);
        var day = ('0' + now.getDate()).slice(-2);
        return year + month + day;
    }

    function initColorSchemePreview() {
        $('select[name="color_scheme"]').on('change', function() {
            var scheme = $(this).val();
            var $preview = $('.wpnav-redirect-preview');
            $preview.removeClass('wpnav-color-blue wpnav-color-green wpnav-color-red wpnav-color-light');
            $preview.addClass('wpnav-color-' + scheme);
        });
    }

    if ($('.wpnav-redirect-preview').length) {
        initColorSchemePreview();
    }

    console.log('WPNav Links Admin initialized');

    var activeTab = $('.wpnav-tab.active').data('tab') || 'basic_settings';
    if (activeTab) {
        $('.wpnav-tab-section').hide();
        $('.wpnav-tab-section[data-section="' + activeTab + '"]').show();
    }

    var urlParams = new URLSearchParams(window.location.search);
    var tabFromUrl = urlParams.get('tab');
    if (tabFromUrl) {
        $('.wpnav-tab').removeClass('active');
        $('.wpnav-tab[data-tab="' + tabFromUrl + '"]').addClass('active');
        $('.wpnav-tab-section').hide();
        $('.wpnav-tab-section[data-section="' + tabFromUrl + '"]').show();
    }
});