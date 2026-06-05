<?php
/**
 * Fired when the plugin is deleted from the WordPress admin.
 * Removes all options the plugin created, including the stored API credentials.
 */

defined( 'WP_UNINSTALL_PLUGIN' ) or exit;

$wp_idenfy_options = array(
	'wp_idenfy_options',
	'wp_idenfy_kyc',
	'wp_idenfy_customization',
	'wp_idenfy_customization_kyb',
);

foreach ( $wp_idenfy_options as $wp_idenfy_option ) {
	delete_option( $wp_idenfy_option );
}
