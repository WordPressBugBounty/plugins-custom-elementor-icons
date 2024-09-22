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
 * Version: 1.0.6
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'CEI_VERSION', '1.0.6' );
define( 'CEI_FILE', __FILE__ );
define( 'CEI_PATH', dirname( CEI_FILE ) );
define( 'CEI_INCLUDES_PATH', CEI_PATH . '/includes/' );
define( 'CEI_CLASSES_PATH', CEI_INCLUDES_PATH . 'classes/' );
define( 'CEI_URL', plugin_dir_url( CEI_FILE ) );

require_once( CEI_INCLUDES_PATH . '/autoload.php' );
