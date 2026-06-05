<?php
/*
Plugin Name: iDenfy
Description: Enables iDenfy identity verification for Wordpress.
Version:     1.1.0
Author:      www.idenfy.com
Text Domain: wp-idenfy
*/

defined( 'ABSPATH' ) or die;

define( 'WP_IDENFY_VER', '1.1.0' );
define( 'WP_IDENFY_FILE', __FILE__ );
define( 'WP_IDENFY_DIR_PATH', plugin_dir_path( __FILE__ ) );
define( 'WP_IDENFY_NONCE_BN', basename(__FILE__) );
define( 'WP_IDENFY_NONCE_KEY', 'wp_idenfy_nonce' );
define( 'WP_IDENFY_REGISTER_URL', 'https://www.idenfy.com/get-started/?source=wordpress' );
define( 'WP_IDENFY_ENDPOINT_URL', 'https://ivs.idenfy.com/api/v2/token' );
define( 'WP_IDENFY_KYB_ENDPOINT_URL', 'https://ivs.idenfy.com/kyb/tokens/' );

if ( ! class_exists( 'WP_Idenfy' ) ) {
	class WP_Idenfy {
		public static function get_instance() {
			if ( self::$instance === null ) {
				self::$instance = new self();
			}
			return self::$instance;
		}

		private $options = null;
		private $options_name = 'wp_idenfy_options';
		private $customization_option_name = 'wp_idenfy_customization';
		private $customization_kyb_option_name = 'wp_idenfy_customization_kyb';
		private $customizations = array();
		private $kyc_option_name = 'wp_idenfy_kyc';
		private $kyc_settings = null;
		private static $instance = null;

		private function __clone() { }

		public function __wakeup() {
			throw new \Exception( 'Cannot unserialize ' . __CLASS__ );
		}

		private function __construct() {
			// WP Hooks
			add_action( 'admin_menu', array( $this, 'add_admin_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
			add_action( 'admin_post_wp_idenfy_sapis', array( $this, 'save_api_settings' ) );
			add_action( 'wp_ajax_wp_idenfy_save_api', array( $this, 'ajax_save_api' ) );
			add_action( 'admin_post_wp_idenfy_save_customization', array( $this, 'save_customization' ) );
			add_action( 'admin_post_wp_idenfy_save_kyc', array( $this, 'save_kyc' ) );
			add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_assets' ) );
			add_action( 'wp_ajax_wp_idenfy_get_kyc_token', array( $this, 'ajax_get_kyc_token' ) );
			add_action( 'wp_ajax_nopriv_wp_idenfy_get_kyc_token', array( $this, 'ajax_get_kyc_token' ) );
			add_action( 'wp_ajax_wp_idenfy_get_kyb_token', array( $this, 'ajax_get_kyb_token' ) );
			add_action( 'wp_ajax_nopriv_wp_idenfy_get_kyb_token', array( $this, 'ajax_get_kyb_token' ) );
			add_filter( 'submenu_file', array( $this, 'highlight_submenu' ) );

			// Shortcodes
			add_shortcode( 'IDENFY', array( $this, 'output_shortcode' ) );
			add_shortcode( 'IDENFY_KYB', array( $this, 'output_kyb_shortcode' ) );
		}

		public function add_admin_menu() {
			$icon_path = WP_IDENFY_DIR_PATH . 'images/icon.svg';
			$menu_icon = file_exists( $icon_path )
				? 'data:image/svg+xml;base64,' . base64_encode( file_get_contents( $icon_path ) )
				: 'dashicons-id';

			add_menu_page(
				__( 'iDenfy', 'wp-idenfy' ),
				__( 'iDenfy', 'wp-idenfy' ),
				'manage_options',
				'wp-idenfy',
				array( $this, 'render_settings' ),
				$menu_icon
			);
			add_submenu_page(
				'wp-idenfy',
				__( 'Settings', 'wp-idenfy' ),
				__( 'Settings', 'wp-idenfy' ),
				'manage_options',
				'wp-idenfy',
				array( $this, 'render_settings' )
			);

			global $submenu;
			$submenu['wp-idenfy'][] = array(
				__( 'KYC', 'wp-idenfy' ),
				'manage_options',
				'admin.php?page=wp-idenfy&tab=kyc',
			);
			$submenu['wp-idenfy'][] = array(
				__( 'KYB', 'wp-idenfy' ),
				'manage_options',
				'admin.php?page=wp-idenfy&tab=kyb',
			);
			$submenu['wp-idenfy'][] = array(
				__( 'Customization', 'wp-idenfy' ),
				'manage_options',
				'admin.php?page=wp-idenfy&tab=customization',
			);
		}

		public function highlight_submenu( $submenu_file ) {
			if ( isset( $_GET['page'] ) && $_GET['page'] === 'wp-idenfy' && isset( $_GET['tab'] ) ) {
				$tab = sanitize_key( $_GET['tab'] );
				if ( in_array( $tab, array( 'kyc', 'kyb', 'customization' ), true ) ) {
					return 'admin.php?page=wp-idenfy&tab=' . $tab;
				}
			}
			return $submenu_file;
		}

		private function customization_types() {
			return array(
				'kyc' => array(
					'option'      => $this->customization_option_name,
					'selector'    => 'a.idenfy-button',
					'button_text' => __( 'Verify me', 'wp-idenfy' ),
				),
				'kyb' => array(
					'option'      => $this->customization_kyb_option_name,
					'selector'    => 'a.idenfy-kyb-button',
					'button_text' => __( 'Verify business', 'wp-idenfy' ),
				),
			);
		}

		public function get_customization( $type = 'kyc' ) {
			$type  = ( $type === 'kyb' ) ? 'kyb' : 'kyc';
			if ( ! isset( $this->customizations[ $type ] ) ) {
				$types       = $this->customization_types();
				$conf        = $types[ $type ];
				$defaults = array(
					'button_text'   => $conf['button_text'],
					'bg_color'      => '#445deb',
					'text_color'    => '#ffffff',
					'border_radius' => 10,
					'padding_y'     => 15,
					'padding_x'     => 20,
					'font_size'     => 14,
					'advanced_css'  => $this->default_button_css( $conf['selector'] ),
				);
				$saved = (array) get_option( $conf['option'], array() );
				$this->customizations[ $type ] = array_merge( $defaults, $saved );
			}
			return $this->customizations[ $type ];
		}

		private function default_button_css( $selector ) {
			$style_path = WP_IDENFY_DIR_PATH . 'css/style.css';
			$css        = file_exists( $style_path ) ? file_get_contents( $style_path ) : '';
			return $this->extract_css_rule( $css, $selector );
		}

		private function extract_css_rule( $css, $selector ) {
			$pattern = '/' . preg_quote( $selector, '/' ) . '[^{]*\{[\s\S]*?\}/i';
			if ( preg_match( $pattern, (string) $css, $m ) ) return $m[0];
			return '';
		}

		public function get_kyc_settings() {
			if ( is_null( $this->kyc_settings ) ) {
				$defaults = array(
					'accept_suspected'    => 0,
					'accept_unverified'   => 0,
					'redirect'            => '',
					'redirect_failed'     => '',
					'redirect_unverified' => '',
					'close_button_text'   => '',
					'hide_on_complete'    => 0,
					'hide_button_on_complete' => 0,
				);
				$saved = (array) get_option( $this->kyc_option_name, array() );
				$this->kyc_settings = array_merge( $defaults, $saved );
			}
			return $this->kyc_settings;
		}

		public function save_customization() {
			if ( empty( $_POST[ WP_IDENFY_NONCE_KEY ] ) || ! wp_verify_nonce( $_POST[ WP_IDENFY_NONCE_KEY ], WP_IDENFY_NONCE_BN ) ) {
				wp_die( __( 'Invalid request', 'wp-idenfy' ) );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Insufficient permissions', 'wp-idenfy' ) );
			}

			$type     = ( isset( $_POST['type'] ) && $_POST['type'] === 'kyb' ) ? 'kyb' : 'kyc';
			$types    = $this->customization_types();
			$conf     = $types[ $type ];
			$selector = $conf['selector'];

			$saved = $this->get_customization( $type );

			$button_text = isset( $_POST['button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['button_text'] ) ) : $conf['button_text'];
			if ( $button_text === '' ) $button_text = $conf['button_text'];

			$bg_color   = isset( $_POST['bg_color'] ) ? sanitize_hex_color( $_POST['bg_color'] ) : '';
			$bg_color   = $bg_color ? $bg_color : '#445deb';
			$text_color = isset( $_POST['text_color'] ) ? sanitize_hex_color( $_POST['text_color'] ) : '';
			$text_color = $text_color ? $text_color : '#ffffff';
			$radius     = isset( $_POST['border_radius'] ) ? min( 100, absint( $_POST['border_radius'] ) ) : 10;
			$padding_y  = isset( $_POST['padding_y'] ) ? min( 100, absint( $_POST['padding_y'] ) ) : 15;
			$padding_x  = isset( $_POST['padding_x'] ) ? min( 200, absint( $_POST['padding_x'] ) ) : 20;
			$font_size  = isset( $_POST['font_size'] ) ? min( 64, max( 8, absint( $_POST['font_size'] ) ) ) : 14;
			$advanced   = isset( $_POST['advanced_css'] ) ? wp_strip_all_tags( wp_unslash( $_POST['advanced_css'] ) ) : '';

			list( $bg_color, $advanced ) = $this->reconcile_color( $saved['advanced_css'], $advanced, $saved['bg_color'], $bg_color, 'background-color', $selector, array( 'border-color' ) );

			list( $text_color, $advanced ) = $this->reconcile_color( $saved['advanced_css'], $advanced, $saved['text_color'], $text_color, 'color', $selector );

			list( $radius, $advanced ) = $this->reconcile_int( $saved['advanced_css'], $advanced, $saved['border_radius'], $radius, 'border-radius', $selector, 0, 100 );

			$css_pad_saved  = $this->parse_padding( $this->extract_css_property( $saved['advanced_css'], 'padding', $selector ) );
			$css_pad_posted = $this->parse_padding( $this->extract_css_property( $advanced, 'padding', $selector ) );
			$css_pad_changed = ( $css_pad_saved !== $css_pad_posted );
			$field_pad_changed = ( $padding_y !== (int) $saved['padding_y'] || $padding_x !== (int) $saved['padding_x'] );
			if ( $css_pad_changed && $css_pad_posted !== null ) {
				$padding_y = min( 100, max( 0, $css_pad_posted[0] ) );
				$padding_x = min( 200, max( 0, $css_pad_posted[1] ) );
			} elseif ( $field_pad_changed ) {
				$advanced = $this->set_css_property( $advanced, 'padding', $padding_y . 'px ' . $padding_x . 'px', $selector );
			}

			list( $font_size, $advanced ) = $this->reconcile_int( $saved['advanced_css'], $advanced, $saved['font_size'], $font_size, 'font-size', $selector, 8, 64 );

			update_option( $conf['option'], array(
				'button_text'   => $button_text,
				'bg_color'      => $bg_color,
				'text_color'    => $text_color,
				'border_radius' => $radius,
				'padding_y'     => $padding_y,
				'padding_x'     => $padding_x,
				'font_size'     => $font_size,
				'advanced_css'  => $advanced,
			) );
			unset( $this->customizations[ $type ] );

			wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=customization&saved=1#wp-idenfy-cust-' . $type ) );
			die;
		}

		public function save_kyc() {
			if ( empty( $_POST[ WP_IDENFY_NONCE_KEY ] ) || ! wp_verify_nonce( $_POST[ WP_IDENFY_NONCE_KEY ], WP_IDENFY_NONCE_BN ) ) {
				wp_die( __( 'Invalid request', 'wp-idenfy' ) );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Insufficient permissions', 'wp-idenfy' ) );
			}

			update_option( $this->kyc_option_name, array(
				'accept_suspected'    => isset( $_POST['accept_suspected'] ) ? 1 : 0,
				'accept_unverified'   => isset( $_POST['accept_unverified'] ) ? 1 : 0,
				'redirect'            => isset( $_POST['redirect'] ) ? esc_url_raw( wp_unslash( $_POST['redirect'] ) ) : '',
				'redirect_failed'     => isset( $_POST['redirect_failed'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_failed'] ) ) : '',
				'redirect_unverified' => isset( $_POST['redirect_unverified'] ) ? esc_url_raw( wp_unslash( $_POST['redirect_unverified'] ) ) : '',
				'close_button_text'   => isset( $_POST['close_button_text'] ) ? sanitize_text_field( wp_unslash( $_POST['close_button_text'] ) ) : '',
				'hide_on_complete'    => isset( $_POST['hide_on_complete'] ) ? 1 : 0,
				'hide_button_on_complete' => isset( $_POST['hide_button_on_complete'] ) ? 1 : 0,
			) );
			$this->kyc_settings = null;

			wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=kyc&saved=kyc' ) );
			die;
		}

		private function reconcile_color( $saved_css, $posted_css, $saved_field, $posted_field, $css_prop, $selector, $also_sync = array() ) {
			$css_saved  = $this->normalize_hex_color( $this->extract_css_property( $saved_css, $css_prop, $selector ) );
			$css_posted = $this->normalize_hex_color( $this->extract_css_property( $posted_css, $css_prop, $selector ) );
			$css_changed   = ( $css_saved !== $css_posted );
			$field_changed = ( $posted_field !== $saved_field );

			if ( $css_changed && $css_posted ) {
				$posted_field = $css_posted;
			} elseif ( $field_changed ) {
				$posted_css = $this->set_css_property( $posted_css, $css_prop, $posted_field, $selector );
				foreach ( $also_sync as $extra_prop ) {
					$posted_css = $this->set_css_property( $posted_css, $extra_prop, $posted_field, $selector );
				}
			}
			return array( $posted_field, $posted_css );
		}

		private function reconcile_int( $saved_css, $posted_css, $saved_field, $posted_field, $css_prop, $selector, $min, $max ) {
			$css_saved  = $this->parse_px( $this->extract_css_property( $saved_css, $css_prop, $selector ) );
			$css_posted = $this->parse_px( $this->extract_css_property( $posted_css, $css_prop, $selector ) );
			$css_changed   = ( $css_saved !== $css_posted );
			$field_changed = ( $posted_field !== (int) $saved_field );

			if ( $css_changed && $css_posted !== null ) {
				$posted_field = min( $max, max( $min, $css_posted ) );
			} elseif ( $field_changed ) {
				$posted_css = $this->set_css_property( $posted_css, $css_prop, $posted_field . 'px', $selector );
			}
			return array( $posted_field, $posted_css );
		}

		private function normalize_hex_color( $value ) {
			if ( $value === null ) return null;
			$value = trim( (string) $value );
			if ( preg_match( '/^#[0-9a-f]{6}$/i', $value ) ) return strtolower( $value );
			if ( preg_match( '/^#([0-9a-f])([0-9a-f])([0-9a-f])$/i', $value, $m ) ) {
				return strtolower( '#' . $m[1] . $m[1] . $m[2] . $m[2] . $m[3] . $m[3] );
			}
			if ( preg_match( '/^rgb\s*\(\s*(\d+)\s*,\s*(\d+)\s*,\s*(\d+)\s*\)$/i', $value, $m ) ) {
				return sprintf( '#%02x%02x%02x', intval( $m[1] ), intval( $m[2] ), intval( $m[3] ) );
			}
			return null;
		}

		private function parse_px( $value ) {
			if ( $value === null || $value === '' ) return null;
			if ( preg_match( '/^(\d+(?:\.\d+)?)/', trim( $value ), $m ) ) return intval( $m[1] );
			return null;
		}

		private function parse_padding( $value ) {
			if ( $value === null || $value === '' ) return null;
			$parts = preg_split( '/\s+/', trim( $value ) );
			if ( ! $parts ) return null;
			$y = $this->parse_px( $parts[0] );
			if ( $y === null ) return null;
			$x = ( count( $parts ) > 1 ) ? $this->parse_px( $parts[1] ) : $y;
			return array( $y, $x !== null ? $x : $y );
		}

		private function extract_css_property( $css, $prop, $selector ) {
			if ( ! preg_match( '/' . preg_quote( $selector, '/' ) . '[^{]*\{([\s\S]*?)\}/i', (string) $css, $rule ) ) return null;
			$pattern = '/(?:^|;)\s*' . preg_quote( $prop, '/' ) . '\s*:\s*([^;}!]+?)(?:\s*!important)?\s*(?:;|$)/i';
			if ( preg_match( $pattern, $rule[1], $m ) ) return trim( $m[1] );
			return null;
		}

		private function set_css_property( $css, $prop, $new_value, $selector ) {
			if ( ! preg_match( '/(' . preg_quote( $selector, '/' ) . '[^{]*\{)([\s\S]*?)(\})/i', $css, $rule, PREG_OFFSET_CAPTURE ) ) {
				return $css;
			}
			$prefix_start = $rule[1][1];
			$prefix_end   = $prefix_start + strlen( $rule[1][0] );
			$declarations = $rule[2][0];
			$suffix_start = $rule[3][1];

			$pattern = '/((?:^|;)\s*' . preg_quote( $prop, '/' ) . '\s*:\s*)([^;}]+)(\s*;?)/i';
			if ( preg_match( $pattern, $declarations ) ) {
				$declarations = preg_replace_callback( $pattern, function( $m ) use ( $new_value ) {
					$has_imp = preg_match( '/!\s*important\s*$/i', rtrim( $m[2] ) );
					return $m[1] . $new_value . ( $has_imp ? ' !important' : '' ) . $m[3];
				}, $declarations, 1 );
			} else {
				$trimmed = rtrim( $declarations );
				if ( $trimmed !== '' && substr( $trimmed, -1 ) !== ';' && substr( $trimmed, -1 ) !== '{' ) {
					$trimmed .= ';';
				}
				$declarations = $trimmed . "\n\t" . $prop . ': ' . $new_value . " !important;\n";
			}
			return substr( $css, 0, $prefix_end ) . $declarations . substr( $css, $suffix_start );
		}

		public function render_settings() {
			require( __DIR__ . '/inc/admin/activate-api.php' );
		}

		public function enqueue_admin_assets( $hn ) {
			if ( strpos( $hn, 'wp-idenfy' ) === false ) return;
			$css_path    = WP_IDENFY_DIR_PATH . 'css/admin.css';
			$js_path     = WP_IDENFY_DIR_PATH . 'js/admin.js';
			$style_path  = WP_IDENFY_DIR_PATH . 'css/style.css';

			$cm_settings = wp_enqueue_code_editor( array( 'type' => 'text/css' ) );

			wp_enqueue_style( 'wp-idenfy-admin', plugins_url( 'css/admin.css', WP_IDENFY_FILE ), null, file_exists( $css_path ) ? filemtime( $css_path ) : WP_IDENFY_VER, 'all' );
			wp_enqueue_script( 'wp-idenfy-admin', plugins_url( 'js/admin.js', WP_IDENFY_FILE ), array( 'jquery' ), file_exists( $js_path ) ? filemtime( $js_path ) : WP_IDENFY_VER, true );
			wp_enqueue_style( 'wp-idenfy', plugins_url( 'css/style.css', WP_IDENFY_FILE ), null, file_exists( $style_path ) ? filemtime( $style_path ) : WP_IDENFY_VER, 'all' );

			wp_localize_script( 'wp-idenfy-admin', 'WPIdenfyAdminData', array(
				'codeEditor' => $cm_settings,
				'ajaxUrl'    => admin_url( 'admin-ajax.php' ),
				'i18n'       => array(
					'testing' => __( 'Verifying credentials…', 'wp-idenfy' ),
					'valid'   => __( 'Connected — credentials are valid.', 'wp-idenfy' ),
					'invalid' => __( 'The API KEY and API SECRET are incorrect.', 'wp-idenfy' ),
					'error'   => __( 'Could not reach iDenfy. Please try again.', 'wp-idenfy' ),
					'show'    => __( 'Show secret', 'wp-idenfy' ),
					'hide'    => __( 'Hide secret', 'wp-idenfy' ),
				),
			) );
		}

		public function save_api_settings() {
			if ( empty( $_POST[ WP_IDENFY_NONCE_KEY ] ) || ! wp_verify_nonce( $_POST[ WP_IDENFY_NONCE_KEY ], WP_IDENFY_NONCE_BN ) ) {
				wp_die( __( 'Invalid request', 'wp-idenfy' ) );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_die( __( 'Insufficient permissions', 'wp-idenfy' ) );
			}

			$api_key    = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
			$api_secret = isset( $_POST['api_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['api_secret'] ) ) : '';

			if ( $api_key === '' || $api_secret === '' ) wp_die( __( 'Invalid request', 'wp-idenfy' ) );

			$test = $this->test_credentials( $api_key, $api_secret );

			if ( $test['status'] === 'error' ) wp_die( esc_html( $test['message'] ) );

			if ( $test['status'] !== 'valid' ) {
				wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=settings&error=invalid_credentials' ) );
				die;
			}

			update_option( $this->options_name, array(
				'api_key'    => $api_key,
				'api_secret' => $api_secret,
				'uuid'       => 'wordpress-user-1',
				'token'      => $test['authToken'],
			) );
			$this->options = null;

			wp_redirect( admin_url( 'admin.php?page=wp-idenfy&tab=kyc&saved=1' ) );
			die;
		}

		private function test_credentials( $api_key, $api_secret ) {
			$result = $this->api_request( $api_key, $api_secret, 'wordpress-user-1' );

			if ( is_wp_error( $result ) ) {
				return array( 'status' => 'error', 'message' => $result->get_error_message() );
			}

			if ( property_exists( $result, 'identifier' ) && $result->identifier === 'UNAUTHORIZED' ) {
				return array( 'status' => 'invalid' );
			}

			if ( ! property_exists( $result, 'authToken' ) ) {
				return array( 'status' => 'invalid' );
			}

			return array( 'status' => 'valid', 'authToken' => $result->authToken );
		}

		public function ajax_save_api() {
			if ( empty( $_POST[ WP_IDENFY_NONCE_KEY ] ) || ! wp_verify_nonce( $_POST[ WP_IDENFY_NONCE_KEY ], WP_IDENFY_NONCE_BN ) ) {
				wp_send_json_error( array( 'status' => 'error', 'message' => __( 'Invalid request.', 'wp-idenfy' ) ) );
			}
			if ( ! current_user_can( 'manage_options' ) ) {
				wp_send_json_error( array( 'status' => 'error', 'message' => __( 'Insufficient permissions.', 'wp-idenfy' ) ) );
			}

			$api_key    = isset( $_POST['api_key'] ) ? sanitize_text_field( wp_unslash( $_POST['api_key'] ) ) : '';
			$api_secret = isset( $_POST['api_secret'] ) ? sanitize_text_field( wp_unslash( $_POST['api_secret'] ) ) : '';

			if ( $api_key === '' || $api_secret === '' ) {
				wp_send_json_error( array( 'status' => 'invalid', 'message' => __( 'Enter both the API KEY and API SECRET.', 'wp-idenfy' ) ) );
			}

			$test = $this->test_credentials( $api_key, $api_secret );

			if ( $test['status'] === 'error' ) {
				wp_send_json_error( array( 'status' => 'error', 'message' => $test['message'] ) );
			}

			if ( $test['status'] !== 'valid' ) {
				wp_send_json_error( array( 'status' => 'invalid', 'message' => __( 'The API KEY and API SECRET are incorrect.', 'wp-idenfy' ) ) );
			}

			update_option( $this->options_name, array(
				'api_key'    => $api_key,
				'api_secret' => $api_secret,
				'uuid'       => 'wordpress-user-1',
				'token'      => $test['authToken'],
			) );
			$this->options = null;

			wp_send_json_success( array( 'status' => 'valid', 'message' => __( 'Credentials saved and verified.', 'wp-idenfy' ) ) );
		}

		public function enqueue_assets() {
			$style_path = WP_IDENFY_DIR_PATH . 'css/style.css';
			$js_path    = WP_IDENFY_DIR_PATH . 'js/script.js';

			wp_enqueue_style( 'font-awesome', 'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css', null, WP_IDENFY_VER, 'all' );
			wp_enqueue_style( 'wp-idenfy', plugins_url( 'css/style.css', WP_IDENFY_FILE ), null, file_exists( $style_path ) ? filemtime( $style_path ) : WP_IDENFY_VER, 'all' );
			wp_add_inline_style( 'wp-idenfy', $this->build_button_css() );
			wp_enqueue_script( 'wp-idenfy', plugins_url( 'js/script.js', WP_IDENFY_FILE ), array( 'jquery' ), file_exists( $js_path ) ? filemtime( $js_path ) : WP_IDENFY_VER, true );
			wp_localize_script( 'wp-idenfy', 'WPIdenfyData', array(
				'ajaxUrl' => admin_url( 'admin-ajax.php' ),
				'i18n' => array(
					'error'   => __( 'Error', 'wp-idenfy' ),
					'NSError' => __( 'Network/Server error', 'wp-idenfy' ),
					'close'   => __( 'Close', 'wp-idenfy' ),
				)
			) );
		}

		public function build_button_css() {
			$css = '';
			foreach ( $this->customization_types() as $type => $conf ) {
				$css .= $this->build_button_css_for( $type, $conf['selector'] );
			}
			return $css;
		}

		private function build_button_css_for( $type, $selector ) {
			$c   = $this->get_customization( $type );
			$css = '';
			if ( ! empty( $c['advanced_css'] ) ) {
				$css .= $c['advanced_css'] . "\n";
			}
			$full = $selector . ', ' . $selector . ':link, ' . $selector . ':visited, ' . $selector . ':hover, ' . $selector . ':focus';
			$css .= $full . " {";
			$css .= "background-color: " . $c['bg_color'] . " !important;";
			$css .= "border-color: " . $c['bg_color'] . " !important;";
			$css .= "color: " . $c['text_color'] . " !important;";
			$css .= "border-radius: " . absint( $c['border_radius'] ) . "px !important;";
			$css .= "padding: " . absint( $c['padding_y'] ) . "px " . absint( $c['padding_x'] ) . "px !important;";
			$css .= "font-size: " . absint( $c['font_size'] ) . "px !important;";
			$css .= "}";
			return $css;
		}

		public function output_shortcode( $atts ) {
			$atts = shortcode_atts( array(
				'on_complete_enable' => '',
				'sync_field'         => '',
			), $atts, 'IDENFY' );

			$s = $this->get_kyc_settings();
			$c = $this->get_customization();

			$data = array(
				'on_complete_enable'  => $atts['on_complete_enable'],
				'sync_field'          => $atts['sync_field'],
				'accept_suspected'    => ! empty( $s['accept_suspected'] ) ? 'true' : '',
				'accept_unverified'   => ! empty( $s['accept_unverified'] ) ? 'true' : '',
				'redirect'            => $s['redirect'],
				'redirect_failed'     => $s['redirect_failed'],
				'redirect_unverified' => $s['redirect_unverified'],
				'close_button_text'   => $s['close_button_text'],
				'hide_on_complete'    => ! empty( $s['hide_on_complete'] ) ? 'true' : '',
				'hide_button_on_complete' => ! empty( $s['hide_button_on_complete'] ) ? 'true' : '',
			);

			$data_attrs = '';
			foreach ( $data as $k => $v ) {
				if ( $v !== '' ) {
					$data_attrs .= ' data-' . esc_attr( str_replace( '_', '-', $k ) ) . '="' . esc_attr( $v ) . '"';
				}
			}

			return '<a href="#" class="idenfy-button"' . $data_attrs . '>' . esc_html( $c['button_text'] ) . '<i class="fa fa-circle-notch fa-spin ajax-loader"></i></a>';
		}

		private function sanitize_kyb_identifier( $value ) {
			return substr( trim( sanitize_text_field( (string) $value ) ), 0, 64 );
		}

		public function output_kyb_shortcode( $atts ) {
			$atts = shortcode_atts( array(
				'client_id'               => '',
				'external_ref'            => '',
				'flow'                    => '',
				'theme'                   => '',
				'locale'                  => '',
				'lifetime'                => '',
				'questionnaire'           => '',
				'questionnaire_required'  => '',
				'tags'                    => '',
				'on_complete_enable'      => '',
				'sync_field'              => '',
				'hide_on_complete'        => '',
				'hide_button_on_complete' => '',
				'close_button_text'       => '',
				'redirect'                => '',
				'button_text'             => '',
			), $atts, 'IDENFY_KYB' );

			if ( $atts['redirect'] !== '' ) {
				$atts['redirect'] = esc_url_raw( $atts['redirect'] );
			}

			$c           = $this->get_customization( 'kyb' );
			$button_text = $atts['button_text'] !== '' ? $atts['button_text'] : $c['button_text'];
			unset( $atts['button_text'] );

			$data_attrs = '';
			foreach ( $atts as $k => $v ) {
				if ( $v !== '' ) {
					$data_attrs .= ' data-' . esc_attr( str_replace( '_', '-', $k ) ) . '="' . esc_attr( $v ) . '"';
				}
			}

			return '<a href="#" class="idenfy-kyb-button"' . $data_attrs . '>' . esc_html( $button_text ) . '<i class="fa fa-circle-notch fa-spin ajax-loader"></i></a>';
		}

		public function ajax_get_kyc_token() {
			$client_id = isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '';
			$client_id = substr( $client_id, 0, 100 );

			$token = $this->get_token( $client_id );
			if ( ! $token ) wp_send_json_error();

			wp_send_json_success( array( 'token' => $token ) );
		}

		private function get_option( $option_name, $default = '' ) {
			if ( is_null( $this->options ) ) $this->options = ( array ) get_option( $this->options_name, array() );
			if ( isset( $this->options[$option_name] ) ) return $this->options[$option_name];
			return $default;
		}

		private function api_request( $api_key, $api_secret, $uuid ) {
			$result = wp_remote_post( WP_IDENFY_ENDPOINT_URL, array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
					'Content-Type'  => 'application/json; charset=utf-8',
				),
				'body'    => wp_json_encode( array( 'clientId' => $uuid ) ),
			) );

			if ( is_wp_error( $result ) ) return $result;

			$object = json_decode( wp_remote_retrieve_body( $result ) );

			if ( ! $object ) return new WP_Error( 'error', __( 'Invalid server response', 'wp-idenfy' ) );

			return $object;
		}

		private function get_token( $client_id = '' ) {
			$api_key = $this->get_option( 'api_key' );
			$api_secret = $this->get_option( 'api_secret' );
			if ( $api_key === '' || $api_secret === '' ) return false;

			$uuid = ( $client_id !== '' ) ? $client_id : 'wordpress-kyc-' . round( microtime( true ) * 1000 );
			$result = $this->api_request( $api_key, $api_secret, $uuid );
			if ( is_wp_error( $result ) ) return false;

			if ( property_exists( $result, 'identifier' ) && $result->identifier === 'UNAUTHORIZED' ) return false;
			if ( ! property_exists( $result, 'authToken' ) ) return false;

			return $result->authToken;
		}

		public function ajax_get_kyb_token() {
			$overrides = array(
				'client_id'              => isset( $_POST['client_id'] ) ? sanitize_text_field( wp_unslash( $_POST['client_id'] ) ) : '',
				'external_ref'           => isset( $_POST['external_ref'] ) ? sanitize_text_field( wp_unslash( $_POST['external_ref'] ) ) : '',
				'flow'                   => isset( $_POST['flow'] ) ? sanitize_text_field( wp_unslash( $_POST['flow'] ) ) : '',
				'theme'                  => isset( $_POST['theme'] ) ? sanitize_text_field( wp_unslash( $_POST['theme'] ) ) : '',
				'locale'                 => isset( $_POST['locale'] ) ? sanitize_text_field( wp_unslash( $_POST['locale'] ) ) : '',
				'lifetime'               => isset( $_POST['lifetime'] ) ? absint( $_POST['lifetime'] ) : 0,
				'questionnaire'          => isset( $_POST['questionnaire'] ) ? sanitize_text_field( wp_unslash( $_POST['questionnaire'] ) ) : '',
				'questionnaire_required' => isset( $_POST['questionnaire_required'] ) ? sanitize_text_field( wp_unslash( $_POST['questionnaire_required'] ) ) : '',
				'tags'                   => isset( $_POST['tags'] ) ? sanitize_text_field( wp_unslash( $_POST['tags'] ) ) : '',
			);

			$result = $this->create_kyb_token( $overrides );
			if ( is_wp_error( $result ) ) {
				wp_send_json_error( array( 'message' => $result->get_error_message() ) );
			}
			wp_send_json_success( array( 'token' => $result ) );
		}

		public function create_kyb_token( $overrides = array() ) {
			$api_key    = $this->get_option( 'api_key' );
			$api_secret = $this->get_option( 'api_secret' );
			if ( $api_key === '' || $api_secret === '' ) {
				return new WP_Error( 'no_credentials', __( 'iDenfy API credentials are not configured.', 'wp-idenfy' ) );
			}

			$lifetime = ! empty( $overrides['lifetime'] )
				? max( 60, min( 2592000, (int) $overrides['lifetime'] ) )
				: 3600;
			$body     = array(
				'tokenType' => 'FORM',
				'lifetime'  => $lifetime,
			);

			$client_id = ( isset( $overrides['client_id'] ) && $overrides['client_id'] !== '' )
				? $overrides['client_id']
				: 'wordpress-kyb-' . round( microtime( true ) * 1000 );
			$body['clientId'] = substr( $client_id, 0, 100 );

			if ( ! empty( $overrides['external_ref'] ) ) {
				$body['externalRef'] = substr( $overrides['external_ref'], 0, 40 );
			}

			$flow = ! empty( $overrides['flow'] ) ? $this->sanitize_kyb_identifier( $overrides['flow'] ) : '';
			if ( $flow !== '' ) {
				$body['flow'] = $flow;
			}

			$theme = ! empty( $overrides['theme'] ) ? $this->sanitize_kyb_identifier( $overrides['theme'] ) : '';
			if ( $theme !== '' ) {
				$body['theme'] = $theme;
			}

			$allowed_locs = array( 'en', 'es', 'fr', 'ru', 'de', 'it', 'pl', 'lt', 'lv', 'et', 'cs', 'ro', 'hu', 'ja', 'bg', 'nl', 'pt' );
			$locale = ! empty( $overrides['locale'] ) ? $overrides['locale'] : '';
			if ( in_array( $locale, $allowed_locs, true ) ) {
				$body['locale'] = $locale;
			}

			// Questionnaire is ignored when a flow is set (the flow controls its own questionnaire).
			$questionnaire = ! empty( $overrides['questionnaire'] ) ? $overrides['questionnaire'] : '';
			if ( isset( $overrides['questionnaire_required'] ) && $overrides['questionnaire_required'] !== '' ) {
				$questionnaire_required = ( $overrides['questionnaire_required'] === 'true' || $overrides['questionnaire_required'] === '1' );
			} else {
				$questionnaire_required = true;
			}
			if ( $flow === '' && $questionnaire !== '' ) {
				$body['questionnaire']         = $questionnaire;
				$body['questionnaireRequired'] = $questionnaire_required;
			}

			if ( ! empty( $overrides['tags'] ) ) {
				$tags = array_filter( array_map( 'trim', explode( ',', $overrides['tags'] ) ) );
				$tags = array_slice( $tags, 0, 5 );
				$tags = array_map( function( $t ) { return substr( $t, 0, 32 ); }, $tags );
				if ( ! empty( $tags ) ) {
					$body['tags'] = array_values( $tags );
				}
			}

			$response = wp_remote_post( WP_IDENFY_KYB_ENDPOINT_URL, array(
				'headers' => array(
					'Authorization' => 'Basic ' . base64_encode( $api_key . ':' . $api_secret ),
					'Content-Type'  => 'application/json; charset=utf-8',
					'Accept'        => 'application/json',
				),
				'body'    => wp_json_encode( $body ),
				'timeout' => 20,
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$code   = wp_remote_retrieve_response_code( $response );
			$object = json_decode( wp_remote_retrieve_body( $response ) );

			if ( $code === 201 && $object && property_exists( $object, 'tokenString' ) ) {
				return $object->tokenString;
			}

			$message = __( 'Failed to create KYB session.', 'wp-idenfy' );
			if ( $object && property_exists( $object, 'identifier' ) ) {
				if ( $object->identifier === 'UNAUTHORIZED' ) {
					$message = __( 'Invalid iDenfy API credentials.', 'wp-idenfy' );
				} elseif ( $object->identifier === 'PARTNER_CONTRACT_ERROR' ) {
					$message = __( 'KYB session creation is not enabled on your iDenfy account. Contact iDenfy support to enable it.', 'wp-idenfy' );
				} elseif ( property_exists( $object, 'message' ) ) {
					$message = (string) $object->message;
				}
			}
			return new WP_Error( 'kyb_token_failed', $message );
		}
	}
}
WP_Idenfy::get_instance();
