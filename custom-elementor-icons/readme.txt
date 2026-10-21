=== Custom Icons for Elementor and WPBakery ===
Contributors: gdprcompliancewp
Donate link: https://www.calculator.io/
Tags: elementor, icon, icons, elementor icon, fontello
Requires at least: 4.6
Requires PHP: 7.1
Tested up to: 7.1
Stable tag: 1.0.7
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Incorporate your own custom icon fonts into Elementor's integrated icon controls.

== Description ==

Allows users to integrate their own custom icons into Elementor's native icon controls and elements. This eliminates the need for FontAwesome, offering enhanced branding possibilities through personalized icon sets on websites. Exclusively utilizes Fontello's complimentary icon font service for this functionality.

## Main Features of the Icons for Elementor plugin:

- Fully compatible with Elementor Page Builder.
- Fully compatible with WPBakery Page Builder.
- Allows the addition of unlimited icons to your website.
- Supports IcoMoon.
- Enables the addition of custom icons anywhere on your website.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate Icons for Elementor through the 'Plugins' screen in WordPress
3. Set Up Page in Menu -> Custom Icons.


== Changelog ==

= 1.0.7 =
* Security: the uploaded icon set is now validated. Only font files with a whitelisted extension are extracted from the ZIP, the font name must be a plain slug, and icon names are no longer written into executable PHP; icons are read from selection.json instead.
* Security: the font upload no longer requests the extracted SVG over HTTP, and the temp folder is no longer made world-writable.
* The menu page requires the manage_options capability; uploads still require update_plugins.
* Internal classes, constants and the upload capability filter were renamed with a full plugin prefix (the filter is now custom_elementor_icons_file_upload_capability; the old avf_file_upload_capability still works).
* Fixed the icon search, which broke because the tag attribute was never printed.

= 1.0.6 =
* Minor bug fix

= 1.0.5 =
* Fixed bug with custom icons

= 1.0.4 =
* Fixed visual bugs in Admin dashboard

= 1.0.3 =
* Added hook to delete icon set button

= 1.0.2 =
* Added Customize Enqueue Scripts.
* Added WPCFTO Enqueue Scripts.

= 1.0.1 =
* Changed "CustomIcons" class Access Modifiers.

= 1.0 =
* First Version of Plugin.
