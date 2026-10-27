<?php

namespace Custom_Elementor_Icons\Classes\Admin;

use Custom_Elementor_Icons\Classes\LoadIcons;
use Custom_Elementor_Icons\Classes\Template;
use Custom_Elementor_Icons\Classes\Nonces;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DashboardController {

	/**
	 * Enqueue Admin Styles & Scripts
	 */
	public static function enqueue_styles_scripts() {
		if ( file_exists( CUSTOM_ELEMENTOR_ICONS_PATH . '/assets/css/admin.css' ) ) {
			wp_enqueue_style( 'custom-elementor-icons-css', CUSTOM_ELEMENTOR_ICONS_URL . 'assets/css/admin.css', array(), CUSTOM_ELEMENTOR_ICONS_VERSION );
		}

		wp_enqueue_script( 'media-upload' );
		wp_enqueue_media();

		wp_enqueue_script( 'custom-elementor-icons-js', CUSTOM_ELEMENTOR_ICONS_URL . 'assets/js/admin.js', array( 'jquery' ), CUSTOM_ELEMENTOR_ICONS_VERSION, true );

		$ajax_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonces'   => Nonces::get_nonces(),
			'i18n'     => array(
				'not_found'     => __( "Can't find the icon?", 'custom-elementor-icons' ),
				'found'         => __( 'Icons found.', 'custom-elementor-icons' ),
				'upload_link'   => __( 'Click here to upload', 'custom-elementor-icons' ),
				'upload_title'  => __( 'Upload/Select IcoMoon Font Zip', 'custom-elementor-icons' ),
				'upload_button' => __( 'Insert Fonts Zip File', 'custom-elementor-icons' ),
				'invalid_zip'   => __( 'Please upload a valid ZIP file. You can create the file on icomoon.io', 'custom-elementor-icons' ),
				'added'         => __( 'Icons Font added successfully! Reloading the page...', 'custom-elementor-icons' ),
				'add_failed'    => __( "Couldn't add the Icons Font. Error:", 'custom-elementor-icons' ),
				'confirm_delete' => __( 'Are you sure to delete this Icons Font?', 'custom-elementor-icons' ),
				'remove_failed_retry'  => __( "Couldn't remove the Icons Font. Please reload the page, then try again.", 'custom-elementor-icons' ),
				'removed'       => __( 'Icons Font deleted successfully! Reloading the page...', 'custom-elementor-icons' ),
				'remove_failed_reload' => __( "Couldn't remove the Icons Font. Reloading the page...", 'custom-elementor-icons' ),
			),
		);

		wp_localize_script( 'custom-elementor-icons-js', 'cei_window', $ajax_data );

		$load_icons = new LoadIcons();
		$load_icons->enqueue();
	}

	/**
	 * Display Rendered Template
	 *
	 * @return bool|string
	 */
	public static function render() {
		self::enqueue_styles_scripts();

		return Template::load_template(
			'icon-manager',
			array( 'fonts' => get_option( 'stm_fonts' ) ),
			true
		);
	}

}
