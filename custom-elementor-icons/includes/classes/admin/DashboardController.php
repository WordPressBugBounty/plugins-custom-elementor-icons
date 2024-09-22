<?php

namespace CEI\Classes\Admin;

use CEI\Classes\LoadIcons;
use CEI\Classes\Template;
use CEI\Classes\Nonces;

class DashboardController {

	/**
	 * Enqueue Admin Styles & Scripts
	 */
	public static function enqueue_styles_scripts() {
		if ( file_exists( CEI_PATH . '/assets/css/admin.css' ) ) {
			wp_enqueue_style( 'custom-elementor-icons-css', CEI_URL . 'assets/css/admin.css', array(), CEI_VERSION );
		}

		wp_enqueue_script( 'media-upload' );
		wp_enqueue_media();

		wp_enqueue_script( 'custom-elementor-icons-js', CEI_URL . 'assets/js/admin.js', array(), CEI_VERSION );

		$ajax_data = array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonces'   => Nonces::get_nonces(),
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
