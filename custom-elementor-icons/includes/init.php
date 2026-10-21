<?php
if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Init Admin Classes
 */
if ( is_admin() ) {
	\Custom_Elementor_Icons\Classes\Admin\AdminMenu::init();
	new \Custom_Elementor_Icons\Classes\Admin\CustomIcons();
}

/**
 * Init Classes
 */
new \Custom_Elementor_Icons\Classes\LoadIcons();
