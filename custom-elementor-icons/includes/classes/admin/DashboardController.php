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
				'upload_title'  => __( 'Upload/Select Fontello Font Zip', 'custom-elementor-icons' ),
				'upload_button' => __( 'Insert Fonts Zip File', 'custom-elementor-icons' ),
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
