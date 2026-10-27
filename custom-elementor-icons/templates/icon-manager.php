<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="wrap">
	<h2>
		<?php esc_html_e( 'Custom Icons Manager', 'custom-elementor-icons' ); ?>
		<a href="#stm_upload_icon" class="add-new-h2 stm_upload_icon" data-target="iconfont_upload"
		   data-title="<?php echo esc_attr__( 'Upload/Select IcoMoon Font Zip', 'custom-elementor-icons' ); ?>"
		   data-type="application/octet-stream, application/zip"
		   data-button="<?php echo esc_attr__( 'Insert Fonts Zip File', 'custom-elementor-icons' ); ?>"
		   data-trigger="cei_upload_font"
		   data-class="media-frame ">
			<?php esc_html_e( 'Upload New Icons', 'custom-elementor-icons' ); ?>
		</a> &nbsp; <span class="spinner"></span></h2>
	<div id="msg"></div>
	<?php if ( ! empty( $custom_elementor_icons_fonts ) && is_array( $custom_elementor_icons_fonts ) ) : ?>
		<div class="metabox-holder meta-search">
			<div class="postbox">
				<h3>
					<input class="search-icon" type="text" placeholder="<?php esc_attr_e( 'Search', 'custom-elementor-icons' ); ?>"/>
					<span class="search-count"></span>
				</h3>
			</div>
		</div>
		<div class="stm_cei_icon_sets_wrap">
		<?php
		foreach ( $custom_elementor_icons_fonts as $custom_elementor_icons_font => $custom_elementor_icons_info ) :
			$custom_elementor_icons_data = \Custom_Elementor_Icons\Classes\FontData::load( $custom_elementor_icons_info );

			if ( empty( $custom_elementor_icons_data['icons'] ) ) {
				continue;
			}

			$custom_elementor_icons_prefix = $custom_elementor_icons_data['prefix'];
			$custom_elementor_icons_label  = apply_filters( 'stm_delete_iconset_button_label', esc_html__( 'Delete Icon Set', 'custom-elementor-icons' ), $custom_elementor_icons_font ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- public hook kept for backward compatibility.
			?>
			<div class="icon_set-<?php echo esc_attr( $custom_elementor_icons_font ); ?> metabox-holder">
				<div class="postbox">
					<h3 class="icon_font_name"><strong><?php echo esc_html( ucfirst( $custom_elementor_icons_font ) ); ?></strong>
						<span class="fonts-count count-<?php echo esc_attr( $custom_elementor_icons_font ); ?>"><?php echo esc_html( count( $custom_elementor_icons_data['icons'] ) ); ?></span>
						<button
							class="button button-secondary button-small cei_delete_font"
							data-delete="<?php echo esc_attr( $custom_elementor_icons_font ); ?>"
							data-title="<?php echo esc_attr( $custom_elementor_icons_label ); ?>">
						<?php echo esc_html( $custom_elementor_icons_label ); ?>
						</button>
						<div class="clearfix"></div>
					</h3>
					<div class="inside">
						<div class="icon_actions"></div>
						<div class="icon_search">
							<ul class="icons-list fi_icon">
							<?php foreach ( $custom_elementor_icons_data['icons'] as $custom_elementor_icons_icon ) : ?>
								<li
									title="<?php echo esc_attr( $custom_elementor_icons_icon['class'] ); ?>"
									data-icons="<?php echo esc_attr( $custom_elementor_icons_icon['class'] ); ?>"
									data-icons-tag="<?php echo esc_attr( $custom_elementor_icons_icon['tags'] ); ?>">
									<i class="<?php echo esc_attr( $custom_elementor_icons_prefix . $custom_elementor_icons_icon['class'] ); ?>"></i>
									<label class="icon"><?php echo esc_html( $custom_elementor_icons_icon['class'] ); ?></label>
								</li>
							<?php endforeach; ?>
							</ul>
						</div>
					</div>
				</div>
			</div>
			<?php
		endforeach;
		?>
		</div>
	<?php else : ?>
		<div class="updated">
			<p><?php esc_html_e( 'No font icons uploaded. Upload some font icons to display here:', 'custom-elementor-icons' ); ?></p>
			<p><?php esc_html_e( '1. Custom Icons Set/Pack should be uploaded. Icon Fonts can be generated via', 'custom-elementor-icons' ); ?>
				<a href="https://icomoon.io/app/" target="_blank" rel="noopener">https://icomoon.io/app/</a>.
			</p>
			<p><?php esc_html_e( '2. For using the custom icons, upload the pack of Icon Fonts.', 'custom-elementor-icons' ); ?></p>
			<p><?php esc_html_e( '3. Add your Custom Fonts or generate Icon Fonts through', 'custom-elementor-icons' ); ?>
				<a href="https://icomoon.io/app/" target="_blank" rel="noopener">https://icomoon.io/app/</a>.</p>
		</div>
	<?php endif; ?>
</div>
