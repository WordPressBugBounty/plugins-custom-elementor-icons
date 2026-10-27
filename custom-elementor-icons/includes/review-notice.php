<?php
/**
 * "Rate this plugin" admin notice: manage_options users only, on the plugins screen and this plugin's settings,
 * 14 days after it was first seen; dismissible for good or snoozed for 30 days. No external requests, no tracking.
 */

defined( 'ABSPATH' ) || exit;

define( 'CEI_REVIEW_DELAY', 14 * DAY_IN_SECONDS );
define( 'CEI_REVIEW_SNOOZE', 30 * DAY_IN_SECONDS );

/**
 * Remembers when the plugin was first seen; existing installs start counting from the update.
 */
function cei_review_since() {
	$since = (int) get_option( 'cei_review_since', 0 );
	if ( ! $since ) {
		$since = time();
		update_option( 'cei_review_since', $since, false );
	}
	return $since;
}

/**
 * Whether the current user should see the notice on the current screen.
 */
function cei_review_due() {
	if ( ! current_user_can( 'manage_options' ) ) {
		return false;
	}
	$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
	if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'toplevel_page_custom-elementor-icons' ), true ) ) {
		return false;
	}
	$state = get_user_meta( get_current_user_id(), 'cei_review_state', true );
	if ( 'done' === $state ) {
		return false;
	}
	if ( is_numeric( $state ) && time() < (int) $state ) {
		return false;
	}
	return time() >= cei_review_since() + CEI_REVIEW_DELAY;
}

function cei_review_notice() {
	if ( ! cei_review_due() ) {
		return;
	}
	$rate  = 'https://wordpress.org/support/plugin/custom-elementor-icons/reviews/#new-post';
	$later = wp_nonce_url( admin_url( 'admin-post.php?action=cei_review&do=later' ), 'cei_review' );
	$done  = wp_nonce_url( admin_url( 'admin-post.php?action=cei_review&do=done' ), 'cei_review' );
	?>
	<div class="notice notice-info">
		<p>
			<?php
			/* translators: %s: plugin name */
			echo esc_html( sprintf( __( 'Thank you for using %s! If it helps your site, would you rate it on WordPress.org? It takes a minute and helps other people find it.', 'custom-elementor-icons' ), 'Custom Icons for Elementor and WPBakery' ) );
			?>
		</p>
		<p>
			<a class="button button-primary" href="<?php echo esc_url( $rate ); ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Rate it', 'custom-elementor-icons' ); ?></a>
			<a class="button" href="<?php echo esc_url( $later ); ?>"><?php esc_html_e( 'Maybe later', 'custom-elementor-icons' ); ?></a>
			<a class="button-link" href="<?php echo esc_url( $done ); ?>"><?php esc_html_e( 'I already did', 'custom-elementor-icons' ); ?></a>
		</p>
	</div>
	<?php
}
add_action( 'admin_notices', 'cei_review_notice' );

function cei_review_action() {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'Sorry, you are not allowed to do that.', 'custom-elementor-icons' ), 403 );
	}
	check_admin_referer( 'cei_review' );
	$do = isset( $_GET['do'] ) ? sanitize_key( wp_unslash( $_GET['do'] ) ) : '';
	update_user_meta( get_current_user_id(), 'cei_review_state', 'done' === $do ? 'done' : time() + CEI_REVIEW_SNOOZE );
	wp_safe_redirect( wp_get_referer() ? wp_get_referer() : admin_url( 'plugins.php' ) );
	exit;
}
add_action( 'admin_post_cei_review', 'cei_review_action' );

add_action( 'admin_init', 'cei_review_since' );
