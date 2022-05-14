<?php defined( 'ABSPATH' ) or die; ?>
<div class="wrap wrap-wp-idenfy">
	<img class="logo" src="<?php esc_attr_e( plugins_url( 'images/logo.png', WP_IDENFY_FILE ) ); ?>">
	<h3><?php _e( 'Incorrect credentials', 'wp-idenfy' ); ?></h3>
	<p><?php _e( 'The API KEY and API SECRET are incorrect. Verify API KEY and API SECRET and click the "TRY AGAIN" button. If you don\'t have API KEY and API SECRET click "REGISTRATION BUTTON"', 'wp-idenfy' ); ?></p>
	<p class="link-buttons">
		<a href="<?php esc_attr_e( admin_url( 'admin.php?page=wp-idenfy-settings' )); ?>" class="button" ><?php _e( 'Try again', 'wp-idenfy' ); ?></a>
		<a href="<?php esc_attr_e( WP_IDENFY_REGISTER_URL ); ?>" target="_blank" class="button btn-bg-white" ><?php _e( 'Registration', 'wp-idenfy' ); ?></a>
	</p>
</div>