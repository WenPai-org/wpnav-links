=== WPNav Links ===
Contributors: wpnavlinks
Tags: external links, redirect, security, links, SEO
Requires at least: 5.0
Tested up to: 6.4
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

A comprehensive WordPress external link redirect tool with customizable redirect pages, security features, and detailed analytics.

== Description ==

WPNav Links is a powerful WordPress plugin that helps you manage external links on your website by redirecting them through a customizable warning page. This provides better user experience, improved security, and detailed analytics about external link usage.

= Key Features =

* **Customizable Redirect Pages** - Choose from 4 different templates (Simple, Minimal, Default, Full) with 3 color schemes
* **Smart Content Processing** - Automatically processes external links in posts, pages, comments, and widgets
* **Domain Whitelist Management** - Easily manage domains that should not be redirected
* **Detailed Analytics** - Track external link clicks with comprehensive statistics
* **Security Features** - HTTPS detection, security warnings, and safety tips
* **User Experience Options** - Auto-redirect timers, progress bars, and "don't show again" options
* **Accessibility Ready** - Proper ARIA labels, keyboard navigation, and screen reader support
* **Mobile Optimized** - Touch gestures and responsive design for mobile devices
* **SEO Friendly** - Proper nofollow, noopener, and noreferrer attributes
* **Import/Export** - Bulk import/export domain whitelists via CSV

= Template Options =

1. **Simple** - Ultra-minimal design with just the essentials
2. **Minimal** - Clean and lightweight design
3. **Default** - Balanced information display
4. **Full** - Comprehensive with detailed security information

= Color Schemes =

* Blue - Professional appearance
* Green - Safe and trustworthy feel
* Red - Warning and caution emphasis

= Advanced Features =

* **Auto Whitelist Rules** - Automatically whitelist same-root domains and search engines
* **Flexible URL Formats** - Query, Path, or Target parameter formats
* **Content Filtering** - Choose which content areas to process (posts, comments, widgets)
* **Admin Dashboard Widget** - Quick overview of link activity and statistics
* **Custom CSS Support** - Add your own styling to redirect pages
* **Cookie Management** - Remember user preferences for specified durations
* **Data Cleanup** - Automatic old data cleanup with configurable retention periods

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/wpnav-links` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Use the Tools → External Links screen to configure the plugin.
4. Customize your redirect page design and add domains to your whitelist.

== Frequently Asked Questions ==

= How does the plugin work? =

The plugin automatically detects external links in your content and redirects them through a customizable warning page. Users see information about the destination before being redirected.

= Can I customize the redirect page? =

Yes! You can choose from 4 different templates, 3 color schemes, customize text content, and even add custom CSS.

= Which domains should I whitelist? =

Common domains to whitelist include social media platforms, search engines, and trusted partner sites. The plugin can automatically whitelist same-root domains and major search engines.

= Does the plugin affect SEO? =

The plugin actually helps SEO by adding proper nofollow, noopener, and noreferrer attributes to external links, which is recommended by search engines.

= Can I see statistics about external link usage? =

Yes! The plugin provides detailed analytics including top clicked links, daily/monthly statistics, HTTPS vs HTTP breakdown, and more.

= Is the plugin accessible? =

Yes, the plugin is built with accessibility in mind, including proper ARIA labels, keyboard navigation, and screen reader support.

= Can I export my whitelist? =

Yes, you can export your domain whitelist as a CSV file and also import domains from CSV files for bulk management.

== Screenshots ==

1. **Admin Dashboard** - Main settings interface with tabbed navigation
2. **Redirect Page Templates** - Four different template styles to choose from
3. **Live Preview** - Real-time preview of your redirect page design
4. **Whitelist Management** - Easy domain whitelist management with quick-add buttons
5. **Statistics Dashboard** - Comprehensive analytics and reporting
6. **Mobile Experience** - Responsive design works perfectly on mobile devices

== Changelog ==

= 2.0.0 =
* Major user experience improvements
* Added warning message show/hide control
* Fixed back button functionality with multiple fallback strategies
* Implemented complete internationalization support
* Enhanced mobile touch gestures and keyboard navigation
* Improved accessibility with better ARIA labels and screen reader support
* Added draft saving for textarea fields
* Enhanced preview functionality with real-time updates
* Better error handling and user feedback
* Optimized database queries and performance improvements

= 1.9.0 =
* Added 4 redirect page templates (Simple, Minimal, Default, Full)
* Introduced 3 color schemes (Blue, Green, Red)
* Enhanced security features with HTTPS detection
* Added progress bar option for countdown timer
* Improved mobile responsiveness and touch support
* Added copy-to-clipboard functionality
* Enhanced statistics dashboard with more metrics

= 1.8.0 =
* Added comprehensive analytics and statistics
* Introduced admin dashboard widget
* Added CSV import/export functionality
* Enhanced whitelist management with quick-add buttons
* Improved auto-redirect functionality with countdown timer
* Added custom CSS support for redirect pages

= 1.7.0 =
* Added domain whitelist functionality
* Introduced auto-whitelist rules for same-root domains and search engines
* Enhanced content processing with better DOM handling
* Added cookie management for user preferences
* Improved admin interface with tabbed navigation

= 1.6.0 =
* Added multiple URL format options (Query, Path, Target)
* Enhanced security with script injection detection
* Improved content filtering options
* Added admin exemption feature
* Better error handling and validation

= 1.5.0 =
* Initial stable release
* Basic external link redirection
* Simple redirect page
* Admin configuration panel

== Upgrade Notice ==

= 2.0.0 =
Major update with enhanced user experience, internationalization support, and improved functionality. Backup your settings before upgrading.

== Support ==

For support, please visit our [support forum](https://example.com/support) or [documentation](https://example.com/docs).

== Contributing ==

We welcome contributions! Please visit our [GitHub repository](https://github.com/example/wpnav-links) to report issues or submit pull requests.

== Privacy Policy ==

This plugin collects basic analytics data about external link clicks including:
- Target URL
- Source page
- Timestamp
- IP address (anonymized)
- User agent string

This data is stored locally on your server and is used solely for analytics purposes. No data is transmitted to external services.

== Credits ==

Developed by the WPNav Links team with contributions from the WordPress community.