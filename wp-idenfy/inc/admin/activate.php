<?php defined( 'ABSPATH' ) or die; ?>
<div class="wrap wrap-wp-idenfy">
	<img class="logo" src="<?php esc_attr_e( plugins_url( 'images/logo.png', WP_IDENFY_FILE ) ); ?>">
	<h3><?php _e( 'Welcome', 'wp-idenfy' ); ?></h3>
	<p><?php _e( 'You will need iDenfy account and API keys to activate the plugin. Click the "I HAVE ACCOUNT" button to continue plugin activation. If you don\'t have an account with iDenfy click the "REGISTRATION" button.', 'wp-idenfy' ); ?></p>
	<p class="link-buttons">
		<a href="<?php esc_attr_e( admin_url( 'admin.php?page=wp-idenfy-settings' )); ?>" class="button" ><?php _e( 'I have an account', 'wp-idenfy' ); ?></a>
		<a href="<?php esc_attr_e( WP_IDENFY_REGISTER_URL ); ?>" target="_blank" class="button btn-bg-white" ><?php _e( 'Registration', 'wp-idenfy' ); ?></a>
	</p>
</div>