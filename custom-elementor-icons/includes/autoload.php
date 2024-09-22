<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/* Autoload Classes */
require_once( CEI_CLASSES_PATH . 'Nonces.php' );
require_once( CEI_CLASSES_PATH . 'Template.php' );
require_once( CEI_CLASSES_PATH . 'LoadIcons.php' );

/* WP Admin Autoload */
if ( is_admin() ) {
	require_once( CEI_CLASSES_PATH . 'admin/DashboardController.php' );
	require_once( CEI_CLASSES_PATH . 'admin/AdminMenu.php' );
	require_once( CEI_CLASSES_PATH . 'admin/CustomIcons.php' );
}

/* Autoload Files */
require_once( CEI_INCLUDES_PATH . 'init.php' );