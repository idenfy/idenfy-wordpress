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
$kyb             = $this->get_kyb_options();
// KYB form UI currently ships translations for these languages only.
// See https://documentation.idenfy.com/resources/supported-languages
$kyb_locales = array(
	''   => __( 'Auto (by user IP)', 'wp-idenfy' ),
	'en' => 'English',
	'lt' => 'Lietuvių',
	'nl' => 'Nederlands',
	'de' => 'Deutsch',
	'fr' => 'Français',
	'pt' => 'Português',
);
?>
<div class="wrap">
	<h1><?php _e( 'iDenfy', 'wp-idenfy' ); ?></h1>

	<?php if ( isset( $_GET['error'] ) && $_GET['error'] === 'invalid_credentials' ) : ?>
		<div class="notice notice-error">
			<p><?php _e( 'The API KEY and API SECRET are incorrect. Please verify and try again.', 'wp-idenfy' ); ?></p>
		</div>
	<?php endif; ?>

	<?php if ( isset( $_GET['saved'] ) && $_GET['saved'] === '1' ) :
		$saved_msg = esc_html__( 'API credentials saved successfully.', 'wp-idenfy' );
		if ( $active_tab === 'customization' ) {
			$saved_msg = esc_html__( 'Customization saved successfully.', 'wp-idenfy' );
		} elseif ( $active_tab === 'kyb' ) {
			$saved_msg = esc_html__( 'KYB settings saved successfully.', 'wp-idenfy' );
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
						<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_sapis' ) ); ?>" method="POST">
							<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-api-key"><?php _e( 'API KEY', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-api-key" name="api_key" value="<?php echo esc_attr( $this->get_option( 'api_key' ) ); ?>" autocomplete="off" required>
							</div>
							<div class="wp-idenfy-field">
								<label for="wp-idenfy-api-secret"><?php _e( 'API SECRET', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-api-secret" name="api_secret" value="<?php echo esc_attr( $this->get_option( 'api_secret' ) ); ?>" autocomplete="off" required>
							</div>
							<p class="submit">
								<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'wp-idenfy' ); ?></button>
							</p>
						</form>
					</div>
				</div>
				<div class="wp-idenfy-card-image">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="">
				</div>
			</div>
		</div>

		<div class="tab-panel <?php echo $active_tab === 'kyc' ? 'is-active' : ''; ?>" data-tab="kyc">
			<div class="wp-idenfy-card-grid">
				<div class="wp-idenfy-card-form">
					<div class="wp-idenfy-form-box">
						<h2><?php _e( 'KYC Verification', 'wp-idenfy' ); ?></h2>
						<p class="description"><?php _e( 'Use this shortcode to add an identity verification button to any page or post:', 'wp-idenfy' ); ?></p>
						<p><code class="shortcode-copy" title="<?php esc_attr_e( 'Click to copy', 'wp-idenfy' ); ?>">[IDENFY]</code></p>
					</div>
				</div>
				<div class="wp-idenfy-card-image">
					<img src="<?php echo esc_url( $logo_url ); ?>" alt="">
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

						<hr>

						<h3><?php _e( 'Defaults', 'wp-idenfy' ); ?></h3>
						<p class="description"><?php _e( 'These values are used when the shortcode is rendered without overriding attributes.', 'wp-idenfy' ); ?></p>

						<form action="<?php echo esc_url( admin_url( 'admin-post.php?action=wp_idenfy_save_kyb' ) ); ?>" method="POST">
							<?php wp_nonce_field( WP_IDENFY_NONCE_BN, WP_IDENFY_NONCE_KEY ); ?>

							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyb-flow"><?php _e( 'Flow ID', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyb-flow" name="flow" value="<?php echo esc_attr( $kyb['flow'] ); ?>" placeholder="00000000-0000-0000-0000-000000000000" autocomplete="off">
								<p class="description"><?php _e( 'UUID of the custom KYB flow from your iDenfy dashboard. Leave empty to use your account default.', 'wp-idenfy' ); ?></p>
							</div>

							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyb-theme"><?php _e( 'Theme ID', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyb-theme" name="theme" value="<?php echo esc_attr( $kyb['theme'] ); ?>" placeholder="00000000-0000-0000-0000-000000000000" autocomplete="off">
								<p class="description"><?php _e( 'Optional. UUID of the KYB branding theme.', 'wp-idenfy' ); ?></p>
							</div>

							<div class="wp-idenfy-field-row">
								<div class="wp-idenfy-field">
									<label for="wp-idenfy-kyb-lifetime"><?php _e( 'Token lifetime (seconds)', 'wp-idenfy' ); ?></label>
									<input type="number" id="wp-idenfy-kyb-lifetime" name="lifetime" value="<?php echo esc_attr( $kyb['lifetime'] ); ?>" min="60" max="2592000">
									<p class="description"><?php _e( 'Default 3600 (1 hour). Max 2592000 (30 days).', 'wp-idenfy' ); ?></p>
								</div>

								<div class="wp-idenfy-field">
									<label for="wp-idenfy-kyb-locale"><?php _e( 'Default language', 'wp-idenfy' ); ?></label>
									<select id="wp-idenfy-kyb-locale" name="locale">
										<?php foreach ( $kyb_locales as $code => $label ) : ?>
											<option value="<?php echo esc_attr( $code ); ?>" <?php selected( $kyb['locale'], $code ); ?>><?php echo esc_html( $label ); ?></option>
										<?php endforeach; ?>
									</select>
									<p class="description"><?php _e( 'KYB form translations are limited to these languages. Unsupported languages fall back to English.', 'wp-idenfy' ); ?></p>
								</div>
							</div>

							<div class="wp-idenfy-field">
								<label for="wp-idenfy-kyb-questionnaire"><?php _e( 'Questionnaire key', 'wp-idenfy' ); ?></label>
								<input type="text" id="wp-idenfy-kyb-questionnaire" name="questionnaire" value="<?php echo esc_attr( $kyb['questionnaire'] ); ?>" autocomplete="off">
								<p class="description"><?php _e( 'Optional. Ignored when a Flow ID is set (the flow controls its own questionnaire).', 'wp-idenfy' ); ?></p>
							</div>

							<div class="wp-idenfy-field">
								<label>
									<input type="checkbox" name="questionnaire_required" value="1" <?php checked( ! empty( $kyb['questionnaire_required'] ) ); ?>>
									<?php _e( 'Questionnaire required', 'wp-idenfy' ); ?>
								</label>
							</div>

							<p class="submit">
								<button type="submit" class="button button-primary"><?php _e( 'Save Changes', 'wp-idenfy' ); ?></button>
							</p>
						</form>
					</div>
				</div>
				<div class="wp-idenfy-card-docs">
					<h3><?php _e( 'How it works', 'wp-idenfy' ); ?></h3>
					<p class="description"><?php _e( 'When the page loads, the shortcode renders a container, calls the iDenfy KYB token endpoint server-side using the credentials and defaults above, then swaps the container for an iframe. When the visitor finishes verification, the plugin can enable a "Next" button, set a hidden field, or fire a JavaScript event &mdash; your choice.', 'wp-idenfy' ); ?></p>
					<p class="description"><?php _e( 'A bare <code>[IDENFY_KYB]</code> is enough for most sites. Add attributes only when you need per-session data (like an order ID), frontend wiring (a button selector), or a different value than the defaults above (different flow on a specific page).', 'wp-idenfy' ); ?></p>

					<h3><?php _e( 'Shortcode attributes', 'wp-idenfy' ); ?></h3>

					<h4><?php _e( 'Per-session data', 'wp-idenfy' ); ?></h4>
					<p class="description"><?php _e( 'These can\'t be admin defaults &mdash; they\'re different per page or per visitor.', 'wp-idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>client_id</code> &mdash; <?php _e( 'identifier shown in your iDenfy dashboard (max 100 chars). Auto-generated if omitted.', 'wp-idenfy' ); ?></li>
						<li><code>external_ref</code> &mdash; <?php _e( 'your own correlation ID, e.g. an order number (max 40 chars).', 'wp-idenfy' ); ?></li>
						<li><code>tags</code> &mdash; <?php _e( 'comma-separated, max 5 tags, 32 chars each.', 'wp-idenfy' ); ?></li>
					</ul>

					<h4><?php _e( 'Frontend behavior', 'wp-idenfy' ); ?></h4>
					<p class="description"><?php _e( 'These wire the iframe to your page\'s DOM, so they have to be set per shortcode.', 'wp-idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>on_complete_enable</code> &mdash; <?php _e( 'CSS selector of an element to enable when verification finishes (e.g. a "Next" button).', 'wp-idenfy' ); ?></li>
						<li><code>sync_field</code> &mdash; <?php _e( 'CSS selector of a hidden input to set to <code>"completed"</code> when verification finishes &mdash; useful for form-plugin server-side validation.', 'wp-idenfy' ); ?></li>
						<li><code>hide_on_complete="true"</code> &mdash; <?php _e( 'Auto-hide the iframe after the user finishes. Off by default (a Close button is shown instead).', 'wp-idenfy' ); ?></li>
						<li><code>close_button_text</code> &mdash; <?php _e( 'Custom label for the Close button. Defaults to "Close".', 'wp-idenfy' ); ?></li>
					</ul>

					<h4><?php _e( 'Default overrides (rarely needed)', 'wp-idenfy' ); ?></h4>
					<p class="description"><?php _e( 'Use these only if a specific page needs different values than the defaults on the left. Most sites use one set of defaults everywhere.', 'wp-idenfy' ); ?></p>
					<ul class="wp-idenfy-attr-list">
						<li><code>flow</code>, <code>theme</code>, <code>locale</code>, <code>lifetime</code> &mdash; <?php _e( 'override the corresponding defaults.', 'wp-idenfy' ); ?></li>
						<li><code>questionnaire</code>, <code>questionnaire_required="true|false"</code> &mdash; <?php _e( 'override the questionnaire defaults. Ignored when a flow is set (the flow controls its own questionnaire).', 'wp-idenfy' ); ?></li>
					</ul>

					<h3><?php _e( 'Examples', 'wp-idenfy' ); ?></h3>

					<p><strong><?php _e( 'Basic &mdash; uses all defaults:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB]</code></p>

					<p><strong><?php _e( 'Inside a multi-step form &mdash; enable a "Next" button when verification completes:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB on_complete_enable=".gform_next_button"]</code></p>
					<p class="description"><?php _e( 'Replace <code>.gform_next_button</code> with whatever your form plugin uses (e.g. <code>.wpforms-page-next</code> for WPForms).', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Hidden field bridging &mdash; let form-plugin validation block submission until verified:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB sync_field="input[name=kyb_status]"]</code></p>
					<p class="description"><?php _e( 'Add a required hidden field <code>&lt;input type="hidden" name="kyb_status" required&gt;</code> to your form. The plugin sets its value to <code>"completed"</code> on success.', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Two different KYB flows on the same site:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB flow="standard-kyb-uuid"]</code> <?php _e( 'on one page, and', 'wp-idenfy' ); ?> <code>[IDENFY_KYB flow="sole-proprietor-uuid"]</code> <?php _e( 'on another.', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Order correlation:', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB external_ref="order-12345" tags="checkout,premium"]</code></p>
					<p class="description"><?php _e( 'The external reference and tags show up in your iDenfy dashboard, making it easier to match KYB sessions back to records in your own system.', 'wp-idenfy' ); ?></p>

					<p><strong><?php _e( 'Auto-hide iframe after completion (no Close button):', 'wp-idenfy' ); ?></strong></p>
					<p><code>[IDENFY_KYB hide_on_complete="true"]</code></p>

					<h3><?php _e( 'Custom JavaScript', 'wp-idenfy' ); ?></h3>
					<p class="description"><?php _e( 'For anything not covered by the attributes above, listen for the <code>idenfy:kyb:complete</code> DOM event on the container:', 'wp-idenfy' ); ?></p>
					<pre class="wp-idenfy-code"><code>document.querySelector('.idenfy-kyb').addEventListener('idenfy:kyb:complete', function(e) {
    if (e.detail.status === 'success') {
        // your custom logic
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
