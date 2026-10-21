<?php

namespace Custom_Elementor_Icons\Classes\Admin;

use Custom_Elementor_Icons\Classes\FontData;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CustomIcons {

	private const OPTION          = 'stm_fonts';
	private const MAX_FILES       = 300;
	private const MAX_FILE_SIZE   = 5242880;
	private const MAX_TOTAL_SIZE  = 20971520;
	private const ALLOWED_FORMATS = [ 'eot', 'svg', 'ttf', 'woff', 'woff2', 'json', 'css' ];

	private $wp_filesystem;
	private $svg_file;
	private $json_file;
	private $font_name      = '';
	private $icons          = [];
	private $prefix         = '';
	private $custom_fonts   = [];
	public $paths           = [];

	/**
	 * Init CustomIcons Class
	 */
	public function __construct() {
		$this->custom_fonts     = get_option( self::OPTION, [] );
		$this->paths            = wp_upload_dir();
		$this->paths['fonts']   = self::OPTION;
		$this->paths['temp']    = trailingslashit( $this->paths['fonts'] ) . 'stm_temp';
		$this->paths['fontdir'] = trailingslashit( $this->paths['basedir'] ) . $this->paths['fonts'];
		$this->paths['tempdir'] = trailingslashit( $this->paths['basedir'] ) . $this->paths['temp'];
		$this->paths['fonturl'] = set_url_scheme( trailingslashit( $this->paths['baseurl'] ) . $this->paths['fonts'] );
		$this->paths['tempurl'] = trailingslashit( $this->paths['baseurl'] ) . trailingslashit( $this->paths['temp'] );
		$this->paths['config']  = 'charmap.php';
		$this->paths['json']    = 'selection.json';

		add_action( 'wp_ajax_cei_upload_font_archive', [ $this, 'upload_font_archive' ] );
		add_action( 'wp_ajax_cei_remove_font_archive', [ $this, 'remove_font_archive' ] );
		add_action( 'admin_init', [ $this, 'maybe_regenerate_configs' ] );
	}

	/**
	 * Stop an AJAX request with a plain-text message (the admin script matches on the message).
	 *
	 * @param string $message Already safe to print.
	 */
	private function finish( $message ) {
		echo esc_html( $message );
		wp_die();
	}

	/**
	 * Load WP_Filesystem on demand; only the upload and delete handlers need it.
	 */
	private function init_filesystem() {
		global $wp_filesystem;

		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			WP_Filesystem();
		}

		$this->wp_filesystem = $wp_filesystem;

		if ( empty( $this->wp_filesystem ) ) {
			$this->finish( __( 'Failed to work with the file system.', 'custom-elementor-icons' ) );
		}
	}

	/**
	 * Upload new Icons Font
	 */
	public function upload_font_archive() {
		check_ajax_referer( 'cei_upload_font_archive', 'nonce' );

		$capability = apply_filters( 'avf_file_upload_capability', 'update_plugins' ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- pre-1.0.7 filter name, kept for compatibility
		if ( ! current_user_can( apply_filters( 'custom_elementor_icons_file_upload_capability', $capability ) ) ) {
			$this->finish( __( "You unfortunately don't have the necessary permissions.", 'custom-elementor-icons' ) );
		}

		$attachment_id = 0;
		if ( isset( $_POST['values']['id'] ) && is_scalar( $_POST['values']['id'] ) ) {
			$attachment_id = absint( wp_unslash( $_POST['values']['id'] ) );
		}

		$path = $this->get_archive_path( $attachment_id );

		if ( '' === $path ) {
			$this->finish( __( 'Please select a valid ZIP file from the media library.', 'custom-elementor-icons' ) );
		}

		$this->init_filesystem();
		$this->custom_fonts = get_option( self::OPTION, [] );

		if ( $this->unpack_zip( $path, self::ALLOWED_FORMATS ) ) {
			$this->create_new_config();
		}

		if ( '' === $this->font_name ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to get Font name from downloaded folder', 'custom-elementor-icons' ) );
		}

		$this->finish( 'stm_font_added:' . $this->font_name );
	}

	/**
	 * Delete Icons Font
	 */
	public function remove_font_archive() {
		check_ajax_referer( 'cei_remove_font_archive', 'nonce' );

		if ( ! current_user_can( 'manage_options' ) ) {
			$this->finish( __( "You unfortunately don't have the necessary permissions.", 'custom-elementor-icons' ) );
		}

		$font = '';
		if ( isset( $_POST['delete_font'] ) && is_string( $_POST['delete_font'] ) ) {
			$font = sanitize_text_field( wp_unslash( $_POST['delete_font'] ) );
		}

		$this->init_filesystem();
		$this->custom_fonts = get_option( self::OPTION, [] );
		$list = $this->get_fonts_list();

		if ( '' !== $font && isset( $list[ $font ] ) ) {
			$this->delete_directory( $list[ $font ]['include'] );
			$this->delete_font( $font );
			$this->finish( 'stm_font_removed' );
		}

		$this->finish( __( 'Failed to remove the Font', 'custom-elementor-icons' ) );
	}

	/**
	 * Absolute path of the selected ZIP attachment, or an empty string when it is not an acceptable archive.
	 *
	 * @param int $attachment_id
	 * @return string
	 */
	private function get_archive_path( $attachment_id ) {
		if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) || ! current_user_can( 'edit_post', $attachment_id ) ) {
			return '';
		}

		$file = get_attached_file( $attachment_id );
		$path = $file ? realpath( $file ) : false;
		$base = realpath( $this->paths['basedir'] );

		if ( ! $path || ! $base || 0 !== strpos( $path, trailingslashit( $base ) ) || ! is_file( $path ) ) {
			return '';
		}

		if ( 'zip' !== strtolower( pathinfo( $path, PATHINFO_EXTENSION ) ) ) {
			return '';
		}

		return $path;
	}

	/**
	 * Extract the whitelisted font files of the zip into a flat temp folder.
	 *
	 * Entry names are reduced to a sanitized base name and must end in one of the allowed extensions, so nothing
	 * outside the temp folder is written and nothing executable can be created (sanitize_file_name() neutralises
	 * inner extensions such as "x.php.svg").
	 *
	 * @param string $archive
	 * @param array  $allowed Allowed lower-case file extensions.
	 * @return bool
	 */
	protected function unpack_zip( $archive, $allowed ) {
		if ( is_dir( $this->paths['tempdir'] ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
		}

		if ( ! $this->create_directory( $this->paths['tempdir'] ) ) {
			$this->finish( __( 'Failed to create temp folder', 'custom-elementor-icons' ) );
		}

		$zip = new \ZipArchive();

		if ( true !== $zip->open( $archive ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to work with Zip Archive', 'custom-elementor-icons' ) );
		}

		if ( $zip->numFiles > self::MAX_FILES ) {
			$zip->close();
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'The archive contains too many files.', 'custom-elementor-icons' ) );
		}

		$total = 0;

		for ( $i = 0; $i < $zip->numFiles; $i ++ ) {
			$stat  = $zip->statIndex( $i );
			$entry = is_array( $stat ) ? str_replace( '\\', '/', (string) $stat['name'] ) : '';

			if ( '' === $entry || '/' === substr( $entry, -1 ) || 0 === strpos( $entry, '__MACOSX/' ) ) {
				continue;
			}

			if ( in_array( '..', explode( '/', $entry ), true ) ) {
				continue;
			}

			$name = sanitize_file_name( basename( $entry ) );
			$ext  = strtolower( pathinfo( $name, PATHINFO_EXTENSION ) );

			if ( '' === $name || '.' === $name[0] || ! in_array( $ext, $allowed, true ) ) {
				continue;
			}

			if ( $stat['size'] > self::MAX_FILE_SIZE ) {
				$zip->close();
				$this->delete_directory( $this->paths['tempdir'] );
				$this->finish( __( 'A file in the archive is too large.', 'custom-elementor-icons' ) );
			}

			$contents = $zip->getFromIndex( $i, self::MAX_FILE_SIZE + 1 );

			if ( false === $contents ) {
				$zip->close();
				$this->delete_directory( $this->paths['tempdir'] );
				$this->finish( __( 'Unable to extract the file.', 'custom-elementor-icons' ) );
			}

			$total += strlen( $contents );

			if ( strlen( $contents ) > self::MAX_FILE_SIZE || $total > self::MAX_TOTAL_SIZE ) {
				$zip->close();
				$this->delete_directory( $this->paths['tempdir'] );
				$this->finish( __( 'The archive is too large.', 'custom-elementor-icons' ) );
			}

			if ( 'svg' === $ext && preg_match( '/<script|\son[a-z]+\s*=|javascript:/i', $contents ) ) {
				$zip->close();
				$this->delete_directory( $this->paths['tempdir'] );
				$this->finish( __( 'The SVG font contains active content and was rejected.', 'custom-elementor-icons' ) );
			}

			$this->wp_filesystem->put_contents( trailingslashit( $this->paths['tempdir'] ) . $name, $contents, FS_CHMOD_FILE );
		}

		$zip->close();

		return true;
	}

	/**
	 * Read the font name and the icon list from the extracted files.
	 *
	 * @return bool
	 */
	protected function create_new_config() {
		$this->json_file = $this->find_config( 'json' );
		$this->svg_file  = $this->find_config( 'svg' );

		if ( empty( $this->json_file ) || empty( $this->svg_file ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to create the necessary config files', 'custom-elementor-icons' ) );
		}

		$tempdir = trailingslashit( $this->paths['tempdir'] );
		$json    = $this->wp_filesystem->get_contents( $tempdir . $this->json_file );

		if ( empty( $json ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to create the necessary config files', 'custom-elementor-icons' ) );
		}

		// The archive may hold several SVG files; the font is the one that declares a <font id="...">.
		$this->font_name = '';
		foreach ( $this->find_configs( 'svg' ) as $file ) {
			$this->font_name = FontData::sanitize_name( $this->read_font_id( $this->wp_filesystem->get_contents( $tempdir . $file ) ) );

			if ( '' !== $this->font_name ) {
				$this->svg_file = $file;
				break;
			}
		}

		if ( '' === $this->font_name ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to get Font name from downloaded folder', 'custom-elementor-icons' ) );
		}

		if ( is_dir( trailingslashit( $this->paths['fontdir'] ) . $this->font_name ) || isset( $this->custom_fonts[ $this->font_name ] ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Seems Font Icon with the same name is already exist! Please upload the Font Icon with different name.', 'custom-elementor-icons' ) );
		}

		$file_contents = json_decode( $json );

		if ( ! is_object( $file_contents ) || ! isset( $file_contents->IcoMoonType ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Font Icon is not from Icomoon. Please upload fonts created with the Icomoon.', 'custom-elementor-icons' ) );
		}

		$parsed = FontData::parse_selection( $json );

		if ( empty( $parsed['icons'] ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to create the necessary config files', 'custom-elementor-icons' ) );
		}

		$this->icons  = $parsed['icons'];
		$this->prefix = $parsed['prefix'];

		$this->init_config();

		return true;
	}

	/**
	 * Init Created Config
	 */
	protected function init_config() {
		if ( $this->json_file !== $this->paths['json'] ) {
			$this->wp_filesystem->move(
				trailingslashit( $this->paths['tempdir'] ) . $this->json_file,
				trailingslashit( $this->paths['tempdir'] ) . $this->paths['json'],
				true
			);
		}

		$this->add_config_file();
		$this->rewrite_css();
		$this->move_files();
		$this->rename_directory();
		$this->save_font();
	}

	/**
	 * Write the charmap files of the font: charmap.php (read by themes using the stm_fonts convention) and
	 * charmap.json (read by Elementor).
	 */
	protected function add_config_file() {
		if ( ! $this->write_charmaps( $this->paths['tempdir'], $this->font_name, $this->prefix, $this->icons ) ) {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to write Config file.', 'custom-elementor-icons' ) );
		}
	}

	/**
	 * @param string $dir
	 * @param string $font_name
	 * @param string $prefix
	 * @param array  $icons
	 * @return bool
	 */
	private function write_charmaps( $dir, $font_name, $prefix, $icons ) {
		$names = [];

		foreach ( $icons as $info ) {
			$names[] = $prefix . $info['class'];
		}

		return $this->wp_filesystem->put_contents( trailingslashit( $dir ) . $this->paths['config'], FontData::charmap_php( $font_name, $icons ), FS_CHMOD_FILE )
			&& $this->wp_filesystem->put_contents( trailingslashit( $dir ) . 'charmap.json', wp_json_encode( [ 'icons' => $names ] ), FS_CHMOD_FILE );
	}

	/**
	 * Fonts saved by earlier versions got a charmap.php generated from unescaped upload data. Rewrite it from
	 * selection.json once, so no such file stays in the uploads folder.
	 */
	public function maybe_regenerate_configs() {
		if ( get_option( 'cei_configs_version' ) === '1.0.7' || ! current_user_can( 'manage_options' ) ) {
			return;
		}

		// Unlike the upload handlers this runs on any admin request, so it must never wp_die(): without direct file
		// access (FTP/SSH credentials) it waits for a later request instead of marking itself done.
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
			if ( 'direct' !== get_filesystem_method() || ! WP_Filesystem() ) {
				return;
			}
		}
		$this->wp_filesystem = $wp_filesystem;
		$this->custom_fonts  = get_option( self::OPTION, [] );

		if ( is_array( $this->custom_fonts ) ) {
			foreach ( $this->custom_fonts as $font => $info ) {
				$dir    = FontData::font_dir( $info );
				$parsed = FontData::load( $info );

				if ( '' === $dir || ! $parsed || ! is_dir( $dir ) || ! FontData::sanitize_name( $font ) ) {
					continue;
				}

				$this->write_charmaps( $dir, $font, $parsed['prefix'], $parsed['icons'] );
			}
		}

		update_option( 'cei_configs_version', '1.0.7', false );
	}

	/**
	 * Tidy the stylesheet that ships with the font.
	 */
	protected function rewrite_css() {
		$style = trailingslashit( $this->paths['tempdir'] ) . 'style.css';
		$file  = $this->wp_filesystem->get_contents( $style );

		if ( $file ) {
			$str = str_replace( 'fonts/', '', $file );
			$str = preg_replace( '!/\*[^*]*\*+([^/][^*]*\*+)*/!', '', $str );
			$str = str_replace( array( "\r\n", "\r", "\n", "\t", '  ', '    ', '    ' ), '', $str );

			$this->wp_filesystem->put_contents( $style, $str, FS_CHMOD_FILE );
		} else {
			$this->delete_directory( $this->paths['tempdir'] );
			$this->finish( __( 'Failed to write CSS. Please upload icons downloaded only from Icomoon', 'custom-elementor-icons' ) );
		}
	}

	/**
	 * Get Icon-Fonts List
	 * @return array|mixed
	 */
	protected function get_fonts_list() {
		$font_configs = is_array( $this->custom_fonts ) ? $this->custom_fonts : [];
		$url          = trailingslashit( $this->paths['baseurl'] );

		foreach ( $font_configs as $key => $config ) {
			$dir = FontData::font_dir( $config );

			if ( '' === $dir ) {
				unset( $font_configs[ $key ] );
				continue;
			}

			if ( empty( $config['full_path'] ) ) {
				$font_configs[ $key ]['include'] = $dir;
				$font_configs[ $key ]['folder']  = $url . $config['folder'];
			}
		}

		return $font_configs;
	}

	/**
	 * Add Icons Font
	 */
	protected function save_font() {
		$this->custom_fonts[ $this->font_name ] = array(
			'include'   => trailingslashit( $this->paths['fonts'] ) . $this->font_name,
			'folder'    => trailingslashit( $this->paths['fonts'] ) . $this->font_name,
			'style'     => $this->font_name . '/' . $this->font_name . '.css',
			'config'    => $this->paths['config'],
			'json'      => $this->paths['json']
		);
		update_option( self::OPTION, $this->custom_fonts );
	}

	/**
	 * Remove Icons Font
	 * @param $font
	 */
	protected function delete_font( $font ) {
		if ( isset( $this->custom_fonts[ $font ] ) ) {
			unset( $this->custom_fonts[ $font ] );
			update_option( self::OPTION, $this->custom_fonts );
		}
	}

	/**
	 * Create Folder
	 *
	 * @param $folder
	 * @return bool
	 */
	public function create_directory( &$folder ) {
		if ( is_dir( $folder ) ) {
			return true;
		}

		return wp_mkdir_p( trailingslashit( $folder ) );
	}

	/**
	 * Delete Folder. Only folders inside the uploads/stm_fonts directory are ever removed.
	 *
	 * @param $folder
	 */
	protected function delete_directory( $folder ) {
		$real = is_string( $folder ) ? realpath( $folder ) : false;
		$root = realpath( $this->paths['fontdir'] );

		if ( ! $real || ! $root || 0 !== strpos( $real, trailingslashit( $root ) ) ) {
			return;
		}

		$this->wp_filesystem->delete( $real, true );
	}

	/**
	 * Move the temp folder to the final font folder
	 *
	 * @return bool
	 */
	protected function rename_directory() {
		$new_name = trailingslashit( $this->paths['fontdir'] ) . $this->font_name;

		if ( $this->wp_filesystem->move( $this->paths['tempdir'], $new_name, false ) ) {
			return true;
		}

		$this->delete_directory( $this->paths['tempdir'] );
		$this->finish( __( 'Failed to add this font. Please try again!', 'custom-elementor-icons' ) );
	}

	/**
	 * Rename Icon Font Files to the font name
	 */
	protected function move_files() {
		foreach ( glob( trailingslashit( $this->paths['tempdir'] ) . '*' ) as $file ) {
			$path_parts = pathinfo( $file );
			if ( strpos( $path_parts['filename'], '.dev' ) === false
			     && isset( $path_parts['extension'] )
			     && in_array( $path_parts['extension'], [ 'eot', 'svg', 'ttf', 'woff', 'woff2', 'css' ], true )
			) {
				if ( $path_parts['filename'] !== $this->font_name ) {
					$this->wp_filesystem->move( $file, trailingslashit( $path_parts['dirname'] ) . $this->font_name . '.' . $path_parts['extension'], true );
				}
			}
		}
	}

	/**
	 * Id of the first <font> element of an SVG font, or an empty string.
	 *
	 * @param string $svg
	 * @return string
	 */
	private function read_font_id( $svg ) {
		if ( empty( $svg ) ) {
			return '';
		}

		$previous = libxml_use_internal_errors( true );
		$xml      = simplexml_load_string( $svg, 'SimpleXMLElement', LIBXML_NONET );
		libxml_clear_errors();
		libxml_use_internal_errors( $previous );

		if ( ! $xml || ! isset( $xml->defs->font ) ) {
			return '';
		}

		$attributes = $xml->defs->font->attributes();

		return isset( $attributes['id'] ) ? (string) $attributes['id'] : '';
	}

	/**
	 * All extracted files with the given extension.
	 *
	 * @param string $ext
	 * @return array
	 */
	protected function find_configs( $ext ) {
		$found = [];

		foreach ( scandir( $this->paths['tempdir'] ) as $file ) {
			if ( '.' !== $file[0] && strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) === $ext ) {
				$found[] = $file;
			}
		}

		return $found;
	}

	/**
	 * Find SVG|JSON config files
	 * @param $ext
	 * @return mixed
	 */
	protected function find_config( $ext ) {
		foreach ( scandir( $this->paths['tempdir'] ) as $file ) {
			if ( '.' !== $file[0] && strtolower( pathinfo( $file, PATHINFO_EXTENSION ) ) === $ext ) {
				return $file;
			}
		}

		return '';
	}

}
