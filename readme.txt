=== Clarity Consent Auto ===
Contributors: gioxx
Tags: clarity, microsoft, gdpr, privacy, tracking
Donate link: https://ko-fi.com/gioxx
Requires at least: 6.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 2.0.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Automatic consent layer for Microsoft Clarity - grants consent automatically for internal websites.

== Description ==
Clarity Consent Auto is a lightweight consent layer that works on top of the official Microsoft Clarity plugin. It automatically applies consent settings to Microsoft Clarity without interfering with the original plugin configuration.
The plugin is designed and developed only and exclusively for protected, corporate websites, cases where acceptance of cookies is not strictly necessary and against European regulation. The responsibility in case of misuse with respect to the European regulation, lies with the end administrator (who chooses to install and use this plugin).

**Key Features:**
* Automatic consent management for Microsoft Clarity
* Works as a layer on top of the official Microsoft Clarity plugin
* Configurable Ad Storage and Analytics Storage consent settings
* Automatic Project ID detection from existing Clarity installations
* Multi-language support (English and Italian included)
* Zero interference with Microsoft Clarity plugin functionality
* Safe mode operation - read-only detection of existing configurations

**Perfect for:**
* Internal company websites where consent is always granted
* Development and testing environments
* Websites where manual consent management is not required
* GDPR-compliant setups with pre-approved consent policies

**How it works:**
1. Install and activate the official Microsoft Clarity plugin
2. Configure Microsoft Clarity with your Project ID
3. Install Clarity Consent Auto
4. Configure your consent preferences (Grant/Deny for Ad Storage and Analytics Storage)
5. The plugin automatically applies consent to Clarity on page load

**Important:** This plugin requires the official Microsoft Clarity plugin to be installed and active. It acts as a consent layer and does not replace the Microsoft Clarity functionality.

== Installation ==
1. Install and activate the official **Microsoft Clarity** plugin from the WordPress repository
2. Configure Microsoft Clarity with your Project ID
3. Download and install **Clarity Consent Auto**
4. Activate the plugin
5. Go to Settings > Clarity Consent to configure your consent preferences
6. The consent will be automatically applied to all Clarity tracking

**Manual Installation:**
1. Upload the plugin files to the `/wp-content/plugins/clarity-consent-auto/` directory
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Use the Settings > Clarity Consent screen to configure the plugin

== Frequently Asked Questions ==

= Does this plugin replace Microsoft Clarity? =
No. This plugin works as a consent layer on top of the official Microsoft Clarity plugin. You need both plugins installed and active.

= Will this interfere with my existing Clarity configuration? =
No. The plugin operates in read-only mode and never modifies your Microsoft Clarity settings. It only adds consent information.

= What happens if I change my Project ID in Microsoft Clarity? =
The plugin automatically detects Project ID changes and adapts accordingly. No manual configuration needed.

= Can I use this on production websites? =
Yes, but ensure you comply with your local privacy laws. This plugin is designed for scenarios where consent is pre-approved or not required.

= Is it GDPR compliant? =
The plugin provides technical consent management, but GDPR compliance depends on your specific use case and legal requirements. Consult with legal experts for your specific situation.

= Which languages are supported? =
Currently English and Italian are included. The plugin uses WordPress translation system, so additional languages can be easily added.

== Screenshots ==
1. Main plugin configuration page showing Microsoft Clarity status and consent settings
2. Setup instructions when Microsoft Clarity plugin is not active
3. Consent configuration options - Ad Storage and Analytics Storage settings
4. Status information showing detected Project ID and consent layer status

== Changelog ==

= 2.0.1 =
* Initial release in WordPress plugin directory
* Automatic Project ID detection from Microsoft Clarity plugin
* Configurable Ad Storage and Analytics Storage consent
* Multi-language support (English/Italian)
* Safe mode operation with read-only detection
* Comprehensive status reporting and setup guidance
* Debug information panel for troubleshooting

== Upgrade Notice ==

= 2.0.1 =
Initial release. Install the Microsoft Clarity plugin first, then configure Clarity Consent Auto for automatic consent management.

== Developer Notes ==

**Plugin Architecture:**
* Operates as a consent layer without modifying external configurations
* Uses WordPress localization system for translations
* Implements safe detection methods to avoid plugin conflicts
* Provides comprehensive debug information for troubleshooting

**Support:**
For support, feature requests, or bug reports, please visit the plugin's GitHub repository or contact the developer through the WordPress support forums.
