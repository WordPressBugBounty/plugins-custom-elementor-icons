<?php

namespace Custom_Elementor_Icons\Classes;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Safe access to the data of an uploaded icon font.
 *
 * Icons are read from the font's selection.json (IcoMoon export). PHP files stored next to it are never included.
 */
class FontData {

	/**
	 * Font names become folder names, option keys and CSS file names, so only plain slugs are accepted.
	 *
	 * @param mixed $name
	 * @return string The name, or an empty string when it is not safe.
	 */
	public static function sanitize_name( $name ) {
		$name = is_string( $name ) ? $name : '';

		return preg_match( '/^[A-Za-z0-9_-]{1,64}$/', $name ) ? $name : '';
	}

	/**
	 * Turn an IcoMoon selection.json document into a prefix and an icon list.
	 *
	 * @param string $json
	 * @return array|null array( 'prefix' => string, 'icons' => array( name => array( 'class' => string, 'tags' => string ) ) )
	 */
	public static function parse_selection( $json ) {
		$data = json_decode( (string) $json, true );

		if ( ! is_array( $data ) || empty( $data['icons'] ) || ! is_array( $data['icons'] ) ) {
			return null;
		}

		$prefix = '';
		if ( isset( $data['preferences']['fontPref']['prefix'] ) && is_string( $data['preferences']['fontPref']['prefix'] ) ) {
			$prefix = preg_replace( '/[^A-Za-z0-9_-]/', '', $data['preferences']['fontPref']['prefix'] );
		}

		$icons = array();

		foreach ( $data['icons'] as $icon ) {
			if ( ! isset( $icon['properties']['name'] ) || ! is_string( $icon['properties']['name'] ) ) {
				continue;
			}

			$name  = $icon['properties']['name'];
			$class = str_replace( ',', ' ', str_replace( ' ', '', $name ) );
			$class = trim( preg_replace( '/[^A-Za-z0-9_\- ]/', '', $class ) );

			if ( '' === $class ) {
				continue;
			}

			$tags = array();
			if ( isset( $icon['icon']['tags'] ) && is_array( $icon['icon']['tags'] ) ) {
				foreach ( $icon['icon']['tags'] as $tag ) {
					if ( is_string( $tag ) ) {
						$tags[] = sanitize_text_field( str_replace( ',', ' ', $tag ) );
					}
				}
			}

			$icons[ $name ] = array(
				'class' => $class,
				'tags'  => implode( ',', $tags ),
			);
		}

		return array(
			'prefix' => $prefix,
			'icons'  => $icons,
		);
	}

	/**
	 * Absolute folder of a saved font, or an empty string when the stored path looks unsafe.
	 *
	 * @param array $info One entry of the stm_fonts option.
	 * @return string
	 */
	public static function font_dir( $info ) {
		if ( ! is_array( $info ) || empty( $info['include'] ) || ! is_string( $info['include'] ) ) {
			return '';
		}

		$include = str_replace( '\\', '/', $info['include'] );

		if ( in_array( '..', explode( '/', $include ), true ) ) {
			return '';
		}

		if ( ! empty( $info['full_path'] ) ) {
			return untrailingslashit( $include );
		}

		$upload_dir = wp_upload_dir();

		return trailingslashit( $upload_dir['basedir'] ) . $include;
	}

	/**
	 * Read the icons of a saved font from its selection.json.
	 *
	 * @param array $info One entry of the stm_fonts option.
	 * @return array|null See parse_selection().
	 */
	public static function load( $info ) {
		$dir = self::font_dir( $info );

		if ( '' === $dir || empty( $info['json'] ) || ! is_string( $info['json'] ) || basename( $info['json'] ) !== $info['json'] ) {
			return null;
		}

		$file = $dir . '/' . $info['json'];

		if ( ! is_readable( $file ) ) {
			return null;
		}

		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- local file, WP_Filesystem is not initialised on the front end.
		return self::parse_selection( file_get_contents( $file ) );
	}

	/**
	 * PHP source of the legacy charmap.php that themes built on the same stm_fonts convention read.
	 * Every value goes through php_string(), so uploaded data can never become code.
	 *
	 * @param string $font_name
	 * @param array  $icons
	 * @return string
	 */
	public static function charmap_php( $font_name, $icons ) {
		$content = "<?php\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\n\$icons = array();\n";

		foreach ( $icons as $name => $info ) {
			$content .= sprintf(
				"\$icons[%s][%s] = array( 'class' => %s, 'tags' => %s );\n",
				self::php_string( $font_name ),
				self::php_string( $name ),
				self::php_string( $info['class'] ),
				self::php_string( $info['tags'] )
			);
		}

		return $content;
	}

	/**
	 * Single-quoted PHP string literal; inside single quotes only the backslash and the quote are special.
	 *
	 * @param mixed $value
	 * @return string
	 */
	private static function php_string( $value ) {
		return "'" . str_replace( array( '\\', "'" ), array( '\\\\', "\\'" ), (string) $value ) . "'";
	}

}
