<?php defined( 'ABSPATH' ) or die; ?>
<div class="wrap wrap-wp-idenfy">
	<img class="logo" src="<?php esc_attr_e( plugins_url( 'images/logo.png', WP_IDENFY_FILE ) ); ?>">
	<h3><?php _e( 'Shortcode', 'wp-idenfy' ); ?></h3>
	<p><?php _e( 'Use this shortcode to add an identity verification button to any page or post.', 'wp-idenfy' ); ?></p>
	<table class="shortcode-table">
		<tbody>
			<tr>
				<td><span class="shortcode-def">[IDENFY]</span></td>
				<td><img class="shortcode-img" src="<?php esc_attr_e( plugins_url( 'images/arrow.png', WP_IDENFY_FILE ) ); ?>"></td>
				<td><a href="#" class="button" ><?php _e( 'Verify Me', 'wp-idenfy' ); ?></a></td>
			</tr>
			<tr>
				<td class="p-top"><span class="span-padding"><span class="span-dotted shortcode-copy"><?php _e( 'copy shortcode', 'wp-idenfy' ); ?></span></span></td>
				<td class="p-top"></td>
				<td class="p-top"><?php _e( 'The user will see this button', 'wp-idenfy' ); ?></td>
			</tr>
		</tbody>
	</table>
</div>