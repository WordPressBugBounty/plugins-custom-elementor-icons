<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* Autoload Classes */
require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'Nonces.php' );
require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'Template.php' );
require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'FontData.php' );
require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'LoadIcons.php' );
require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'Block.php' );
require_once( CUSTOM_ELEMENTOR_ICONS_INCLUDES_PATH . 'review-notice.php' );

/* WP Admin Autoload */
if ( is_admin() ) {
	require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'admin/DashboardController.php' );
	require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'admin/AdminMenu.php' );
	require_once( CUSTOM_ELEMENTOR_ICONS_CLASSES_PATH . 'admin/CustomIcons.php' );
}

/* Autoload Files */
require_once( CUSTOM_ELEMENTOR_ICONS_INCLUDES_PATH . 'init.php' );