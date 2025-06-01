(function($) {
    'use strict';

    var wpnavRedirect = {
        init: function() {
            this.processExistingLinks();
            this.setupMutationObserver();
            this.setupEventHandlers();
            this.optimizeForMobile();
        },

        processExistingLinks: function() {
            var self = this;
            $('a:not([data-wpnav-processed])').each(function() {
                self.processLink($(this));
            });
        },

        processLink: function($link) {
            var href = $link.attr('href');

            if (!href || this.shouldSkipLink(href, $link)) {
                $link.attr('data-wpnav-processed', '1');
                return;
            }

            if (this.isExternalLink(href) && !this.isWhitelistedDomain(href)) {
                this.processExternalLink($link, href);
            }

            $link.attr('data-wpnav-processed', '1');
        },

        shouldSkipLink: function(href, $link) {
            if (href.startsWith('#') || href.startsWith('javascript:') || href.startsWith('mailto:') || href.startsWith('tel:')) {
                return true;
            }

            if (wpnav_params.exclude_class && $link.hasClass(wpnav_params.exclude_class)) {
                return true;
            }

            if ($link.attr('data-wpnav-external') || $link.attr('data-wpnav-processed')) {
                return true;
            }

            return false;
        },

        isExternalLink: function(url) {
            if (!url.match(/^(https?:)?\/\//i)) {
                return false;
            }

            try {
                var parser = document.createElement('a');
                parser.href = url;
                var linkDomain = parser.hostname.toLowerCase();
                var siteDomain = wpnav_params.site_domain.toLowerCase();

                return linkDomain !== siteDomain;
            } catch (e) {
                return false;
            }
        },

        isWhitelistedDomain: function(url) {
            if (!wpnav_params.whitelist_domains || wpnav_params.whitelist_domains.length === 0) {
                return false;
            }

            try {
                var parser = document.createElement('a');
                parser.href = url;
                var hostname = parser.hostname.toLowerCase();

                for (var i = 0; i < wpnav_params.whitelist_domains.length; i++) {
                    var whitelistDomain = wpnav_params.whitelist_domains[i].toLowerCase();
                    if (hostname === whitelistDomain) {
                        return true;
                    }
                    if (whitelistDomain.indexOf('*') !== -1) {
                        var pattern = whitelistDomain.replace(/\*/g, '.*');
                        var regex = new RegExp('^' + pattern + '$', 'i');
                        if (regex.test(hostname)) {
                            return true;
                        }
                    }
                }
            } catch (e) {
                return false;
            }

            return false;
        },

        processExternalLink: function($link, href) {
            var rel = $link.attr('rel') || '';
            var relValues = rel ? rel.split(' ') : [];

            ['nofollow', 'noopener', 'noreferrer'].forEach(function(value) {
                if (relValues.indexOf(value) === -1) {
                    relValues.push(value);
                }
            });

            $link.attr('rel', relValues.join(' '));

            if (wpnav_params.open_new_tab) {
                $link.attr('target', '_blank');
            }

            var redirectUrl = this.getRedirectUrl(href);
            $link.attr('href', redirectUrl);
            $link.attr('data-wpnav-external', '1');

            this.addExternalIndicator($link);
        },

        addExternalIndicator: function($link) {
            if (!$link.find('.wpnav-external-icon').length) {
                var iconHtml = '<span class="wpnav-external-icon" style="font-size: 0.8em; margin-left: 3px; opacity: 0.6; vertical-align: super; color: #646970; transition: opacity 0.2s ease;">↗</span>';
                $link.append(iconHtml);
            }
        },

        getRedirectUrl: function(url) {
            var currentPage = encodeURIComponent(window.location.href);
            var baseParams = 'wpnav_redirect=1&ref=' + currentPage;

            switch(wpnav_params.redirect_method) {
                case 'path':
                    var encodedUrl = this.encodeUrl(url);
                    if (wpnav_params.permalink_structure) {
                        return wpnav_params.home_url + '/go/' + encodedUrl;
                    } else {
                        return wpnav_params.home_url + '?' + baseParams + '&url_param=' + encodeURIComponent(encodedUrl);
                    }

                case 'target':
                    return wpnav_params.home_url + '?' + baseParams + '&target=' + encodeURIComponent(url);

                default:
                    var encodedUrl = this.encodeUrl(url);
                    return wpnav_params.home_url + '?' + baseParams + '&url=' + encodeURIComponent(encodedUrl);
            }
        },

        encodeUrl: function(url) {
            if (wpnav_params.redirect_method === 'target') {
                return url;
            }

            switch(wpnav_params.url_encoding) {
                case 'base64':
                    try {
                        return btoa(encodeURIComponent(url)).replace(/[+/=]/g, function(match) {
                            return {'+': '-', '/': '_', '=': ''}[match];
                        });
                    } catch (e) {
                        return encodeURIComponent(url);
                    }

                case 'urlencode':
                    return encodeURIComponent(url);

                case 'none':
                    return url;

                default:
                    try {
                        return btoa(encodeURIComponent(url)).replace(/[+/=]/g, function(match) {
                            return {'+': '-', '/': '_', '=': ''}[match];
                        });
                    } catch (e) {
                        return encodeURIComponent(url);
                    }
            }
        },

        setupMutationObserver: function() {
            if (!window.MutationObserver) {
                return;
            }

            var self = this;
            var observer = new MutationObserver(function(mutations) {
                mutations.forEach(function(mutation) {
                    if (mutation.addedNodes && mutation.addedNodes.length > 0) {
                        mutation.addedNodes.forEach(function(node) {
                            if (node.nodeType === 1) {
                                if (node.nodeName === 'A' && !node.hasAttribute('data-wpnav-processed')) {
                                    self.processLink($(node));
                                }

                                var $links = $(node).find('a:not([data-wpnav-processed])');
                                $links.each(function() {
                                    self.processLink($(this));
                                });
                            }
                        });
                    }
                });
            });

            observer.observe(document.body, {
                childList: true,
                subtree: true
            });
        },

        setupEventHandlers: function() {
            var self = this;

            $(document).ajaxComplete(function() {
                setTimeout(function() {
                    self.processExistingLinks();
                }, 100);
            });

            $(document).on('click', 'a[data-wpnav-external]', function(e) {
                var $link = $(this);
                var href = $link.attr('href');

                if (typeof gtag !== 'undefined') {
                    gtag('event', 'click', {
                        event_category: 'external_link',
                        event_label: href,
                        transport_type: 'beacon'
                    });
                }

                $(document).trigger('wpnav.external_link_click', {
                    link: $link,
                    url: href,
                    target: $link.attr('target')
                });
            });

            $(document).keydown(function(e) {
                if (e.keyCode === 27 && window.parent !== window) {
                    if (typeof window.parent.postMessage === 'function') {
                        window.parent.postMessage('wpnav_close', '*');
                    }
                }
            });

            $(document).on('mouseenter', 'a[data-wpnav-external]', function() {
                $(this).find('.wpnav-external-icon').css('opacity', '1');
            });

            $(document).on('mouseleave', 'a[data-wpnav-external]', function() {
                $(this).find('.wpnav-external-icon').css('opacity', '0.6');
            });
        },

        optimizeForMobile: function() {
            if (!('ontouchstart' in window)) {
                return;
            }

            $(document).on('touchstart', 'a[data-wpnav-external]', function(e) {
                var $this = $(this);
                $this.addClass('wpnav-touch-active');

                setTimeout(function() {
                    $this.removeClass('wpnav-touch-active');
                }, 150);
            });

            var style = document.createElement('style');
            style.textContent = `
                a[data-wpnav-external].wpnav-touch-active {
                    opacity: 0.7;
                    transform: scale(0.98);
                    transition: all 0.15s ease;
                }

                @media (hover: none) and (pointer: coarse) {
                    a[data-wpnav-external]:hover {
                        opacity: 1;
                        transform: none;
                    }

                    .wpnav-external-icon {
                        opacity: 0.8 !important;
                    }
                }
            `;
            document.head.appendChild(style);
        },

        refresh: function() {
            this.processExistingLinks();
        }
    };

    $(document).ready(function() {
        wpnavRedirect.init();
    });

    window.wpnavRedirect = wpnavRedirect;

    if (typeof addComment !== 'undefined') {
        var originalAddComment = addComment.moveForm;
        addComment.moveForm = function() {
            var result = originalAddComment.apply(this, arguments);
            setTimeout(function() {
                wpnavRedirect.processExistingLinks();
            }, 100);
            return result;
        };
    }

    if (typeof wp !== 'undefined' && wp.data) {
        wp.data.subscribe(function() {
            setTimeout(function() {
                wpnavRedirect.processExistingLinks();
            }, 500);
        });
    }

    $(document).on('wpcf7mailsent wpcf7invalid wpcf7spam wpcf7mailfailed', function() {
        setTimeout(function() {
            wpnavRedirect.processExistingLinks();
        }, 100);
    });

    $(document.body).on('updated_wc_div updated_cart_totals', function() {
        setTimeout(function() {
            wpnavRedirect.processExistingLinks();
        }, 100);
    });

    if ('serviceWorker' in navigator) {
        window.addEventListener('load', function() {
            setTimeout(function() {
                wpnavRedirect.processExistingLinks();
            }, 1000);
        });
    }

})(jQuery);
