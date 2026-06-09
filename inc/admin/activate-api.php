<?php defined( 'ABSPATH' ) or die;

// phpcs:disable WordPress.Security.NonceVerification.Recommended
$active_tab   = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : 'settings';
$notice_error = isset( $_GET['error'] ) ? sanitize_key( wp_unslash( $_GET['error'] ) ) : '';
$notice_saved = isset( $_GET['saved'] ) ? sanitize_key( wp_unslash( $_GET['saved'] ) ) : '';
// phpcs:enable WordPress.Security.NonceVerification.Recommended
if ( ! in_array( $active_tab, array( 'settings', 'kyc', 'kyb', 'customization' ), true ) ) {
	$active_tab = 'settings';
}

$tabs = array(
	'settings'      => __( 'Settings', 'idenfy' ),
	'kyc'           => __( 'KYC', 'idenfy' ),
	'kyb'           => __( 'KYB', 'idenfy' ),
	'customization' => __( 'Customization', 'idenfy' ),
);

$has_credentials = $this->get_option( 'api_key' ) !== '' && $this->get_option( 'api_secret' ) !== '';
$logo_url        = plugins_url( 'images/logo.png', WP_IDENFY_FILE );
$kyc             = $this->get_kyc_settings();
?>
<div class="wrap">
	<h1 class="wp-idenfy-title">
		<img class="wp-idenfy-header-logo" src="<?php echo esc_url( $logo_url ); ?>" alt="<?php esc_attr_e( 'iDenfy', 'idenfy' ); ?>">
	</h1>

	<?php if ( $notice_error === 'invalid_credentials' ) : ?>
		<div class="notice notice-error">
			<p><?php esc_html_e( 'The API KEY and API SECRET are incorrect. Please verify and try again.', 'idenfy' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( in_array( $notice_saved, array( '1', 'kyc' ), true ) ) :
		$saved_msg = __( 'API credentials saved successfully.', 'idenfy' );
		if ( $notice_saved === 'kyc' ) {
			$saved_msg = __( 'KYC settings saved successfully.', 'idenfy' );
		} elseif ( $active_tab === 'customization' ) {
			$saved_msg = __( 'Customization saved successfully.', 'idenfy' );
		}
		?>
		<div class="notice notice-success is-dismissible">
			<p><?php echo esc_html( $saved_msg ); ?></p>
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
						<h2><?php esc_html_e( 'API Credentials', 'idenfy' ); ?></h2>
						<?php if ( ! $has_credentials ) : ?>
							<p class="description">
								<?php esc_html_e( 'Enter your iDenfy API KEY and API SECRET to activate the plugin.', 'idenfy' ); ?>
								<?php printf(
									/* translators: %s: registration link */
									esc_html__( 'Don\'t have an account? %s.', 'idenfy' ),
									'<a href="' . esc_url( WP_IDENFY_REGISTER_URL ) . '" target="_blank">' . esc_html__( 'Register here', 'idenfy' ) . '</a>'
								); ?>
							</p>
						<?php endif; ?>

						<?php
						if ( $has_credentials ) {
							$status_state = 'valid';
							$status_icon  = 'dashicons-yes-alt';
							$status_text  = __( 'Connected — credentials are valid.', 'idenfy' );
						} else {
							$status_state = 'empty';
							$status_icon  = 'dashicons-dismiss';
							$status_text  = __( 'Not connected.', 'idenfy' );
						}
						?>
						<div class="wp-idenfy-cred-status is-<?php echo esc_attr( $status_state ); ?>" id="wp-idenfy-cred-status" aria-live="polite">
							<span class="dashicons <?php echo esc_attr( $status_icon ); ?>"></span>
							<span class="wp-idenfy-cred-status-text"><?php echo esc_html( $status_text ); ?></span>
						</div>

						<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_sapis' ) ); ?>" method="POST" id="wp-idenfy-credentials-form">
							<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-api-key"><?php esc_html_e( 'API KEY', 'idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-api-key" name="api_key" value="<?php echo esc_attr( $this->get_option( 'api_key' ) ); ?>" autocomplete="off" required>
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-api-secret"><?php esc_html_e( 'API SECRET', 'idenfy' ); ?></label>
								<div class="wp-idenfy-secret-wrap">
									<input type="password" id="wp-idenfy-api-secret" name="api_secret" value="" autocomplete="off"<?php echo $has_credentials ? ' placeholder="••••••••••••"' : ' required'; ?>>
									<button type="button" class="wp-idenfy-secret-toggle" id="wp-idenfy-secret-toggle" aria-label="<?php esc_attr_e( 'Show secret', 'idenfy' ); ?>" aria-pressed="false">
										<span class="dashicons dashicons-visibility"></span>
									</button>
								</div>
								<?php if ( $has_credentials ) : ?>
									<p class="description"><?php esc_html_e( 'A secret is already saved. Leave this blank to keep it, or enter a new one to replace it.', 'idenfy' ); ?></p>
								<?php endif; ?>
							</div>
							<p class="submit">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'idenfy' ); ?></button>
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
						<h2><?php esc_html_e( 'KYC Verification', 'idenfy' ); ?></h2>
						<p class="description"><?php esc_html_e( 'Add an identity verification button to any page or post with this shortcode:', 'idenfy' ); ?></p>
						<p><code class="shortcode-copy" title="<?php esc_attr_e( 'Click to copy', 'idenfy' ); ?>">[IDENFY]</code></p>
						<p class="description"><?php echo wp_kses_post( __( 'The settings below apply to every <code>[IDENFY]</code> button on your site.', 'idenfy' ) ); ?></p>

						<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_save_kyc' ) ); ?>" method="POST" id="wp-idenfy-kyc-form">
							<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>

							<h3><?php esc_html_e( 'Redirect by result', 'idenfy' ); ?></h3>
							<p class="description"><?php echo wp_kses_post( __( 'Where to send the visitor after their verification ends. Relative paths (e.g. <code>/welcome</code>) and full URLs both work. Leave blank to keep them on the page.', 'idenfy' ) ); ?></p>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-redirect"><?php esc_html_e( 'On success (approved)', 'idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-redirect" name="redirect" value="<?php echo esc_attr( $kyc['redirect'] ); ?>" placeholder="/welcome">
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-redirect-failed"><?php esc_html_e( 'On failure', 'idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-redirect-failed" name="redirect_failed" value="<?php echo esc_attr( $kyc['redirect_failed'] ); ?>" placeholder="/verification-failed">
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-redirect-unverified"><?php esc_html_e( 'On unverified / pending review', 'idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-redirect-unverified" name="redirect_unverified" value="<?php echo esc_attr( $kyc['redirect_unverified'] ); ?>" placeholder="/contact">
							</div>

							<h3><?php esc_html_e( 'Accept borderline results', 'idenfy' ); ?></h3>
							<p class="description"><?php esc_html_e( 'By default only an approved result counts as a pass. Loosen this based on your own risk appetite.', 'idenfy' ); ?></p>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="accept_suspected" value="1" <?php checked( ! empty( $kyc['accept_suspected'] ) ); ?>> <?php esc_html_e( 'Accept suspected (approved but flagged) results as a pass', 'idenfy' ); ?></label>
							</div>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="accept_unverified" value="1" <?php checked( ! empty( $kyc['accept_unverified'] ) ); ?>> <?php esc_html_e( 'Accept unverified results (e.g. awaiting manual review) as a pass', 'idenfy' ); ?></label>
							</div>

							<h3><?php esc_html_e( 'Verification window', 'idenfy' ); ?></h3>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="hide_on_complete" value="1" <?php checked( ! empty( $kyc['hide_on_complete'] ) ); ?>> <?php esc_html_e( 'Auto-close the window when verification finishes (otherwise a Close button is shown)', 'idenfy' ); ?></label>
							</div>
							<div class="wp-idenfy-field">
								<label><input type="checkbox" name="hide_button_on_complete" value="1" <?php checked( ! empty( $kyc['hide_button_on_complete'] ) ); ?>> <?php esc_html_e( 'Hide the verification button after a successful verification', 'idenfy' ); ?></label>
								<p class="description"><?php esc_html_e( 'Removes the button from the page once the visitor passes, so they can\'t start another verification. Has no effect if a success redirect is set. The button stays visible after a failure so they can retry.', 'idenfy' ); ?></p>
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyc-close-text"><?php esc_html_e( 'Close button label', 'idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyc-close-text" name="close_button_text" value="<?php echo esc_attr( $kyc['close_button_text'] ); ?>" placeholder="<?php esc_attr_e( 'Close', 'idenfy' ); ?>">
								<p class="description"><?php esc_html_e( 'Text on the button the visitor clicks to dismiss the verification window after they finish. Only shown when auto-close above is off. Defaults to "Close".', 'idenfy' ); ?></p>
							</div>

							<p class="submit">
								<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'idenfy' ); ?></button>
							</p>
						</form>
					</div>
				</div>
				<div class="wp-idenfy-card-docs">
					<h3><?php esc_html_e( 'How it works', 'idenfy' ); ?></h3>
					<p class="description"><?php esc_html_e( 'The button opens the iDenfy identity verification flow in an embedded window, so the visitor stays on your page. When they finish, the plugin reacts according to the settings on the left &mdash; redirect them, gate a form, or run your own code.', 'idenfy' ); ?></p>
					<p class="description"><strong><?php esc_html_e( 'Important:', 'idenfy' ); ?></strong> <?php esc_html_e( 'The in-page result is a convenience for onboarding UX &mdash; it is not a trustworthy confirmation on its own and can be tampered with in the browser. For anything that grants real access, confirm the result server-side with an iDenfy webhook before trusting it.', 'idenfy' ); ?></p>

					<h3><?php esc_html_e( 'Gate a form (per page)', 'idenfy' ); ?></h3>
					<p class="description"><?php esc_html_e( 'These two attributes go on the shortcode itself, since they point at a specific form on a specific page:', 'idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>on_complete_enable</code> &mdash; <?php echo wp_kses_post( __( 'CSS selector of an element to enable on a pass (e.g. a "Next" button). For a link or any non-form element, add the <code>idenfy-disabled</code> class to it &mdash; it will be greyed out and unclickable until the visitor passes.', 'idenfy' ) ); ?></li>
						<li><code>sync_field</code> &mdash; <?php echo wp_kses_post( __( 'CSS selector of a hidden input to set to <code>"completed"</code> on a pass &mdash; useful for form-plugin server-side validation.', 'idenfy' ) ); ?></li>
					</ul>

					<p><strong><?php esc_html_e( 'Unlock a "Next" button once the visitor verifies:', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY on_complete_enable=".gform_next_button"]</code></p>
					<p class="description"><?php echo wp_kses_post( __( 'Replace <code>.gform_next_button</code> with whatever your form plugin uses (e.g. <code>.wpforms-page-next</code> for WPForms).', 'idenfy' ) ); ?></p>

					<p><strong><?php esc_html_e( 'Block form submission until verified:', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY sync_field="input[name=kyc_status]"]</code></p>
					<p class="description"><?php echo wp_kses_post( __( 'Add a required hidden field <code>&lt;input type="hidden" name="kyc_status" required&gt;</code> to your form. The plugin sets its value to <code>"completed"</code> on a pass.', 'idenfy' ) ); ?></p>

					<h3><?php esc_html_e( 'Run your own code when it finishes', 'idenfy' ); ?></h3>
					<p class="description"><?php echo wp_kses_post( __( 'Need more? Listen for the <code>idenfy:kyc:complete</code> event on the button &mdash; it fires for every outcome:', 'idenfy' ) ); ?></p>
					<pre class="wp-idenfy-code"><code>document.querySelector('a.idenfy-button').addEventListener('idenfy:kyc:complete', function(e) {
    // e.detail.outcome — 'approved' | 'suspected' | 'unverified' | 'failed'
    // e.detail.passed  — true if it counts as a pass (honours the accept settings)
    if (e.detail.passed) {
        // your code here — analytics, a custom message, etc.
    }
    // e.detail.raw holds the full message from iDenfy
});</code></pre>
					<p class="description"><?php echo wp_kses_post( __( 'The event bubbles, so you can also listen on <code>document</code>.', 'idenfy' ) ); ?></p>
				</div>
			</div>
		</div>

		<div class="tab-panel <?php echo $active_tab === 'kyb' ? 'is-active' : ''; ?>" data-tab="kyb">
			<div class="wp-idenfy-card-grid">
				<div class="wp-idenfy-card-form">
					<div class="wp-idenfy-form-box">
						<h2><?php esc_html_e( 'KYB Verification', 'idenfy' ); ?></h2>
						<p class="description">
							<?php esc_html_e( 'Use this shortcode to embed business verification on any page or post:', 'idenfy' ); ?>
						</p>
						<p><code class="shortcode-copy shortcode-copy-kyb" title="<?php esc_attr_e( 'Click to copy', 'idenfy' ); ?>">[IDENFY_KYB]</code></p>
						<p class="description">
							<?php esc_html_e( 'Requires a custom KYB flow configured in your iDenfy dashboard and KYB session creation enabled by iDenfy support on your account.', 'idenfy' ); ?>
						</p>
						<p class="description">
							<?php esc_html_e( 'Use the shortcode attributes on the right to target a specific flow, theme, language, or questionnaire &mdash; e.g. to run different KYB flows on different pages. With no attributes, your iDenfy account defaults apply.', 'idenfy' ); ?>
						</p>
						<p class="description">
							<?php printf(
								/* translators: %s: link to the Customization tab */
								esc_html__( 'The shortcode renders a button. Change its text and appearance in the %s tab.', 'idenfy' ),
								'<a href="' . esc_url( admin_url( 'admin.php?page=wp-idenfy&tab=customization' ) ) . '">' . esc_html__( 'Customization', 'idenfy' ) . '</a>'
							); ?>
						</p>
					</div>
				</div>
				<div class="wp-idenfy-card-docs">
					<h3><?php esc_html_e( 'How it works', 'idenfy' ); ?></h3>
					<p class="description"><?php esc_html_e( 'Drop the shortcode on any page. It shows a button; when a visitor clicks it, the plugin starts a business verification with iDenfy and opens it in a modal window over your page. When the visitor finishes, the plugin can react &mdash; unlock a button, mark a form field, or run your own code.', 'idenfy' ); ?></p>
					<h3><?php esc_html_e( 'Shortcode attributes', 'idenfy' ); ?></h3>
						<p class="description"><?php echo wp_kses_post( __( 'Every attribute is optional &mdash; a bare <code>[IDENFY_KYB]</code> uses your iDenfy account defaults.', 'idenfy' ) ); ?></p>

					<h4><?php esc_html_e( 'Label &amp; track the session', 'idenfy' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Identify each session and tie it back to your own records.', 'idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>client_id</code> &mdash; <?php esc_html_e( 'identifier shown in your iDenfy dashboard (max 100 chars). Auto-generated if omitted.', 'idenfy' ); ?></li>
						<li><code>external_ref</code> &mdash; <?php esc_html_e( 'your own correlation ID, e.g. an order number (max 40 chars).', 'idenfy' ); ?></li>
						<li><code>tags</code> &mdash; <?php echo wp_kses_post( __( 'comma-separated labels (max 5, 32 chars each), e.g. <code>tags="checkout,premium"</code>.', 'idenfy' ) ); ?></li>
					</ul>

					<h4><?php esc_html_e( 'Connect it to your page', 'idenfy' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Hook the verification into elements on your page.', 'idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>on_complete_enable</code> &mdash; <?php echo wp_kses_post( __( 'CSS selector of an element to enable when verification finishes (e.g. a "Next" button). For a link or any non-form element, add the <code>idenfy-disabled</code> class to it &mdash; it will be greyed out and unclickable until verification finishes.', 'idenfy' ) ); ?></li>
						<li><code>sync_field</code> &mdash; <?php echo wp_kses_post( __( 'CSS selector of a hidden input to set to <code>"completed"</code> when verification finishes &mdash; useful for form-plugin server-side validation.', 'idenfy' ) ); ?></li>
						<li><code>hide_on_complete="true"</code> &mdash; <?php esc_html_e( 'Auto-close the modal after the user finishes. Off by default (a Close button is shown instead).', 'idenfy' ); ?></li>
						<li><code>hide_button_on_complete="true"</code> &mdash; <?php esc_html_e( 'Hide the button after a successful verification so the visitor can\'t start another. Has no effect if a success redirect is set.', 'idenfy' ); ?></li>
						<li><code>close_button_text</code> &mdash; <?php esc_html_e( 'Custom label for the Close button. Defaults to "Close".', 'idenfy' ); ?></li>
						<li><code>button_text</code> &mdash; <?php esc_html_e( 'Override the button label for this shortcode only. Defaults to the label set in the Customization tab.', 'idenfy' ); ?></li>
							<li><code>redirect</code> &mdash; <?php echo wp_kses_post( __( 'URL to send the visitor to after a successful verification, e.g. <code>redirect="/thank-you"</code>. Relative paths and full URLs both work.', 'idenfy' ) ); ?></li>
					</ul>

					<h4><?php esc_html_e( 'Choose the flow &amp; look', 'idenfy' ); ?></h4>
					<p class="description"><?php esc_html_e( 'Point a shortcode at a specific flow, theme, or questionnaire. Leave them off to use your iDenfy account defaults.', 'idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>flow</code>, <code>theme</code>, <code>lifetime</code> &mdash; <?php esc_html_e( 'set the flow UUID, branding theme UUID, or token lifetime (seconds) for this shortcode.', 'idenfy' ); ?></li>
						<li><code>questionnaire</code>, <code>questionnaire_required="true|false"</code> &mdash; <?php esc_html_e( 'set the questionnaire for this shortcode. Ignored when a flow is set (the flow controls its own questionnaire).', 'idenfy' ); ?></li>
					</ul>

					<h3><?php esc_html_e( 'Examples', 'idenfy' ); ?></h3>

					<p><strong><?php esc_html_e( 'Basic &mdash; uses your iDenfy account defaults:', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB]</code></p>

					<p><strong><?php esc_html_e( 'Unlock a "Next" button in a multi-step form:', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB on_complete_enable=".gform_next_button"]</code></p>
					<p class="description"><?php echo wp_kses_post( __( 'Replace <code>.gform_next_button</code> with whatever your form plugin uses (e.g. <code>.wpforms-page-next</code> for WPForms).', 'idenfy' ) ); ?></p>

					<p><strong><?php esc_html_e( 'Block form submission until verified:', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB sync_field="input[name=kyb_status]"]</code></p>
					<p class="description"><?php echo wp_kses_post( __( 'Add a required hidden field <code>&lt;input type="hidden" name="kyb_status" required&gt;</code> to your form. The plugin sets its value to <code>"completed"</code> on success.', 'idenfy' ) ); ?></p>

					<p><strong><?php esc_html_e( 'Two different KYB flows on the same site:', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB flow="standard-kyb-uuid"]</code> <?php esc_html_e( 'on one page, and', 'idenfy' ); ?> <code>[IDENFY_KYB flow="sole-proprietor-uuid"]</code> <?php esc_html_e( 'on another.', 'idenfy' ); ?></p>

					<p><strong><?php esc_html_e( 'Tie a session to an order:', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB external_ref="order-12345" tags="checkout,premium"]</code></p>
					<p class="description"><?php esc_html_e( 'The external reference and tags show up in your iDenfy dashboard, making it easier to match KYB sessions back to records in your own system.', 'idenfy' ); ?></p>

					<p><strong><?php esc_html_e( 'Auto-hide iframe after completion (no Close button):', 'idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB hide_on_complete="true"]</code></p>

						<p><strong><?php esc_html_e( 'Send the visitor to a thank-you page when they finish:', 'idenfy' ); ?></strong></p>
						<p><code>[IDENFY_KYB redirect="/thank-you"]</code></p>

					<h3><?php esc_html_e( 'Run your own code when it finishes', 'idenfy' ); ?></h3>
					<p class="description"><?php echo wp_kses_post( __( 'Need more than the attributes above? Listen for the <code>idenfy:kyb:complete</code> event on the button &mdash; it fires on both success and failure:', 'idenfy' ) ); ?></p>
					<pre class="wp-idenfy-code"><code>document.querySelector('a.idenfy-kyb-button').addEventListener('idenfy:kyb:complete', function(e) {
    if (e.detail.status === 'success') {
        // your code here — analytics, a custom message, etc.
    } else if (e.detail.status === 'failed') {
        // handle failure
    }
    // e.detail.raw holds the full message from the iframe
});</code></pre>
					<p class="description"><?php echo wp_kses_post( __( 'The event bubbles, so you can also listen on <code>document</code>.', 'idenfy' ) ); ?></p>
				</div>
			</div>
		</div>

		<div class="tab-panel <?php echo $active_tab === 'customization' ? 'is-active' : ''; ?>" data-tab="customization">
			<p class="description wp-idenfy-cust-intro"><?php esc_html_e( 'Customize the KYC and KYB verification buttons independently. Each preview updates as you type.', 'idenfy' ); ?></p>
			<?php
			$cust_sections = array(
				'kyc' => array(
					'heading'   => __( 'KYC button', 'idenfy' ),
					'shortcode' => '[IDENFY]',
					'btn_class' => 'idenfy-button',
					'preview'   => 'wp-idenfy-preview-button',
					'prefix'    => 'wp-idenfy-',
					'data'      => $this->get_customization( 'kyc' ),
				),
				'kyb' => array(
					'heading'   => __( 'KYB button', 'idenfy' ),
					'shortcode' => '[IDENFY_KYB]',
					'btn_class' => 'idenfy-kyb-button',
					'preview'   => 'wp-idenfy-kyb-preview-button',
					'prefix'    => 'wp-idenfy-kyb-',
					'data'      => $this->get_customization( 'kyb' ),
				),
			);
			?>
			<div class="wp-idenfy-cust-switch" role="tablist">
				<?php $first = true; foreach ( $cust_sections as $ctype => $sec ) : ?>
					<button type="button" role="tab" class="wp-idenfy-cust-switch-btn <?php echo $first ? 'is-active' : ''; ?>" data-cust-target="<?php echo esc_attr( $ctype ); ?>" aria-selected="<?php echo $first ? 'true' : 'false'; ?>"><?php echo esc_html( $sec['heading'] ); ?></button>
				<?php $first = false; endforeach; ?>
			</div>
			<?php
			$first = true;
			foreach ( $cust_sections as $ctype => $sec ) :
				$cd = $sec['data'];
				$p  = $sec['prefix'];
			?>
			<div class="wp-idenfy-cust-section <?php echo $first ? 'is-active' : ''; ?>" id="wp-idenfy-cust-<?php echo esc_attr( $ctype ); ?>" data-cust-type="<?php echo esc_attr( $ctype ); ?>">
				<h2 class="wp-idenfy-cust-title">
					<?php echo esc_html( $sec['heading'] ); ?>
					<code class="shortcode-copy" title="<?php esc_attr_e( 'Click to copy', 'idenfy' ); ?>"><?php echo esc_html( $sec['shortcode'] ); ?></code>
				</h2>
				<div class="wp-idenfy-card-grid">
					<div class="wp-idenfy-card-form">
						<div class="wp-idenfy-form-box">
							<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_save_customization' ) ); ?>" method="POST" class="wp-idenfy-customization-form" data-cust-type="<?php echo esc_attr( $ctype ); ?>">
								<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>
								<input type="hidden" name="type" value="<?php echo esc_attr( $ctype ); ?>">
								<div class="wp-idenfy-field">
									<label for="<?php echo esc_attr( $p ); ?>button-text"><?php esc_html_e( 'Button text', 'idenfy' ); ?></label>
									<input type="text" id="<?php echo esc_attr( $p ); ?>button-text" name="button_text" value="<?php echo esc_attr( $cd['button_text'] ); ?>" required>
								</div>
								<div class="wp-idenfy-field-row">
									<div class="wp-idenfy-field">
										<label for="<?php echo esc_attr( $p ); ?>bg-color"><?php esc_html_e( 'Background color', 'idenfy' ); ?></label>
										<input type="color" id="<?php echo esc_attr( $p ); ?>bg-color" name="bg_color" value="<?php echo esc_attr( $cd['bg_color'] ); ?>">
									</div>
									<div class="wp-idenfy-field">
										<label for="<?php echo esc_attr( $p ); ?>text-color"><?php esc_html_e( 'Text color', 'idenfy' ); ?></label>
										<input type="color" id="<?php echo esc_attr( $p ); ?>text-color" name="text_color" value="<?php echo esc_attr( $cd['text_color'] ); ?>">
									</div>
								</div>
								<div class="wp-idenfy-field-row">
									<div class="wp-idenfy-field">
										<label for="<?php echo esc_attr( $p ); ?>border-radius"><?php esc_html_e( 'Border radius (px)', 'idenfy' ); ?></label>
										<input type="number" id="<?php echo esc_attr( $p ); ?>border-radius" name="border_radius" value="<?php echo esc_attr( $cd['border_radius'] ); ?>" min="0" max="100">
									</div>
									<div class="wp-idenfy-field">
										<label for="<?php echo esc_attr( $p ); ?>font-size"><?php esc_html_e( 'Font size (px)', 'idenfy' ); ?></label>
										<input type="number" id="<?php echo esc_attr( $p ); ?>font-size" name="font_size" value="<?php echo esc_attr( $cd['font_size'] ); ?>" min="8" max="64">
									</div>
								</div>
								<div class="wp-idenfy-field-row">
									<div class="wp-idenfy-field">
										<label for="<?php echo esc_attr( $p ); ?>padding-y"><?php esc_html_e( 'Padding vertical (px)', 'idenfy' ); ?></label>
										<input type="number" id="<?php echo esc_attr( $p ); ?>padding-y" name="padding_y" value="<?php echo esc_attr( $cd['padding_y'] ); ?>" min="0" max="100">
									</div>
									<div class="wp-idenfy-field">
										<label for="<?php echo esc_attr( $p ); ?>padding-x"><?php esc_html_e( 'Padding horizontal (px)', 'idenfy' ); ?></label>
										<input type="number" id="<?php echo esc_attr( $p ); ?>padding-x" name="padding_x" value="<?php echo esc_attr( $cd['padding_x'] ); ?>" min="0" max="200">
									</div>
								</div>
								<div class="wp-idenfy-field wp-idenfy-field-code">
									<label for="<?php echo esc_attr( $p ); ?>advanced-css"><?php esc_html_e( 'Advanced CSS', 'idenfy' ); ?></label>
									<textarea id="<?php echo esc_attr( $p ); ?>advanced-css" name="advanced_css" rows="14"><?php echo esc_textarea( $cd['advanced_css'] ); ?></textarea>
									<p class="description"><?php esc_html_e( 'The current button stylesheet. On save, the fields above and this CSS are reconciled: edits made here are pulled into the fields, and edits made in the fields are written back into this CSS.', 'idenfy' ); ?></p>
								</div>
								<p class="submit">
									<button type="submit" class="button button-primary"><?php esc_html_e( 'Save Changes', 'idenfy' ); ?></button>
								</p>
							</form>
						</div>
					</div>
					<div class="wp-idenfy-card-image wp-idenfy-preview-area">
						<h3 class="wp-idenfy-preview-heading"><?php esc_html_e( 'Preview', 'idenfy' ); ?></h3>
						<a href="#" id="<?php echo esc_attr( $sec['preview'] ); ?>" class="<?php echo esc_attr( $sec['btn_class'] ); ?>"><?php echo esc_html( $cd['button_text'] ); ?><i class="ajax-loader" aria-hidden="true"></i></a>
					</div>
				</div>
			</div>
			<?php $first = false; endforeach; ?>
		</div>
	</div>
</div>
