<?php defined( 'ABSPATH' ) or die;

$active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'settings';
if ( ! in_array( $active_tab, array( 'settings', 'kyc', 'kyb', 'customization' ), true ) ) {
	$active_tab = 'settings';
}

$tabs = array(
	'settings'      => __( 'Settings', 'wp-idenfy' ),
	'kyc'           => __( 'KYC', 'wp-idenfy' ),
	'kyb'           => __( 'KYB', 'wp-idenfy' ),
	'customization' => __( 'Customization', 'wp-idenfy' ),
);

$has_credentials = $this->get_option( 'api_key' ) !== '' && $this->get_option( 'api_secret' ) !== '';
$logo_url        = plugins_url( 'images/logo.png', WP_IDENFY_FILE );
$cust            = $this->get_customization();
$kyc             = $this->get_kyc_settings();
?>
<div class="wrap">
	<h1 class="wp-idenfy-title">
		<img class="wp-idenfy-header-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'iDenfy', 'wp-idenfy' ); ?>">
	</h1>

	<?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'invalid_credentials' ) : ?>
		<div class="notice notice-error">
			<p><?php _e( 'The API KEY and API SECRET are incorrect. Please verify and try again.', 'wp-idenfy' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['saved'] ) && in_array( $_GET['saved'], array( '1', 'kyc' ), true ) ) :
		$saved_msg = esc_html__( 'API credentials saved successfully.', 'wp-idenfy' );
		if ( $_GET['saved'] === 'kyc' ) {
			$saved_msg = esc_html__( 'KYC settings saved successfully.', 'wp-idenfy' );
		} elseif ( $active_tab === 'customization' ) {
			$saved_msg = esc_html__( 'Customization saved successfully.', 'wp-idenfy' );
		}
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo $saved_msg; ?></p>
		</div>
	<?php endif; ?>

	<div class="wp-idenfy-container">
		<h2 class="nav-tab-wrapper wp-idenfy-tabs">
			<?php foreach ( $tabs as $slug => $label ) : ?>
				<a href="<?php echo esc_url( admin_url( 'admin.php?page=wp-idenfy&tab=' . $slug ) ); ?>"
				   class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>"
				   data-tab="<?php echo esc_attr( $slug ); ?>"><?php echo esc_html( $label ); ?></a>
			<?php endforeach; ?>
		</h2>

		<div class="tab-panel <?php echo $active_tab === 'settings' ? 'is-active' : ''; ?>" data-tab="settings">
			<div class="wp-idenfy-card-grid">
				<div class="wp-idenfy-card-form">
					<div class="wp-idenfy-form-box">
						<h2><?php _e( 'API Credentials', 'wp-idenfy' ); ?></h2>
						<?php if ( ! $has_credentials ) : ?>
							<p class="description">
								<?php _e( 'Enter your iDenfy API KEY and API SECRET to activate the plugin.', 'wp-idenfy' ); ?>
								<?php printf(
									/* translators: %s: registration link */
									__( 'Don\'t have an account? %s.', 'wp-idenfy' ),
									'<a href="' . esc_url( WP_IDENFY_REGISTER_URL ) . '" target="_blank">' . esc_html__( 'Register here', 'wp-idenfy' ) . '</a>'
								); ?>
							</p>
						<?php endif; ?>

						<?php
						if ( $has_credentials ) {
							$status_state = 'valid';
							$status_icon  = 'dashicons-yes-alt';
							$status_text  = __( 'Connected — credentials are valid.', 'wp-idenfy' );
						} else {
							$status_state = 'empty';
							$status_icon  = 'dashicons-dismiss';
							$status_text  = __( 'Not connected.', 'wp-idenfy' );
						}
						?>
						<div class="wp-idenfy-cred-status is-<?php echo esc_attr( $status_state ); ?>" id="wp-idenfy-cred-status" aria-live="polite">
							<span class="dashicons <?php echo esc_attr( $status_icon ); ?>"></span>
							<span class="wp-idenfy-cred-status-text"><?php echo esc_html( $status_text ); ?></span>
						</div>

						<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_sapis' ) ); ?>" method="POST" id="wp-idenfy-credentials-form">
							<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-api-key"><?php _e( 'API KEY', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-api-key" name="api_key" value="<?php echo esc_attr( $this->get_option( 'api_key' ) ); ?>" autocomplete="off" required>
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-api-secret"><?php _e( 'API SECRET', 'wp-idenfy' ); ?></label>
								<div class="wp-idenfy-secret-wrap">
									<input type="password" id="wp-idenfy-api-secret" name="api_secret" value="<?php echo esc_attr( $this->get_option( 'api_secret' ) ); ?>" autocomplete="off" required>
									<button type="button" class="wp-idenfy-secret-toggle" id="wp-idenfy-secret-toggle" aria-label="<?php esc_attr_e( 'Show secret', 'wp-idenfy' ); ?>" aria-pressed="false">
										<span class="dashicons dashicons-visibility"></span>
									</button>
								</div>
							</div>
							<p class="submit">
								<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'wp-idenfy' ); ?></button>
							</p>
						</form>
					</div>
				</div>
			</div>
		</div>

		<div class="tab-panel <?php echo $active_tab === 'kyc' ? 'is-active' : ''; ?>" data-tab="kyc">
			<div class="wp-idenfy-card-grid">
				<div class="wp-idenfy-card-form">
					<div class="wp-idenfy-form-box">
						<h2><?php _e( 'KYC Verification', 'wp-idenfy' ); ?></h2>
						<p class="description"><?php _e( 'Add an identity verification button to any page or post with this shortcode:', 'wp-idenfy' ); ?></p>
						<p><code class="shortcode-copy" title="<?php esc_attr_e( 'Click to copy', 'wp-idenfy' ); ?>">[IDENFY]</code></p>
						<p class="description"><?php _e( 'The settings below apply to every <code>[IDENFY]</code> button on your site.', 'wp-idenfy' ); ?></p>

						<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_save_kyc' ) ); ?>" method="POST" id="wp-idenfy-kyc-form">
							<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>

							<h3><?php _e( 'Redirect by result', 'wp-idenfy' ); ?></h3>
							<p class="description"><?php _e( 'Where to send the visitor after their verification ends. Relative paths (e.g. <code>/welcome</code>) and full URLs both work. Leave blank to keep them on the page.', 'wp-idenfy' ); ?></p>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-redirect"><?php _e( 'On success (approved)', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-redirect" name="redirect" value="<?php echo esc_attr( $kyc['redirect'] ); ?>" placeholder="/welcome">
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-redirect-failed"><?php _e( 'On failure', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-redirect-failed" name="redirect_failed" value="<?php echo esc_attr( $kyc['redirect_failed'] ); ?>" placeholder="/verification-failed">
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-redirect-unverified"><?php _e( 'On unverified / pending review', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-redirect-unverified" name="redirect_unverified" value="<?php echo esc_attr( $kyc['redirect_unverified'] ); ?>" placeholder="/contact">
							</div>

							<h3><?php _e( 'Accept borderline results', 'wp-idenfy' ); ?></h3>
							<p class="description"><?php _e( 'By default only an approved result counts as a pass. Loosen this based on your own risk appetite.', 'wp-idenfy' ); ?></p>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="accept_suspected" value="1" <?php checked( ! empty( $kyc['accept_suspected'] ) ); ?>> <?php _e( 'Accept suspected (approved but flagged) results as a pass', 'wp-idenfy' ); ?></label>
							</div>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="accept_unverified" value="1" <?php checked( ! empty( $kyc['accept_unverified'] ) ); ?>> <?php _e( 'Accept unverified results (e.g. awaiting manual review) as a pass', 'wp-idenfy' ); ?></label>
							</div>

							<h3><?php _e( 'Verification window', 'wp-idenfy' ); ?></h3>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="hide_on_complete" value="1" <?php checked( ! empty( $kyc['hide_on_complete'] ) ); ?>> <?php _e( 'Auto-close the window when verification finishes (otherwise a Close button is shown)', 'wp-idenfy' ); ?></label>
							</div>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="hide_button_on_complete" value="1" <?php checked( ! empty( $kyc['hide_button_on_complete'] ) ); ?>> <?php _e( 'Hide the verification button after a successful verification', 'wp-idenfy' ); ?></label>
								<p class="description"><?php _e( 'Removes the button from the page once the visitor passes, so they can\'t start another verification. Has no effect if a success redirect is set. The button stays visible after a failure so they can retry.', 'wp-idenfy' ); ?></p>
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-close-text"><?php _e( 'Close button label', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-close-text" name="close_button_text" value="<?php echo esc_attr( $kyc['close_button_text'] ); ?>" placeholder="<?php esc_attr_e( 'Close', 'wp-idenfy' ); ?>">
								<p class="description"><?php _e( 'Text on the button the visitor clicks to dismiss the verification window after they finish. Only shown when auto-close above is off. Defaults to "Close".', 'wp-idenfy' ); ?></p>
							</div>

							<p class="submit">
								<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'wp-idenfy' ); ?></button>
							</p>
						</form>
					</div>
				</div>
				<div class="wp-idenfy-card-docs">
					<h3><?php _e( 'How it works', 'wp-idenfy' ); ?></h3>
					<p class="description"><?php _e( 'The button opens the iDenfy identity verification flow in an embedded window, so the visitor stays on your page. When they finish, the plugin reacts according to the settings on the left &mdash; redirect them, gate a form, or run your own code.', 'wp-idenfy' ); ?></p>
					<p class="description"><strong><?php _e( 'Important:', 'wp-idenfy' ); ?></strong> <?php _e( 'The in-page result is a convenience for onboarding UX &mdash; it is not a trustworthy confirmation on its own and can be tampered with in the browser. For anything that grants real access, confirm the result server-side with an iDenfy webhook before trusting it.', 'wp-idenfy' ); ?></p>

					<h3><?php _e( 'Gate a form (per page)', 'wp-idenfy' ); ?></h3>
					<p class="description"><?php _e( 'These two attributes go on the shortcode itself, since they point at a specific form on a specific page:', 'wp-idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>on_complete_enable</code> &mdash; <?php _e( 'CSS selector of an element to enable on a pass (e.g. a "Next" button). For a link or any non-form element, add the <code>idenfy-disabled</code> class to it &mdash; it will be greyed out and unclickable until the visitor passes.', 'wp-idenfy' ); ?></li>
						<li><code>sync_field</code> &mdash; <?php _e( 'CSS selector of a hidden input to set to <code>"completed"</code> on a pass &mdash; useful for form-plugin server-side validation.', 'wp-idenfy' ); ?></li>
					</ul>

					<p><strong><?php _e( 'Unlock a "Next" button once the visitor verifies:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY on_complete_enable=".gform_next_button"]</code></p>
					<p class="description"><?php _e( 'Replace <code>.gform_next_button</code> with whatever your form plugin uses (e.g. <code>.wpforms-page-next</code> for WPForms).', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Block form submission until verified:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY sync_field="input[name=kyc_status]"]</code></p>
					<p class="description"><?php _e( 'Add a required hidden field <code>&lt;input type="hidden" name="kyc_status" required&gt;</code> to your form. The plugin sets its value to <code>"completed"</code> on a pass.', 'wp-idenfy' ); ?></p>

					<h3><?php _e( 'Run your own code when it finishes', 'wp-idenfy' ); ?></h3>
					<p class="description"><?php _e( 'Need more? Listen for the <code>idenfy:kyc:complete</code> event on the button &mdash; it fires for every outcome:', 'wp-idenfy' ); ?></p>
					<pre class="wp-idenfy-code"><code>document.querySelector('a.idenfy-button').addEventListener('idenfy:kyc:complete', function(e) {
    // e.detail.outcome — 'approved' | 'suspected' | 'unverified' | 'failed'
    // e.detail.passed  — true if it counts as a pass (honours the accept settings)
    if (e.detail.passed) {
        // your code here — analytics, a custom message, etc.
    }
    // e.detail.raw holds the full message from iDenfy
});</code></pre>
					<p class="description"><?php _e( 'The event bubbles, so you can also listen on <code>document</code>.', 'wp-idenfy' ); ?></p>
				</div>
			</div>
		</div>

		<div class="tab-panel <?php echo $active_tab === 'kyb' ? 'is-active' : ''; ?>" data-tab="kyb">
			<div class="wp-idenfy-card-grid">
				<div class="wp-idenfy-card-form">
					<div class="wp-idenfy-form-box">
						<h2><?php _e( 'KYB Verification', 'wp-idenfy' ); ?></h2>
						<p class="description">
							<?php _e( 'Use this shortcode to embed business verification on any page or post:', 'wp-idenfy' ); ?>
						</p>
						<p><code class="shortcode-copy shortcode-copy-kyb" title="<?php esc_attr_e( 'Click to copy', 'wp-idenfy' ); ?>">[IDENFY_KYB]</code></p>
						<p class="description">
							<?php _e( 'Requires a custom KYB flow configured in your iDenfy dashboard and KYB session creation enabled by iDenfy support on your account.', 'wp-idenfy' ); ?>
						</p>
						<p class="description">
							<?php _e( 'Use the shortcode attributes on the right to target a specific flow, theme, language, or questionnaire &mdash; e.g. to run different KYB flows on different pages. With no attributes, your iDenfy account defaults apply.', 'wp-idenfy' ); ?>
						</p>
					</div>
				</div>
				<div class="wp-idenfy-card-docs">
					<h3><?php _e( 'How it works', 'wp-idenfy' ); ?></h3>
					<p class="description"><?php _e( 'Drop the shortcode on any page. When a visitor opens it, the plugin starts a business verification with iDenfy and shows it in an embedded window. When the visitor finishes, the plugin can react &mdash; unlock a button, mark a form field, or run your own code.', 'wp-idenfy' ); ?></p>
					<h3><?php _e( 'Shortcode attributes', 'wp-idenfy' ); ?></h3>
						<p class="description"><?php _e( 'Every attribute is optional &mdash; a bare <code>[IDENFY_KYB]</code> uses your iDenfy account defaults.', 'wp-idenfy' ); ?></p>

					<h4><?php _e( 'Label &amp; track the session', 'wp-idenfy' ); ?></h4>
					<p class="description"><?php _e( 'Identify each session and tie it back to your own records.', 'wp-idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>client_id</code> &mdash; <?php _e( 'identifier shown in your iDenfy dashboard (max 100 chars). Auto-generated if omitted.', 'wp-idenfy' ); ?></li>
						<li><code>external_ref</code> &mdash; <?php _e( 'your own correlation ID, e.g. an order number (max 40 chars).', 'wp-idenfy' ); ?></li>
						<li><code>tags</code> &mdash; <?php _e( 'comma-separated labels (max 5, 32 chars each), e.g. <code>tags="checkout,premium"</code>.', 'wp-idenfy' ); ?></li>
					</ul>

					<h4><?php _e( 'Connect it to your page', 'wp-idenfy' ); ?></h4>
					<p class="description"><?php _e( 'Hook the verification into elements on your page.', 'wp-idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>on_complete_enable</code> &mdash; <?php _e( 'CSS selector of an element to enable when verification finishes (e.g. a "Next" button). For a link or any non-form element, add the <code>idenfy-disabled</code> class to it &mdash; it will be greyed out and unclickable until verification finishes.', 'wp-idenfy' ); ?></li>
						<li><code>sync_field</code> &mdash; <?php _e( 'CSS selector of a hidden input to set to <code>"completed"</code> when verification finishes &mdash; useful for form-plugin server-side validation.', 'wp-idenfy' ); ?></li>
						<li><code>hide_on_complete="true"</code> &mdash; <?php _e( 'Auto-hide the iframe after the user finishes. Off by default (a Close button is shown instead).', 'wp-idenfy' ); ?></li>
						<li><code>close_button_text</code> &mdash; <?php _e( 'Custom label for the Close button. Defaults to "Close".', 'wp-idenfy' ); ?></li>
							<li><code>redirect</code> &mdash; <?php _e( 'URL to send the visitor to after a successful verification, e.g. <code>redirect="/thank-you"</code>. Relative paths and full URLs both work.', 'wp-idenfy' ); ?></li>
					</ul>

					<h4><?php _e( 'Choose the flow &amp; look', 'wp-idenfy' ); ?></h4>
					<p class="description"><?php _e( 'Point a shortcode at a specific flow, theme, or questionnaire. Leave them off to use your iDenfy account defaults.', 'wp-idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>flow</code>, <code>theme</code>, <code>lifetime</code> &mdash; <?php _e( 'set the flow UUID, branding theme UUID, or token lifetime (seconds) for this shortcode.', 'wp-idenfy' ); ?></li>
						<li><code>questionnaire</code>, <code>questionnaire_required="true|false"</code> &mdash; <?php _e( 'set the questionnaire for this shortcode. Ignored when a flow is set (the flow controls its own questionnaire).', 'wp-idenfy' ); ?></li>
					</ul>

					<h3><?php _e( 'Examples', 'wp-idenfy' ); ?></h3>

					<p><strong><?php _e( 'Basic &mdash; uses your iDenfy account defaults:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB]</code></p>

					<p><strong><?php _e( 'Unlock a "Next" button in a multi-step form:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB on_complete_enable=".gform_next_button"]</code></p>
					<p class="description"><?php _e( 'Replace <code>.gform_next_button</code> with whatever your form plugin uses (e.g. <code>.wpforms-page-next</code> for WPForms).', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Block form submission until verified:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB sync_field="input[name=kyb_status]"]</code></p>
					<p class="description"><?php _e( 'Add a required hidden field <code>&lt;input type="hidden" name="kyb_status" required&gt;</code> to your form. The plugin sets its value to <code>"completed"</code> on success.', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Two different KYB flows on the same site:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB flow="standard-kyb-uuid"]</code> <?php _e( 'on one page, and', 'wp-idenfy' ); ?> <code>[IDENFY_KYB flow="sole-proprietor-uuid"]</code> <?php _e( 'on another.', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Tie a session to an order:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB external_ref="order-12345" tags="checkout,premium"]</code></p>
					<p class="description"><?php _e( 'The external reference and tags show up in your iDenfy dashboard, making it easier to match KYB sessions back to records in your own system.', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Auto-hide iframe after completion (no Close button):', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB hide_on_complete="true"]</code></p>

						<p><strong><?php _e( 'Send the visitor to a thank-you page when they finish:', 'wp-idenfy' ); ?></strong></p>
						<p><code>[IDENFY_KYB redirect="/thank-you"]</code></p>

					<h3><?php _e( 'Run your own code when it finishes', 'wp-idenfy' ); ?></h3>
					<p class="description"><?php _e( 'Need more than the attributes above? Listen for the <code>idenfy:kyb:complete</code> event on the container &mdash; it fires on both success and failure:', 'wp-idenfy' ); ?></p>
					<pre class="wp-idenfy-code"><code>document.querySelector('.idenfy-kyb').addEventListener('idenfy:kyb:complete', function(e) {
    if (e.detail.status === 'success') {
        // your code here — analytics, a custom message, etc.
    } else if (e.detail.status === 'failed') {
        // handle failure
    }
    // e.detail.raw holds the full message from the iframe
});</code></pre>
					<p class="description"><?php _e( 'The event bubbles, so you can also listen on <code>document</code>.', 'wp-idenfy' ); ?></p>
				</div>
			</div>
		</div>

		<div class="tab-panel <?php echo $active_tab === 'customization' ? 'is-active' : ''; ?>" data-tab="customization">
			<div class="wp-idenfy-card-grid">
				<div class="wp-idenfy-card-form">
					<div class="wp-idenfy-form-box">
						<h2><?php _e( 'Button Customization', 'wp-idenfy' ); ?></h2>
						<p class="description"><?php _e( 'Change how the verification button looks. Preview updates as you type.', 'wp-idenfy' ); ?></p>
						<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_save_customization' ) ); ?>" method="POST" id="wp-idenfy-customization-form">
							<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-button-text"><?php _e( 'Button text', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-button-text" name="button_text" value="<?php echo esc_attr( $cust['button_text'] ); ?>" required>
							</div>
							<div class="wp-idenfy-field-row">
								<div class="wp-idenfy-field">
									<label for="wp-idenfy-bg-color"><?php _e( 'Background color', 'wp-idenfy' ); ?></label>
									<input type="color" id="wp-idenfy-bg-color" name="bg_color" value="<?php echo esc_attr( $cust['bg_color'] ); ?>">
								</div>
								<div class="wp-idenfy-field">
									<label for="wp-idenfy-text-color"><?php _e( 'Text color', 'wp-idenfy' ); ?></label>
									<input type="color" id="wp-idenfy-text-color" name="text_color" value="<?php echo esc_attr( $cust['text_color'] ); ?>">
								</div>
							</div>
							<div class="wp-idenfy-field-row">
								<div class="wp-idenfy-field">
									<label for="wp-idenfy-border-radius"><?php _e( 'Border radius (px)', 'wp-idenfy' ); ?></label>
									<input type="number" id="wp-idenfy-border-radius" name="border_radius" value="<?php echo esc_attr( $cust['border_radius'] ); ?>" min="0" max="100">
								</div>
								<div class="wp-idenfy-field">
									<label for="wp-idenfy-font-size"><?php _e( 'Font size (px)', 'wp-idenfy' ); ?></label>
									<input type="number" id="wp-idenfy-font-size" name="font_size" value="<?php echo esc_attr( $cust['font_size'] ); ?>" min="8" max="64">
								</div>
							</div>
							<div class="wp-idenfy-field-row">
								<div class="wp-idenfy-field">
									<label for="wp-idenfy-padding-y"><?php _e( 'Padding vertical (px)', 'wp-idenfy' ); ?></label>
									<input type="number" id="wp-idenfy-padding-y" name="padding_y" value="<?php echo esc_attr( $cust['padding_y'] ); ?>" min="0" max="100">
								</div>
								<div class="wp-idenfy-field">
									<label for="wp-idenfy-padding-x"><?php _e( 'Padding horizontal (px)', 'wp-idenfy' ); ?></label>
									<input type="number" id="wp-idenfy-padding-x" name="padding_x" value="<?php echo esc_attr( $cust['padding_x'] ); ?>" min="0" max="200">
								</div>
							</div>
							<div class="wp-idenfy-field wp-idenfy-field-code">
								<label for="wp-idenfy-advanced-css"><?php _e( 'Advanced CSS', 'wp-idenfy' ); ?></label>
								<textarea id="wp-idenfy-advanced-css" name="advanced_css" rows="14"><?php echo esc_textarea( $cust['advanced_css'] ); ?></textarea>
								<p class="description"><?php _e( 'The current button stylesheet. On save, the fields above and this CSS are reconciled: edits made here are pulled into the fields, and edits made in the fields are written back into this CSS.', 'wp-idenfy' ); ?></p>
							</div>
							<p class="submit">
								<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'wp-idenfy' ); ?></button>
							</p>
						</form>
					</div>
				</div>
				<div class="wp-idenfy-card-image wp-idenfy-preview-area">
					<h3 class="wp-idenfy-preview-heading"><?php _e( 'Preview', 'wp-idenfy' ); ?></h3>
					<a href="#" id="wp-idenfy-preview-button" class="idenfy-button"><?php echo esc_html( $cust['button_text'] ); ?><i class="fa fa-circle-notch fa-spin ajax-loader"></i></a>
				</div>
			</div>
		</div>
	</div>
</div>
