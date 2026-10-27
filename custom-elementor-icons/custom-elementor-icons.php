<?php
/**
 * Plugin Name: Custom Icons for Elementor and WPBakery
 * Plugin URI: https://wordpress.org/plugins/custom-elementor-icons/
 * Description: Custom Icons for Elementor and WPBakery is a free WordPress plugin that enables users to upload an unlimited number of custom icons to their websites.
 * Author: gdprcompliancewp
 * Author URI: https://stylemixthemes.com/
 * License: GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: custom-elementor-icons
 * Version: 1.1.0
 * Requires PHP: 7.1
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CUSTOM_ELEMENTOR_ICONS_VERSION', '1.1.0' );
define( 'CUSTOM_ELEMENTOR_ICONS_FILE', __FILE__ );
define( 'CUSTOM_ELEMENTOR_ICONS_PATH', dirname( CUSTOM_ELEMENTOR_ICONS_FILE ) );
define( 'CUSTOM_ELEMENTOR_ICONS_INCLUDES_PATH', CUSTOM_ELEMENTOR_ICONS_PATH . '/includes/' );
define( 'CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH', CUSTOM_ELEMENTOR_ICONS_INCLUDES_PATH . 'classes/' );
define( 'CUSTOM_ELEMENTOR_ICONS_URL', plugin_dir_url( CUSTOM_ELEMENTOR_ICONS_FILE ) );

require_once( CUSTOM_ELEMENTOR_ICONS_INCLUDES_PATH . '/autoload.php' );
