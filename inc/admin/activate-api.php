<?php defined( 'ABSPATH' ) or die; ?>
<div class="wrap wrap-wp-idenfy">
	<img class="logo" src="<?php esc_attr_e( plugins_url( 'images/logo.png', WP_IDENFY_FILE ) ); ?>">
	<h3><?php _e( 'Activate plugin', 'wp-idenfy' ); ?></h3>
	<p><?php _e( 'Please enter your iDenfy account API KEY and API SECRET', 'wp-idenfy' ); ?></p>
	<form action="<?php esc_attr_e( admin_url( 'admin-post.php?action=wp_idenfy_sapis' ) ); ?>" method="POST">
		<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>
		<table class="api-form">
			<tbody>
				<tr>
					<td class="p-bottom"><?php _e( 'API KEY:', 'wp-idenfy' ); ?></td>
					<td class="p-bottom"><input type="text" name="api_key" value="<?php esc_attr_e( $this->get_option( 'api_key' ) ); ?>" autocomplete="off" required></td>
				</tr>
				<tr>
					<td><?php _e( 'API SECRET:', 'wp-idenfy' ); ?></td>
					<td><input type="text" name="api_secret" value="<?php esc_attr_e( $this->get_option( 'api_secret' ) ); ?>" autocomplete="off" required></td>
				</tr>
			</tbody>
		</table>
		<p class="link-buttons-2">
			<button href="#" type="submit" class="button btn-activate"><?php _e( 'Activate', 'wp-idenfy' ); ?></button>
		</p>
	</form>
	<p class="link-buttons-3">
		<a href="<?php esc_attr_e( admin_url( '?page=wp-idenfy' )); ?>" class="button btn-go-back" ><?php _e( 'Go back', 'wp-idenfy' ); ?></a>
	</p>
</div>