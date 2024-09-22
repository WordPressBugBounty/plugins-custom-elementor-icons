<?php

/**
 * Load Textdomain
 */
if ( ! is_textdomain_loaded( 'custom-elementor-icons' ) ) {
    load_plugin_textdomain( 'custom-elementor-icons', false, 'custom-elementor-icons/languages' );
}

/**
 * Init Admin Classes
 */
if ( is_admin() ) {
	\CEI\Classes\Admin\AdminMenu::init();
	new \CEI\Classes\Admin\CustomIcons();
}

/**
 * Init Classes
 */
new \CEI\Classes\LoadIcons();
