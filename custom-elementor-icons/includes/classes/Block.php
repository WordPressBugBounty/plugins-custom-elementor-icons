<?php

namespace Custom_Elementor_Icons\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * "Custom Icon" Gutenberg block: one icon from the uploaded icon fonts, with size, color and alignment.
 */
class Block {

	const NAME   = 'custom-elementor-icons/custom-icon';
	const SCRIPT = 'custom-elementor-icons-custom-icon-editor-script';

	/**
	 * Hook the block into WordPress.
	 */
	public static function init() {
		add_action( 'init', array( self::class, 'register' ) );
		add_action( 'enqueue_block_assets', array( self::class, 'enqueue_fonts' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_editor_data' ) );
	}

	/**
	 * Register the dynamic block (WordPress 5.0+).
	 */
	public static function register() {
		if ( ! function_exists( 'register_block_type' ) ) {
			return;
		}

		register_block_type(
			CUSTOM_ELEMENTOR_ICONS_PATH . '/blocks/custom-icon',
			array(
				'render_callback' => array( self::class, 'render' ),
			)
		);

		wp_set_script_translations( self::SCRIPT, 'custom-elementor-icons' );
	}

	/**
	 * Font CSS of every uploaded set, on the front end and in the editor iframe. The handles are the ones
	 * LoadIcons uses, so a style is never printed twice.
	 */
	public static function enqueue_fonts() {
		$fonts = get_option( 'stm_fonts' );

		if ( ! is_array( $fonts ) ) {
			return;
		}

		$upload_dir = wp_upload_dir();

		foreach ( $fonts as $font => $info ) {
			if ( ! is_array( $info ) || empty( $info['style'] ) || ! is_string( $info['style'] ) || '' === FontData::sanitize_name( $font ) ) {
				continue;
			}

			if ( false !== strpos( $info['style'], 'http://' ) ) {
				wp_enqueue_style( 'stm-' . $font, $info['style'], null, '1.0', 'all' );
			} else {
				wp_enqueue_style( 'stm-' . $font, trailingslashit( $upload_dir['baseurl'] . '/stm_fonts/' ) . $info['style'], null, '1.0', 'all' );
			}
		}
	}

	/**
	 * Icon list for the editor sidebar: array( array( 'name' =>, 'prefix' =>, 'icons' => array( array( 'class' =>, 'tags' => ) ) ) ).
	 *
	 * @return array
	 */
	public static function fonts_for_editor() {
		$fonts = get_option( 'stm_fonts' );
		$out   = array();

		if ( ! is_array( $fonts ) ) {
			return $out;
		}

		foreach ( $fonts as $name => $info ) {
			if ( '' === FontData::sanitize_name( $name ) ) {
				continue;
			}

			$data = FontData::load( $info );

			if ( empty( $data['icons'] ) ) {
				continue;
			}

			$icons = array();
			foreach ( $data['icons'] as $icon ) {
				$icons[] = array(
					'class' => $icon['class'],
					'tags'  => $icon['tags'],
				);
			}

			$out[] = array(
				'name'   => (string) $name,
				'prefix' => $data['prefix'],
				'icons'  => $icons,
			);
		}

		return $out;
	}

	/**
	 * Pass the uploaded icons to the editor script.
	 */
	public static function enqueue_editor_data() {
		self::enqueue_fonts();

		$data = array(
			'fonts'    => self::fonts_for_editor(),
			'adminUrl' => current_user_can( 'manage_options' ) ? admin_url( 'admin.php?page=custom-elementor-icons' ) : '',
		);

		wp_add_inline_script( self::SCRIPT, 'window.ceiCustomIconData = ' . wp_json_encode( $data ) . ';', 'before' );
	}

	/**
	 * Server-side render: the markup is the same in the editor preview and on the front end.
	 *
	 * @param array $attributes Block attributes: font, icon (class without prefix), size, alignment, label.
	 * @return string
	 */
	public static function render( $attributes ) {
		$attributes = is_array( $attributes ) ? $attributes : array();
		$font       = isset( $attributes['font'] ) && is_string( $attributes['font'] ) ? FontData::sanitize_name( $attributes['font'] ) : '';
		$icon       = isset( $attributes['icon'] ) && is_string( $attributes['icon'] ) ? $attributes['icon'] : '';
		$fonts      = get_option( 'stm_fonts' );

		if ( '' === $font || '' === $icon || ! is_array( $fonts ) || empty( $fonts[ $font ] ) ) {
			return '';
		}

		$data = FontData::load( $fonts[ $font ] );

		if ( empty( $data['icons'] ) ) {
			return '';
		}

		// Only classes that exist in the uploaded font are printed.
		$found = false;
		foreach ( $data['icons'] as $item ) {
			if ( $item['class'] === $icon ) {
				$found = true;
				break;
			}
		}

		if ( ! $found ) {
			return '';
		}

		self::enqueue_fonts();

		$size = isset( $attributes['size'] ) ? (int) $attributes['size'] : 32;
		$size = max( 8, min( 400, $size ?: 32 ) );

		$alignment = isset( $attributes['alignment'] ) ? $attributes['alignment'] : 'left';
		$alignment = in_array( $alignment, array( 'left', 'center', 'right' ), true ) ? $alignment : 'left';

		$label = isset( $attributes['label'] ) && is_string( $attributes['label'] ) ? trim( $attributes['label'] ) : '';
		$aria  = '' !== $label ? ' role="img" aria-label="' . esc_attr( $label ) . '"' : ' aria-hidden="true"';

		$wrapper = get_block_wrapper_attributes(
			array(
				'class' => 'has-text-align-' . $alignment,
				'style' => 'text-align:' . $alignment . ';',
			)
		);

		return sprintf(
			'<div %1$s><i class="%2$s" style="font-size:%3$dpx;line-height:1;"%4$s></i></div>',
			$wrapper,
			esc_attr( $data['prefix'] . $icon ),
			$size,
			$aria
		);
	}
}
